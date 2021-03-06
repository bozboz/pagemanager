<?php

namespace App;

trait PageTemplates
{
    /*
    |--------------------------------------------------------------------------
    | Page Templates for Bozboz\PageManager
    |--------------------------------------------------------------------------
    |
    | Each page template has its own method, that define what fields should show up using the Backpack\CRUD API.
    | Use snake_case for naming and PageManager will make sure it looks pretty in the create/update form
    | template dropdown.
    |
    | Any fields defined here will show up after the standard page fields:
    | - select template
    | - page name (only seen by admins)
    | - page title
    | - page slug
    */

    protected function metas()
    {
        $this->crud->addField([
            'name' => 'metas_separator',
            'type' => 'custom_html',
            'value' => '<br><h3>'.trans('backpack::pagemanager.metas').'</h3><hr>',
        ]);
        $this->crud->addField([
            'name' => 'meta_title',
            'label' => trans('backpack::pagemanager.meta_title'),
            'fake' => true,
            'store_in' => 'extras',
        ]);
        $this->crud->addField([
            'name' => 'meta_description',
            'label' => trans('backpack::pagemanager.meta_description'),
            'fake' => true,
            'store_in' => 'extras',
        ]);
        $this->crud->addField([
            'name' => 'meta_keywords',
            'type' => 'textarea',
            'label' => trans('backpack::pagemanager.meta_keywords'),
            'fake' => true,
            'store_in' => 'extras',
        ]);

        $this->crud->addField([
            'name' => 'metas_end_separator',
            'type' => 'custom_html',
            'value' => '<br><hr>',
        ]);
    }

    private function basicPage()
    {

        $this->metas();

        $this->crud->addField([
            'name' => 'content',
            'label' => trans('backpack::pagemanager.content'),
            'type' => 'wysiwyg',
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
        ]);
    }
}
