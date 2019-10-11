<?php

namespace Backpack\PageManager\app\Http\Controllers\Admin;

use App\PageTemplates;
// VALIDATION: change the requests to match your own file names if you need form validation
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\PageManager\app\Http\Requests\PageRequest;

class PageCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { create as traitCreate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\CloneOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;

    use PageTemplates;

    public function setup()
    {

        $this->crud->allowAccess('clone');
        $this->crud->setDefaultPageLength(-1); // show all
        
        $this->crud->setModel(config('backpack.pagemanager.page_model_class', 'Backpack\PageManager\app\Models\Page'));
        $this->crud->setRoute(config('backpack.base.route_prefix').'/page');
        $this->crud->setEntityNameStrings(trans('backpack::pagemanager.page'), trans('backpack::pagemanager.pages'));
    
        if (!$this->request->has('order')) {
            $this->crud->orderBy('lft');
        }

    }

    protected function setupReorderOperation()
    {
        // define which model attribute will be shown on draggable elements 
        $this->crud->set('reorder.label', 'name');
        // define how deep the admin is allowed to nest the items
        // for infinite levels, set it to 0
        $this->crud->set('reorder.max_level', 0);
    }

    protected function setupListOperation()
    {


        $this->crud->addColumn([
            'name' => 'semanticPath',
            'label' => 'Page',
            'orderable' => false,
        ]);

        $this->crud->addColumn([
                                'name' => 'template',
                                'label' => trans('backpack::pagemanager.template'),
                                'type' => 'model_function',
                                'function_name' => 'getTemplateName',
                                'orderable' => false,
        ]);

        $this->crud->addColumn([
                                'name' => 'full_path_slug',
                                'label' => trans('backpack::pagemanager.slug'),
                                'orderable' => false,
                                'type' => 'closure',
                                'function' => function($entry) {

                                    // if slug is too long, truncate backwards, so we can still read it

                                    $len = strlen($entry->fullPathSlug);
                                    $limit = 60;

                                    return $len > $limit ?
                                             "...".substr($entry->fullPathSlug,$len-$limit-1,$len-1)
                                             : $entry->fullPathSlug;

                                },
                                'limit' => 300 // essentiall overriden by the above logic
        ]);


        $this->crud->addButtonFromModelFunction('line', 'open', 'getOpenButton', 'beginning');
    }

    // -----------------------------------------------
    // Overwrites of CrudController
    // -----------------------------------------------

    protected function setupCreateOperation()
    {
        // Note:
        // - default fields, that all templates are using, are set using $this->addDefaultPageFields();
        // - template-specific fields are set per-template, in the PageTemplates trait;

        $this->addDefaultPageFields(\Request::input('template'));
        $this->useTemplate(\Request::input('template'));

        $this->crud->setValidation(PageRequest::class);
    }

    protected function setupUpdateOperation()
    {
        // if the template in the GET parameter is missing, figure it out from the db
        $template = \Request::input('template') ?? $this->crud->getCurrentEntry()->template;

        $this->addDefaultPageFields($template);
        $this->useTemplate($template);

        $this->crud->setValidation(PageRequest::class);
    }

    // -----------------------------------------------
    // Methods that are particular to the PageManager.
    // -----------------------------------------------



    /**
     * $preSave and $postSave exist for extending purposes.
     * Eg. you want to use Visual Composer 
     */
    public function clone($id, $preSave=null, $postSave=null)
    {
        $this->crud->hasAccessOrFail('create');
        $clonedPage = $this->crud->model->findOrFail($id)->replicate();

        if (is_callable($preSave)) {
            $preSave();
        }
        
        /*
        $clonedVisualComposerRows = VisualComposerRow::where('model_id', $id)
                                                        ->where('model_class', get_class($this->crud->model))
                                                        ->get();
        */

        $clonedPage->save();


        if (is_callable($postSave)) {
            $postSave();
        }

        /*
        foreach($clonedVisualComposerRows as $vcRow) {

                    $clonedVcRow = $vcRow->replicate();
                    $clonedVcRow->model_id = $clonedPage->id;
                    $clonedVcRow->save();
        }
        */

        return (string) $clonedPage->push();
        
    }


    /**
     * Populate the create/update forms with basic fields, that all pages need.
     *
     * @param string $template The name of the template that should be used in the current form.
     */
    public function addDefaultPageFields($template = false)
    {
        $this->crud->addField([
            'name' => 'template',
            'label' => trans('backpack::pagemanager.template'),
            'type' => 'select_page_template',
            'view_namespace'  => 'pagemanager::fields',
            'options' => $this->getTemplatesArray(),
            'value' => $template,
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name',
            'label' => trans('backpack::pagemanager.page_name'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            // 'disabled' => 'disabled'
        ]);
        $this->crud->addField([
            'name' => 'title',
            'label' => trans('backpack::pagemanager.page_title'),
            'type' => 'text',
            // 'disabled' => 'disabled'
        ]);

        $this->crud->addField([
            'name' => 'path_slug',
            'label' => 'Path Slug',
            'hint' => 'Automatically generated from the grouping & ordering of the page.',
            'type' => 'text',
            'attributes' => [
                'readonly'=>'readonly',
                'disabled'=>'disabled',
            ]
        ]); 

        $this->crud->addField([
            'name' => 'slug',
            'label' => trans('backpack::pagemanager.page_slug'),
            'type' => 'text',
            'hint' => trans('backpack::pagemanager.page_slug_hint'),
            // 'disabled' => 'disabled'
        ]);
        
    }

    /**
     * Add the fields defined for a specific template.
     *
     * @param  string $template_name The name of the template that should be used in the current form.
     */
    public function useTemplate($template_name = false)
    {
        $templates = $this->getTemplates();

        // set the default template
        if ($template_name == false) {
            $template_name = $templates[0]->name;
        }

        // actually use the template
        if ($template_name) {
            $this->{$template_name}();
        }
    }

    /**
     * Get all defined templates.
     */
    public function getTemplates($template_name = false)
    {
        $templates_array = [];

        $templates_trait = new \ReflectionClass('App\PageTemplates');
        $templates = $templates_trait->getMethods(\ReflectionMethod::IS_PRIVATE);

        if (! count($templates)) {
            abort(503, trans('backpack::pagemanager.template_not_found'));
        }

        return $templates;
    }

    /**
     * Get all defined template as an array.
     *
     * Used to populate the template dropdown in the create/update forms.
     */
    public function getTemplatesArray()
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            $templates_array[$template->name] = str_replace('_', ' ', title_case($template->name));
        }

        return $templates_array;
    }
}
