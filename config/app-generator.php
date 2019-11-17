<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    */

    'path' => [

        'controller' => app_path('Http/Controllers/'),

        'request' => app_path('Http/Requests/'),

        'api_routes' => base_path('routes/api.php'),

        'api_test' => base_path('tests/APIs/'),

        'database_seeder' => database_path('seeds/DatabaseSeeder.php'),

        'factory' => database_path('factories/'),

        'migration' => database_path('migrations/'),

        'model' => app_path('Models/'),

        'repository' => app_path('Repositories/'),

        'repository_test' => base_path('tests/Repositories/'),

        'routes' => base_path('routes/web.php'),

        'schema_files' => resource_path('model_schemas/'),

        'seeder' => database_path('seeds/'),

        'service' => app_path('Services/'),

        'templates_dir' => resource_path('thomisticus/thomisticus-generator-templates/'),

        'test_trait' => base_path('tests/Traits/'),

        'tests' => base_path('tests/'),

        'api_tests' => base_path('tests/'),

        'trait' => app_path('Traits/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    */

    'namespace' => [

        'controller' => 'App\Http\Controllers',

        'request' => 'App\Http\Requests',

        'api_test' => 'Tests\APIs',

        'model' => 'App\Models',

        // Model extend class. E.g.: Illuminate\Database\Eloquent\Model
        'model_extend_class' => 'App\Models\BaseModel',

        'repository' => 'App\Repositories',

        'repository_test' => 'Tests\Repositories',

        'service' => 'App\Services',

        'tests' => 'Tests',

        'trait' => 'App\Traits',
    ],

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

        'soft_delete' => true,

        'save_schema_file' => true,

        'tables_searchable_default' => false,

        'repository_pattern' => false,

        'generate_seeder' => true,

        // Array of columns that doesn't required while creating module
        'excluded_fields' => ['id', 'created_at', 'updated_at', 'deleted_at'],
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

        'enabled' => false,

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
