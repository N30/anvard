<?php

if (Config::get('anvard::routes.index'))
{
	Route::get(Config::get('anvard::routes.index','anvard'),array(
		'as' => 'anvard::routes.index',
		'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@index',
	));
}
if (Config::get('anvard::routes.login'))
{
    Route::get(Config::get('anvard::routes.login','anvard/login/{provider}'),array(
		'as' => 'anvard::routes.login',
		'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@login',
    ));
}
if (Config::get('anvard::routes.logout'))
{
    Route::get(Config::get('anvard::routes.logout','anvard/logout/{provider}'),array(
        'as' => 'anvard::routes.logout',
        'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@logout',
    ));
}
if (Config::get('anvard::routes.endpoint'))
{
    Route::get(Config::get('anvard::routes.endpoint','anvard/endpoint'),array(
		'as' => 'anvard::routes.endpoint',
		'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@endpoint',
    ));
}
