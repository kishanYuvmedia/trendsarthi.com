<?php
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Intraday;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Carbon\Carbon;
Route::get('/dashboard', 'UserDashboardController@index')->name('dashboard');
// Admin
Route::get('/profile', 'UserDashboardController@profile')->name('profile');
Route::get('/edit_profile', 'UserDashboardController@edit')->name('edit');
Route::patch('/edit_profile', 'UserDashboardController@update')->name('update');
Route::get('/change_password', 'UserDashboardController@change_password')->name('password_change');
Route::patch('/change_password', 'UserDashboardController@update_password')->name('change_password');
Route::patch('/change_password', 'UserDashboardController@update_password')->name('change_password');
//afterloginuser
Route::get('/derivatives/{type}', 'UserDashboardController@Getdata')->name('derivatives');
Route::get('/open-interest-chart-data', 'UserDashboardController@getOpenInterestChartData')->name('open-interest-chart-data');
Route::get('/open-interest-chart-data-two', 'UserDashboardController@getOpenInterestChartDatatwo')->name('open-interest-chart-data-two');
Route::get('/api/getcurrentstrike/{type}', 'UserDashboardController@getcurrentstrike')->name('getcurrentstrike');
Route::get('/api/getexpdate/{type}', 'UserDashboardController@getexpdate')->name('getexpdate');
Route::get('/api/getoptiondata/{type}/{currentOptionSrike}', 'UserDashboardController@getoptiondata')->name('getoptiondata');
Route::get('/adddataIntradaynifty','UserDashboardController@adddataIntradaynifty')->name('adddataIntradaynifty');
