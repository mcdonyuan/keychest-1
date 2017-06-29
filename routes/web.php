<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'SearchController@show')->name('/');
Route::post('/', 'SearchController@search');
Route::post('/intro', 'SearchController@search')->name('intro');
Route::post('feedback', 'SearchController@voteFeedback')->name('feedback');
Route::post('rfeedback', 'SearchController@restSubmitFeedback')->name('rfeedback');

Route::get('scan', 'SearchController@show');
Route::post('scan', 'SearchController@search');

Route::get('submitJob', 'SearchController@restSubmitJob');
Route::get('jobState', 'SearchController@restGetJobState');
Route::get('jobResult', 'SearchController@restJobResults');

// Auth routes
Route::auth();
Auth::routes();

// Terms
Route::get('terms', function () {
    return view('terms');
});

Route::get('privacy-policy', function () {
    return view('policy');
});

// Registered user space
Route::get('home', 'HomeController@index')->name('home');
Route::get('home/servers', 'ServersController@index')->name('servers');
Route::get('home/servers/get', 'ServersController@getList')->name('servers/get');
Route::post('home/servers/add', 'ServersController@add')->name('servers/add');
Route::post('home/servers/del', 'ServersController@del')->name('servers/del');
Route::post('home/servers/update', 'ServersController@update')->name('servers/update');
Route::post('home/servers/canAdd', 'ServersController@canAddHost')->name('servers/canAdd');
Route::get('home/scan', 'SearchController@showHome')->name('home/scan')->middleware('auth');

Route::get('home/dashboard/data', 'DashboardController@loadActiveCerts')
    ->name('dashboard/data')->middleware('auth');

//Please do not remove this if you want adminlte:route and adminlte:link commands to works correctly.
#adminlte_routes

