<?php

namespace App\Http\Controllers;

use Bozboz\PageManager\app\Models\Page;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function index($slug, $subs = null)
    {
        if ($subs) {
            $slug = $slug.'/'.$subs;
        }

        $page = Page::findByFullPathSlug($slug);

        if (!$page) {
            abort(404, 'Please go back to our <a href="'.url('').'">homepage</a>.');
        }

        $this->data['title'] = $page->title;
        $this->data['page'] = $page->withFakes();

        return view('pages.'.$page->template, $this->data);
    }

    public function home()
    {
        return $this->index('home');
    }
}
