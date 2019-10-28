<?php

namespace Thomisticus\Generator\Common;

use Illuminate\Support\Str;

class GeneratorConfig
{
    /**
     * @var array Namespace variables array
     */
    public $namespaces;

    /**
     * @var array Path variables array
     */
    public $paths;

    /**
     * @var string Default model name
     */
    public $modelName;

    /**
     * Array of model names in multiple cases
     * @var array
     */
    public $modelNames;

    /* Prefixes */
    public $options;
    public $prefixes;

    /* Command Options */
    public $tableName;

    public $addOns;

    /** @var string */
    protected $primaryKeyName;

    /* Generator AddOns */
    private $commandData;

    public static $availableOptions = [
        'fieldsFile',
        'jsonFromGUI',
        'tableName',
        'fromTable',
        'ignoreFields',
        'save',
        'primary',
        'prefix',
        'paginate',
        'skip',
        'relations',
        'plural',
        'softDelete',
        'forceMigrate',
        'factory',
        'seeder',
        'repositoryPattern',
    ];

    public function init(CommandData &$commandData, $options = null)
    {
        if (!empty($options)) {
            self::$availableOptions = $options;
        }

        $this->modelName = $commandData->modelName;

        $this->prepareAddOns();
        $this->prepareOptions($commandData);
        $this->prepareModelNames();
        $this->preparePrefixes();
        $this->loadPaths();
        $this->prepareTableName();
        $this->preparePrimaryKeyName();
        $this->loadNamespaces($commandData);
        $commandData = $this->loadDynamicVariables($commandData);
        $this->commandData = &$commandData;
    }

    public function prepareAddOns()
    {
        $this->addOns['tests'] = config('app-generator.add_on.tests', false);
    }

    public function prepareOptions(CommandData &$commandData)
    {
        foreach (self::$availableOptions as $option) {
            $this->options[$option] = $commandData->commandObj->option($option);
        }

        if (isset(self::$availableOptions['fromTable']) && $this->options['fromTable'] && !$this->options['tableName']) {
            $commandData->commandError('tableName required with fromTable option.');
            exit;
        }

        if (empty($this->options['save'])) {
            $this->options['save'] = config('app-generator.options.save_schema_file', true);
        }

        $this->options['softDelete'] = config('app-generator.options.soft_delete', false);
        $this->options['repositoryPattern'] = config('app-generator.options.repository_pattern', false);
        $this->options['seeder'] = config('app-generator.options.generate_seeder', false);

        if (!empty($this->options['skip'])) {
            $this->options['skip'] = array_map('trim', explode(',', $this->options['skip']));
        }
    }

    public function prepareModelNames()
    {
        $modelPlural = $this->getOption('plural');

        if (empty($modelPlural)) {
            $modelPlural = Str::plural($this->modelName);
        }

        $baseNames = [
            'default' => $this->modelName,
            'plural' => $modelPlural,
            'camel' => Str::camel($this->modelName),
            'snake' => Str::snake($this->modelName),
            'camel_plural' => Str::camel($modelPlural),
            'snake_plural' => Str::snake($modelPlural),
        ];

        $customNames = [
            'dashed' => str_replace('_', '-', $baseNames['snake']),
            'dashed_plural' => str_replace('_', '-', $baseNames['snake_plural']),
            'slash' => str_replace('_', '/', $baseNames['snake']),
            'slash_plural' => str_replace('_', '/', $baseNames['snake_plural']),
            'human' => Str::title(str_replace('_', ' ', $baseNames['snake'])),
            'human_plural' => Str::title(str_replace('_', ' ', $baseNames['snake_plural'])),
        ];

        $this->modelNames = array_merge($baseNames, $customNames);
    }

