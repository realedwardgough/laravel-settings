<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Settings Table
    |--------------------------------------------------------------------------
    |
    | This value is the name of the settings table which will be used to store
    | the settings of your application.
    |
    */
    'table' => 'settings',

    /*
    |--------------------------------------------------------------------------
    | Settings Cache
    |--------------------------------------------------------------------------
    |
    | These values allow you to handle the cache configuration of the settings
    | that are stored. It's enabled by default, but you can remove it, change the
    | key which is used to store the cached data, and update the store length.
    | If you leave ttl null it will default to forever.
    |
    */
    'cache' => [
        'enabled' => true,
        'key' => 'egough.settings.all',
        'ttl' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Defaults
    |--------------------------------------------------------------------------
    |
    | If you have settings which are standardised by default, you can add them
    | here. Examples would be the site name, or if billing is enabled on your
    | application. Defaults can be overwritten later if you cause to update
    | it using the setter methods.
    |
    */
    'defaults' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Settings
    |--------------------------------------------------------------------------
    |
    | This value is the name of the settings table which will be used to store
    | the settings of your models. Also, here the cache configuration of the settings
    | for models that are stored. It's enabled by default, but you can remove it,
    | change the key which is used to store the cached data, and update the store length.
    | If you leave ttl null it will default to forever.
    |
    */
    'model' => [
        'table' => 'model_settings',
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
            'key_prefix' => 'egough.model_settings.',
        ],
    ],

];
