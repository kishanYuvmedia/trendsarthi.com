<?php

Route::get('/', 'HomeController@index');
Route::get('/viewNews/{blog}', 'HomeController@viewNews');
Route::get('chart-data/{type}', 'HomeController@getChartData');


