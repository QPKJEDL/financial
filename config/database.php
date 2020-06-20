<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

//        'sqlite' => [
//            'driver' => 'sqlite',
//            'database' => env('DB_DATABASE', database_path('database.sqlite')),
//            'prefix' => '',
//        ],


        'mysql' => [
            'read' => [
                'host' => ['101.32.22.231'],
            ],
            'write' => [
                'host' => ['101.32.22.231'],
            ],

            'sticky'    => false,
            'driver'    => 'mysql',
            'database'  => 'huanqiu',
            'username'  => 'huanqiu',
            'password'  => 'lcf2954626',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'hq_',

        ],

        'mysql2' => [
            'host' => ['119.28.226.32'],
            'sticky'    => false,
            'driver'    => 'mysql',
            'database'  => 'rabbit',
            'username'  => 'rabbit',
            'password'  => 'eGpCtxbW5LE4Jcxf',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'zf_',

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'phpredis',

        'default' => [

            'host' => "127.0.0.1",
            'password' => "lcf2954626",
            'port' => '6379',
            'database' => 0,
            'prefix' => "hq_",
        ],

    ],

];
