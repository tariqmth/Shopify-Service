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

Auth::routes();

$this->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register')->middleware('signed');
$this->post('register', 'Auth\RegisterController@register')->middleware('signed');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/history', 'SyncJobsHistoryController@allForWeb')->name('history.show');
Route::get('/history/{uniqueId}', 'SyncJobsHistoryController@getForWeb')->name('history.item.show');
Route::get('/logs', 'SyncJobsLogsController@allForWeb')->name('logs.show');
Route::get('/queue', 'QueueStatsController@allForWeb')->name('queue.show');
