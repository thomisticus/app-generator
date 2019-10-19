<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    */

    'path' => [

        'migration' => database_path('migrations/'),

        'model' => app_path('Models/'),

        'repository' => app_path('Repositories/'),

        'service' => app_path('Services/'),

        'trait' => app_path('Traits/'),

        'routes' => base_path('routes/web.php'),

        'api_routes' => base_path('routes/api.php'),

        'request' => app_path('Http/Requests/'),

        'api_request' => app_path('Http/Requests/API/'),

        'controller' => app_path('Http/Controllers/'),

        'api_controller' => app_path('Http/Controllers/API/'),

        'test_trait' => base_path('tests/Traits/'),

        'repository_test' => base_path('tests/Repositories/'),

        'api_test' => base_path('tests/APIs/'),

        'tests' => base_path('tests/'),

        'schema_files' => resource_path('model_schemas/'),

        'templates_dir' => resource_path('thomisticus/thomisticus-generator-templates/'),

        'seeder' => database_path('seeds/'),

        'database_seeder' => database_path('seeds/DatabaseSeeder.php'),

        'factory' => database_path('factories/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    */

    'namespace' => [

        'model' => 'App\Models',

        'repository' => 'App\Repositories',

        'service' => 'App\Services',

        'trait' => 'App\Traits',

        'controller' => 'App\Http\Controllers',

        'api_controller' => 'App\Http\Controllers\API',

        'request' => 'App\Http\Requests',

        'api_request' => 'App\Http\Requests\API',

        'repository_test' => 'Tests\Repositories',

        'api_test' => 'Tests\APIs',

        'tests' => 'Tests',
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    */

    'templates' => 'adminlte-templates',

    /*
    |--------------------------------------------------------------------------
    | Model extend class
    |--------------------------------------------------------------------------
    |
    */

    'model_extend_class' => 'Eloquent',

    /*
    |--------------------------------------------------------------------------
    | API routes prefix & version
    |--------------------------------------------------------------------------
    |
    */

    'api_prefix' => 'api',

    'api_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    */

    'options' => [

        'softDelete' => true,

        'save_schema_file' => true,

        'tables_searchable_default' => false,

        'repository_pattern' => true,

        'excluded_fields' => ['id'], // Array of columns that doesn't required while creating module
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefixes
    |--------------------------------------------------------------------------
    |
    */

    'prefixes' => [

        'route' => '',  // using admin will create route('admin.?.index') type routes

        'path' => '',

        'public' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-Ons
    |--------------------------------------------------------------------------
    |
    */

    'add_on' => [

        'tests' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamp Fields
    |--------------------------------------------------------------------------
    |
    */

    'timestamps' => [

        'enabled' => true,

        'created_at' => 'created_at',

        'updated_at' => 'updated_at',

        'deleted_at' => 'deleted_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Save model files to `App/Models` when use `--prefix`. see #208
    |--------------------------------------------------------------------------
    |
    */
    'ignore_model_prefix' => false,

    /*
    |--------------------------------------------------------------------------
    | Specify custom doctrine mappings as per your need
    |--------------------------------------------------------------------------
    |
    */
    'from_table' => [

        'doctrine_mappings' => [],
    ],

];