    public function preparePrefixes()
    {
        $this->prefixes['route'] = explode('/', config('app-generator.prefixes.route', ''));
        $this->prefixes['path'] = explode('/', config('app-generator.prefixes.path', ''));

        if ($prefix = $this->getOption('prefix')) {
            $multiplePrefixes = explode(',', $prefix);

            $this->prefixes['route'] = array_merge($this->prefixes['route'], $multiplePrefixes);
            $this->prefixes['path'] = array_merge($this->prefixes['path'], $multiplePrefixes);
        }

        $this->prefixes['route'] = array_filter($this->prefixes['route']);
        $this->prefixes['path'] = array_filter($this->prefixes['path']);

        $routePrefix = '';
        foreach ($this->prefixes['route'] as $singlePrefix) {
            $routePrefix .= Str::camel($singlePrefix) . '.';
        }
        $this->prefixes['route'] = !empty($routePrefix) ? substr($routePrefix, 0, -1) : $routePrefix;

        $namespacePrefix = $pathPrefix = '';
        foreach ($this->prefixes['path'] as $singlePrefix) {
            $namespacePrefix .= Str::title($singlePrefix) . '\\';
            $pathPrefix .= Str::title($singlePrefix) . '/';
        }

        $this->prefixes['namespace'] = !empty($namespacePrefix) ? substr($namespacePrefix, 0, -1) : $namespacePrefix;
        $this->prefixes['path'] = !empty($pathPrefix) ? substr($pathPrefix, 0, -1) : $pathPrefix;
    }

    public function loadPaths()
    {
        $prefix = $this->prefixes['path'];

        if (!empty($prefix)) {
            $prefix .= '/';
        }

        $defaultPaths = [
            'api_controller' => app_path('Http/Controllers/API/'),
            'api_request' => app_path('Http/Requests/API/'),
            'api_routes' => base_path('routes/api.php'),
            'api_tests' => base_path('tests/'),
            'test_trait' => base_path('tests/traits/'),
            'controller' => app_path('Http/Controllers/'),
            'database_seeder' => database_path('seeds/DatabaseSeeder.php'),
            'factory' => database_path('factories/'),
            'model' => app_path('Models/'),
            'repository' => app_path('Repositories/'),
            'request' => app_path('Http/Requests/'),
            'routes' => base_path('routes/web.php'),
            'seeder' => database_path('seeds/'),
            'service' => app_path('Services/'),
        ];

        foreach ($defaultPaths as $key => $defaultPath) {
            $this->paths[$key] = $defaultPath;

            if (!in_array($key,
                ['api_routes', 'api_test', 'test_trait', 'database_seeder', 'factory', 'routes', 'seeder'])) {
                $this->paths[$key] .= $prefix;
            }
        }

        if (config('app-generator.ignore_model_prefix', false)) {
            $this->paths['model'] = config('app-generator.path.model', app_path('Models/'));
        }
    }

    public function prepareTableName()
    {
        $this->tableName = $this->getOption('tableName');

        if (empty($this->tableName)) {
            $this->tableName = $this->modelNames['snake_plural'];
        }
    }

    public function preparePrimaryKeyName()
    {
        $this->primaryKeyName = $this->getOption('primary');

        if (empty($this->primaryKeyName)) {
            $this->primaryKeyName = 'id';
        }
    }

    public function loadNamespaces(CommandData &$commandData)
    {
        $prefix = $this->prefixes['namespace'];

        if (!empty($prefix)) {
            $prefix = '\\' . $prefix;
        }

        $this->namespaces['app'] = rtrim($commandData->commandObj->getLaravel()->getNamespace(), '\\');

        $defaultNamespaces = [
            'api_controller' => 'App\Http\Controllers\API',
            'api_request' => 'App\Http\Requests\API',
            'api_tests' => 'Tests\APIs',
            'model' => 'App\Models',
            'model_extend_class' => 'Illuminate\Database\Eloquent\Model',
            'repository' => 'App\Repositories',
            'repository_tests' => 'Tests\Repositories',
            'request' => 'App\Http\Requests',
            'service' => 'App\Services',
            'tests' => 'Tests',
            'trait' => 'App\Traits',
        ];

        foreach ($defaultNamespaces as $key => $defaultNamespace) {
            $this->namespaces[$key] = config('app-generator.namespace.' . $key, 'App\Models');

            if (!in_array($key, ['tests', 'repository_tests'])) {
                $this->namespaces[$key] .= $prefix;
            }
        }

        if (config('app-generator.ignore_model_prefix', false)) {
            $this->namespaces['model'] = config('app-generator.namespace.model', 'App\Models');
        }

    }

