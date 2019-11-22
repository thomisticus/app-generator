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

        'schema_files' => resource_path('docs/model_schemas/'),

        'postman' => resource_path('docs/'),

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

    /*
    |--------------------------------------------------------------------------
    | Generate a Postman collection according to the api routes.
    |--------------------------------------------------------------------------
    |
    */
    'postman' => [

        /*
         * The name for the exported collection file. Default: collection.json
         */
        'file_name' => 'collection.json',

        /*
         * The name for the exported Postman collection. Default: config('app.name')." API"
         */
        'name' => config('app.name') . " API",

        /*
         * The description for the exported Postman collection.
         */
        'description' => null,

        /*
         * The base URL to be used in the Postman collection.
         * By default, this will be the value of config('app.url').
         */
        'base_url' => config('app.url'),

        /*
         * The routes for which documentation should be generated.
         * Each group contains rules defining which routes should be included ('match', 'include' and 'exclude' sections)
         * and rules which should be applied to them ('apply' section).
         */
        'routes' => [
            [
                /*
                 * Specify conditions to determine what routes will be parsed in this group.
                 * A route must fulfill ALL conditions to pass.
                 */
                'match' => [

                    /*
                     * Match only routes whose domains match this pattern (use * as a wildcard to match any characters).
                     */
                    'domains' => [
                        '*',
                        // 'domain1.*',
                    ],

                    /*
                     * Match only routes whose paths match this pattern (use * as a wildcard to match any characters).
                     */
                    'prefixes' => [
                        '*',
                        // 'users/*',
                    ],

                    /*
                     * Match only routes registered under this version. This option is ignored for Laravel router.
                     * Note that wildcards are not supported.
                     */
                    'versions' => [
                        'v1',
                    ],
                ],

                /*
                 * Include these routes when generating documentation,
                 * even if they did not match the rules above.
                 * Note that the route must be referenced by name here (wildcards are supported).
                 */
                'include' => [
                    // 'users.index', 'healthcheck*'
                ],

                /*
                 * Exclude these routes when generating documentation,
                 * even if they matched the rules above.
                 * Note that the route must be referenced by name here (wildcards are supported).
                 */
                'exclude' => [
                    // 'users.create', 'admin.*'
                ],

                /*
                 * Specify rules to be applied to all the routes in this group when generating documentation
                 */
                'apply' => [
                    /*
                     * Specify headers to be added to the example requests
                     */
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        //'Authorization' => 'Bearer {token}',
                        // 'Api-Version' => 'v2',
                    ],
                ],
            ],
        ],
    ],
];
