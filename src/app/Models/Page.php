<?php

namespace Backpack\PageManager\app\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Model;
use Str;

class Page extends Model
{
    use CrudTrait;
    use Sluggable;
    use SluggableScopeHelpers;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'pages';
    protected $primaryKey = 'id';
    public $timestamps = true;
    // protected $guarded = ['id'];
    protected $fillable = ['template', 'name', 'title', 'slug', 'content', 'extras',
                            'parent_id, lft, rgt, depth'];
    // protected $hidden = [];
    // protected $dates = [];
    protected $fakeColumns = ['extras'];
    protected $casts = [
        'extras' => 'array',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'slug_or_title',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function getTemplateName()
    {
        return str_replace('_', ' ', Str::title(Str::snake($this->template, ' ')));
    }

    public function getOpenButton()
    {
        return '<a class="btn btn-sm btn-link" href="'.$this->getPageLink().'" target="_blank">'.
            '<i class="la la-eye"></i> '.trans('backpack::pagemanager.open').'</a>';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    // The slug is created automatically from the "name" field if no slug exists.
    public function getSlugOrTitleAttribute()
    {
        if ($this->slug != '') {
            return $this->slug;
        }

        return $this->title;
    }

    public static function findByFullPathSlug($path)
    {
        return collect(explode('/', $path))->filter()->reduce(
            function ($page, $slug) {
                if (! $page) {
                    return static::where('slug', $slug)->whereNull('parent_id')->firstOrfail();
                }
                return $page->children()->where('slug', $slug)->firstOrFail();
            }
        );
    }

    public function parentPage()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('lft');
    }

    /**
     * Override PageManager\Page default link generator to introduce sub-pages
     */
    public function getPageLink()
    {
        return url($this->fullPathSlug);
    }

    public function getPathAttribute()
    {
        return $this->pagePathAsString();
    }

    public function getFullPathAttribute()
    {
        return $this->pagePathAsString(true);
    }

    public function getFullPathSlugAttribute()
    {
        return $this->pagePathAsString(true, '/', true);
    }

    public function getPathSlugAttribute()
    {
        return $this->pagePathAsString(false, '/', true);
    }

    public function getSemanticPathAttribute()
    {
        return $this->semanticPagePathAsString();
    }

    public function pagePathAsString($includeSelf = false, $sep = ' ⇨ ', $useSlug = false)
    {
        $ancestorTokens = [];

        $instance = $this;

        if ($includeSelf) {
            $ancestorTokens[] = $useSlug ? $this->slug : $this->name;
        }

        while ($instance->parent_id) {
            $instance = $instance->parentPage;
            $ancestorTokens[] = $useSlug ? $instance->slug : $instance->name;
        }

        return join($sep, array_reverse($ancestorTokens));
    }

    private function isLastSibling()
    {
        if ($this->parentPage) {
            $lastSibling = $this->parentPage->children->last();
            return $lastSibling->id == $this->id;
        } else {
            return (Page::where('parent_id', null)
                ->orderBy('lft', 'desc')
                ->limit(1)
                ->get()
                ->last()
                ->id == $this->id);
        }
    }

    public function semanticPagePathAsString($useSlug = false)
    {

        // TODO, modernise the ascii rendering and remove this early return.
        return $this->name;

        /*
        $ancestorTokens = [];
        $instance = $this;

        while($instance->parent_id){
        $instance = $instance->parentPage;
        $ancestorTokens[] = $instance;
        }

        $semanticPath = "";

        foreach(array_reverse($ancestorTokens) as $instance) {

        if($instance->parentPage) {
        foreach($instance->parentPage->children as $grand) {
          if($grand->lft > $instance->lft) {
            $semanticPath .= '│ ';
          }
        }
        } else {
        $semanticPath .= '│ ';
        }

        $semanticPath .= '　' ;
        }

        $semanticPath .= $this->isLastSibling() ? '└─ ' : '├─ ';
        $semanticPath .=  $useSlug ? $this->slug : $this->name;


        return $semanticPath;
        */
    }
}
