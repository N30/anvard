<?php

if (Config::get('anvard::routes.index')) {
	Route::get(Config::get('anvard::routes.index','anvard'),array(
		'as' => 'anvard::routes.index',
		'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@index',
	));
}
if (Config::get('anvard::routes.login')) {
    Route::get(Config::get('anvard::routes.login','anvard/login/{provider}'),array(
		'as' => 'anvard::routes.login',
		'uses' => '\\Atticmedia\\Anvard\\Controllers\\AnvardController@login',
    ));
}
if (Config::get('anvard::routes.endpoint')) {
    Route::get(
        Config::get('anvard::routes.endpoint'),
        array(
            'as' => 'anvard.routes.endpoint',
            function() {
                Hybrid_Endpoint::process();
            }
        )
    );
}
