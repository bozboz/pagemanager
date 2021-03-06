<?php

/*
|--------------------------------------------------------------------------
| Bozboz\PageManager Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are
| handled by the Bozboz\PageManager package.
|
*/

Route::group([
    'namespace' => '',
    'middleware' => ['web', config('backpack.base.middleware_key', 'admin')],
    'prefix' => config('backpack.base.route_prefix', 'admin'),
], function () {
    $controller = config('backpack.pagemanager.admin_controller_class', 'Bozboz\PageManager\app\Http\Controllers\Admin\PageCrudController');
    Route::crud('page', $controller);
});
