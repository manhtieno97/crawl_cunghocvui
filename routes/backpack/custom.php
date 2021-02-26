<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('question', 'QuestionCrudController');

    Route::get('crawl-id','CrawlController@getCrawl');
    Route::post('crawl-id','CrawlController@postCrawl');

    Route::get('upload-data','CrawlController@getUpload');
    Route::post('upload-data','CrawlController@postUpload');

}); // this should be the absolute last line of this file