    public function loadDynamicVariables(CommandData &$commandData)
    {
        $dynamicVars = [
            '$NAMESPACE_APP$' => $this->namespaces['app'],
            '$NAMESPACE_REPOSITORY$' => $this->namespaces['repository'],
            '$NAMESPACE_SERVICE$' => $this->namespaces['service'],
            '$NAMESPACE_TRAIT$' => $this->namespaces['trait'],
            '$NAMESPACE_MODEL$' => $this->namespaces['model'],
            '$NAMESPACE_MODEL_EXTEND$' => $this->namespaces['model_extend_class'],
            '$NAMESPACE_API_CONTROLLER$' => $this->namespaces['api_controller'],
            '$NAMESPACE_API_REQUEST$' => $this->namespaces['api_request'],
            '$NAMESPACE_REQUEST$' => $this->namespaces['request'],
            '$NAMESPACE_API_TESTS$' => $this->namespaces['api_tests'],
            '$NAMESPACE_REPOSITORIES_TESTS$' => $this->namespaces['repository_tests'],
            '$NAMESPACE_TESTS$' => $this->namespaces['tests'],
            '$TABLE_NAME$' => $this->tableName,
            '$TABLE_NAME_TITLE$' => Str::studly($this->tableName),
            '$PRIMARY_KEY_NAME$' => $this->primaryKeyName,
            '$MODEL_NAME$' => $this->modelName,
            '$MODEL_NAME_CAMEL$' => $this->modelNames['camel'],
            '$MODEL_NAME_PLURAL$' => $this->modelNames['plural'],
            '$MODEL_NAME_PLURAL_CAMEL$' => $this->modelNames['camel_plural'],
            '$MODEL_NAME_SNAKE$' => $this->modelNames['snake'],
            '$MODEL_NAME_PLURAL_SNAKE$' => $this->modelNames['snake_plural'],
            '$MODEL_NAME_DASHED$' => $this->modelNames['dashed'],
            '$MODEL_NAME_PLURAL_DASHED$' => $this->modelNames['dashed_plural'],
            '$MODEL_NAME_SLASH$' => $this->modelNames['slash'],
            '$MODEL_NAME_PLURAL_SLASH$' => $this->modelNames['slash_plural'],
            '$MODEL_NAME_HUMAN$' => $this->modelNames['human'],
            '$MODEL_NAME_PLURAL_HUMAN$' => $this->modelNames['human_plural'],
            '$PATH_PREFIX$' => !empty($this->prefixes['namespace']) ? $this->prefixes['namespace'] . '\\' : '',
            '$API_PREFIX$' => config('app-generator.api_prefix', 'api'),
            '$API_VERSION$' => config('app-generator.api_version', 'v1'),
            '$FILES$' => '',
            '$ROUTE_NAMED_PREFIX$' => '',
            '$ROUTE_PREFIX$' => '',
            '$RAW_ROUTE_PREFIX$' => '',
        ];

        if (!empty($this->prefixes['route'])) {
            $dynamicVars['$ROUTE_NAMED_PREFIX$'] = $this->prefixes['route'] . '.';
            $dynamicVars['$ROUTE_PREFIX$'] = str_replace('.', '/', $this->prefixes['route']) . '/';
            $dynamicVars['$RAW_ROUTE_PREFIX$'] = $this->prefixes['route'];
        }

        foreach ($dynamicVars as $var => $value) {
            $commandData->addDynamicVariable($var, $value);
        }

        return $commandData;
    }

    public function overrideOptionsFromJsonFile($jsonData)
    {
        foreach (self::$availableOptions as $option) {
            if (isset($jsonData['options'][$option])) {
                $this->setOption($option, $jsonData['options'][$option]);
            }
        }

        // prepare prefixes than reload namespaces, paths and dynamic variables
        if (!empty($this->getOption('prefix'))) {
            $this->preparePrefixes();
            $this->loadPaths();
            $this->loadNamespaces($this->commandData);
            $this->loadDynamicVariables($this->commandData);
        }

        foreach (['tests'] as $addOn) {
            if (isset($jsonData['addOns'][$addOn])) {
                $this->addOns[$addOn] = $jsonData['addOns'][$addOn];
            }
        }
    }

    public function getOption($option)
    {
        return $this->options[$option] ?? false;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getAddOn($addOn)
    {
        return $this->addOns[$addOn] ?? false;
    }
}
