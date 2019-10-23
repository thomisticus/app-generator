<?php

namespace Thomisticus\Generator\Common;

use Illuminate\Support\Str;

class GeneratorConfig
{
    /* Namespace variables */
    public $nsApp;
    public $nsRepository;
    public $nsService;
    public $nsTrait;
    public $nsModel;
    public $nsModelExtend;

    public $nsApiController;
    public $nsApiRequest;

    public $nsRequest;
    public $nsRequestBase;
    public $nsController;
    public $nsBaseController;

    public $nsApiTests;
    public $nsRepositoryTests;
    public $nsTestTraits;
    public $nsTests;

    /* Path variables */
    public $pathRepository;
    public $pathService;
    public $pathModel;
    public $pathFactory;
    public $pathSeeder;
    public $pathDatabaseSeeder;

    public $pathApiController;
    public $pathApiRequest;
    public $pathApiRoutes;
    public $pathApiTests;
    public $pathApiTestTraits;

    public $pathController;
    public $pathRequest;
    public $pathRoutes;

    /* Model Names */
    public $mName;
    public $mPlural;
    public $mCamel;
    public $mCamelPlural;
    public $mSnake;
    public $mSnakePlural;
    public $mDashed;
    public $mDashedPlural;
    public $mSlash;
    public $mSlashPlural;
    public $mHuman;
    public $mHumanPlural;

    public $forceMigrate;

    /* Generator Options */
    public $options;

    /* Prefixes */
    public $prefixes;

    private $commandData;

    /* Command Options */
    public static $availableOptions = [
        'fieldsFile',
        'jsonFromGUI',
        'tableName',
        'fromTable',
        'ignoreFields',
        'jsonResponse',
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

    public $tableName;

    /**
     * @var bool
     */
    public $jsonResponse;

    /** @var string */
    protected $primaryName;

    /* Generator AddOns */
    public $addOns;

    public function init(CommandData &$commandData, $options = null)
    {
        if (!empty($options)) {
            self::$availableOptions = $options;
        }

        $this->mName = $commandData->modelName;

        $this->prepareAddOns();
        $this->prepareOptions($commandData);
        $this->prepareModelNames();
        $this->preparePrefixes();
        $this->loadPaths();
        $this->prepareTableName();
        $this->prepareJsonResponseParam();
        $this->preparePrimaryName();
        $this->loadNamespaces($commandData);
        $commandData = $this->loadDynamicVariables($commandData);
        $this->commandData = &$commandData;
    }

    public function loadNamespaces(CommandData &$commandData)
    {
        $prefix = $this->prefixes['ns'];

        if (!empty($prefix)) {
            $prefix = '\\' . $prefix;
        }

        $this->nsApp = $commandData->commandObj->getLaravel()->getNamespace();
        $this->nsApp = substr($this->nsApp, 0, strlen($this->nsApp) - 1);
        $this->nsRepository = config('app-generator.namespace.repository', 'App\Repositories') . $prefix;
        $this->nsService = config('app-generator.namespace.service', 'App\Services') . $prefix;
        $this->nsTrait = config('app-generator.namespace.trait', 'App\Traits') . $prefix;
        $this->nsModel = config('app-generator.namespace.model', 'App\Models') . $prefix;
        if (config('app-generator.ignore_model_prefix', false)) {
            $this->nsModel = config('app-generator.namespace.model', 'App\Models');
        }
        $this->nsModelExtend = config(
            'thomisticus.model_extend_class',
            'Illuminate\Database\Eloquent\Model'
        );

        $this->nsApiController = config(
                'thomisticus.namespace.api_controller',
                'App\Http\Controllers\API'
            ) . $prefix;
        $this->nsApiRequest = config(
                'thomisticus.namespace.api_request',
                'App\Http\Requests\API'
            ) . $prefix;

        $this->nsRequest = config('app-generator.namespace.request', 'App\Http\Requests') . $prefix;
        $this->nsRequestBase = config('app-generator.namespace.request', 'App\Http\Requests');
        $this->nsBaseController = config('app-generator.namespace.controller', 'App\Http\Controllers');
        $this->nsController = config(
                'thomisticus.namespace.controller',
                'App\Http\Controllers'
            ) . $prefix;

        $this->nsApiTests = config('app-generator.namespace.api_test', 'Tests\APIs');
        $this->nsRepositoryTests = config('app-generator.namespace.repository_test', 'Tests\Repositories');
        $this->nsTests = config('app-generator.namespace.tests', 'Tests');
    }

    public function loadPaths()
    {
        $prefix = $this->prefixes['path'];

        if (!empty($prefix)) {
            $prefix .= '/';
        }

        $this->pathRepository = config(
                'thomisticus.path.repository',
                app_path('Repositories/')
            ) . $prefix;

        $this->pathService = config(
                'thomisticus.path.service',
                app_path('Services/')
            ) . $prefix;

        $this->pathModel = config('app-generator.path.model', app_path('Models/')) . $prefix;
        if (config('app-generator.ignore_model_prefix', false)) {
            $this->pathModel = config('app-generator.path.model', app_path('Models/'));
        }

        $this->pathApiController = config(
                'thomisticus.path.api_controller',
                app_path('Http/Controllers/API/')
            ) . $prefix;

        $this->pathApiRequest = config(
                'thomisticus.path.api_request',
                app_path('Http/Requests/API/')
            ) . $prefix;

        $this->pathApiRoutes = config('app-generator.path.api_routes', base_path('routes/api.php'));

        $this->pathApiTests = config('app-generator.path.api_test', base_path('tests/'));

        $this->pathApiTestTraits = config('app-generator.path.test_trait', base_path('tests/traits/'));

        $this->pathController = config(
                'thomisticus.path.controller',
                app_path('Http/Controllers/')
            ) . $prefix;

        $this->pathRequest = config('app-generator.path.request', app_path('Http/Requests/')) . $prefix;

        $this->pathRoutes = config('app-generator.path.routes', base_path('routes/web.php'));

        $this->pathFactory = config('app-generator.path.factory', database_path('factories/'));

        $this->pathSeeder = config('app-generator.path.seeder', database_path('seeds/'));
        $this->pathDatabaseSeeder = config(
            'thomisticus.path.database_seeder',
            database_path('seeds/DatabaseSeeder.php')
        );
    }

    public function loadDynamicVariables(CommandData &$commandData)
    {
        $commandData->addDynamicVariable('$NAMESPACE_APP$', $this->nsApp);
        $commandData->addDynamicVariable('$NAMESPACE_REPOSITORY$', $this->nsRepository);
        $commandData->addDynamicVariable('$NAMESPACE_SERVICE$', $this->nsService);
        $commandData->addDynamicVariable('$NAMESPACE_TRAIT$', $this->nsTrait);
        $commandData->addDynamicVariable('$NAMESPACE_MODEL$', $this->nsModel);
        $commandData->addDynamicVariable('$NAMESPACE_MODEL_EXTEND$', $this->nsModelExtend);

        $commandData->addDynamicVariable('$NAMESPACE_API_CONTROLLER$', $this->nsApiController);
        $commandData->addDynamicVariable('$NAMESPACE_API_REQUEST$', $this->nsApiRequest);

        $commandData->addDynamicVariable('$NAMESPACE_BASE_CONTROLLER$', $this->nsBaseController);
        $commandData->addDynamicVariable('$NAMESPACE_CONTROLLER$', $this->nsController);
        $commandData->addDynamicVariable('$NAMESPACE_REQUEST$', $this->nsRequest);
        $commandData->addDynamicVariable('$NAMESPACE_REQUEST_BASE$', $this->nsRequestBase);

        $commandData->addDynamicVariable('$NAMESPACE_API_TESTS$', $this->nsApiTests);
        $commandData->addDynamicVariable('$NAMESPACE_REPOSITORIES_TESTS$', $this->nsRepositoryTests);
        $commandData->addDynamicVariable('$NAMESPACE_TESTS$', $this->nsTests);

        $commandData->addDynamicVariable('$TABLE_NAME$', $this->tableName);
        $commandData->addDynamicVariable('$TABLE_NAME_TITLE$', Str::studly($this->tableName));
        $commandData->addDynamicVariable('$PRIMARY_KEY_NAME$', $this->primaryName);

        $commandData->addDynamicVariable('$MODEL_NAME$', $this->mName);
        $commandData->addDynamicVariable('$MODEL_NAME_CAMEL$', $this->mCamel);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL$', $this->mPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_CAMEL$', $this->mCamelPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_SNAKE$', $this->mSnake);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_SNAKE$', $this->mSnakePlural);
        $commandData->addDynamicVariable('$MODEL_NAME_DASHED$', $this->mDashed);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_DASHED$', $this->mDashedPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_SLASH$', $this->mSlash);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_SLASH$', $this->mSlashPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_HUMAN$', $this->mHuman);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_HUMAN$', $this->mHumanPlural);
        $commandData->addDynamicVariable('$FILES$', '');

        if (!empty($this->prefixes['route'])) {
            $commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', $this->prefixes['route'] . '.');
            $commandData->addDynamicVariable('$ROUTE_PREFIX$', str_replace('.', '/', $this->prefixes['route']) . '/');
            $commandData->addDynamicVariable('$RAW_ROUTE_PREFIX$', $this->prefixes['route']);
        } else {
            $commandData->addDynamicVariable('$ROUTE_PREFIX$', '');
            $commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', '');
        }

        if (!empty($this->prefixes['ns'])) {
            $commandData->addDynamicVariable('$PATH_PREFIX$', $this->prefixes['ns'] . '\\');
        } else {
            $commandData->addDynamicVariable('$PATH_PREFIX$', '');
        }

        if (!empty($this->prefixes['public'])) {
            $commandData->addDynamicVariable('$PUBLIC_PREFIX$', $this->prefixes['public']);
        } else {
            $commandData->addDynamicVariable('$PUBLIC_PREFIX$', '');
        }

        $commandData->addDynamicVariable(
            '$API_PREFIX$',
            config('app-generator.api_prefix', 'api')
        );

        $commandData->addDynamicVariable(
            '$API_VERSION$',
            config('app-generator.api_version', 'v1')
        );

        return $commandData;
    }

    public function prepareTableName()
    {
        if ($this->getOption('tableName')) {
            $this->tableName = $this->getOption('tableName');
        } else {
            $this->tableName = $this->mSnakePlural;
        }
    }

    public function prepareJsonResponseParam()
    {
        $jsonResponse = $this->getOption('jsonResponse') === 'false' ? false : true;
        $this->setOption('jsonResponse', $jsonResponse);
    }

    public function preparePrimaryName()
    {
        if ($this->getOption('primary')) {
            $this->primaryName = $this->getOption('primary');
        } else {
            $this->primaryName = 'id';
        }
    }

    public function prepareModelNames()
    {
        if ($this->getOption('plural')) {
            $this->mPlural = $this->getOption('plural');
        } else {
            $this->mPlural = Str::plural($this->mName);
        }
        $this->mCamel = Str::camel($this->mName);
        $this->mCamelPlural = Str::camel($this->mPlural);
        $this->mSnake = Str::snake($this->mName);
        $this->mSnakePlural = Str::snake($this->mPlural);
        $this->mDashed = str_replace('_', '-', Str::snake($this->mSnake));
        $this->mDashedPlural = str_replace('_', '-', Str::snake($this->mSnakePlural));
        $this->mSlash = str_replace('_', '/', Str::snake($this->mSnake));
        $this->mSlashPlural = str_replace('_', '/', Str::snake($this->mSnakePlural));
        $this->mHuman = Str::title(str_replace('_', ' ', Str::snake($this->mSnake)));
        $this->mHumanPlural = Str::title(str_replace('_', ' ', Str::snake($this->mSnakePlural)));
    }

    public function prepareOptions(CommandData &$commandData)
    {
        foreach (self::$availableOptions as $option) {
            $this->options[$option] = $commandData->commandObj->option($option);
        }

        if (isset($options['fromTable']) && $this->options['fromTable']) {
            if (!$this->options['tableName']) {
                $commandData->commandError('tableName required with fromTable option.');
                exit;
            }
        }

        if (empty($this->options['save'])) {
            $this->options['save'] = config('app-generator.options.save_schema_file', true);
        }

        $this->options['softDelete'] = config('app-generator.options.softDelete', false);
        $this->options['repositoryPattern'] = config('app-generator.options.repository_pattern', false);
        $this->options['seeder'] = config('app-generator.options.generate_seeder', false);
        if (!empty($this->options['skip'])) {
            $this->options['skip'] = array_map('trim', explode(',', $this->options['skip']));
        }
    }

    public function preparePrefixes()
    {
        $this->prefixes['route'] = explode('/', config('app-generator.prefixes.route', ''));
        $this->prefixes['path'] = explode('/', config('app-generator.prefixes.path', ''));
        $this->prefixes['public'] = explode('/', config('app-generator.prefixes.public', ''));

        if ($this->getOption('prefix')) {
            $multiplePrefixes = explode(',', $this->getOption('prefix'));

            $this->prefixes['route'] = array_merge($this->prefixes['route'], $multiplePrefixes);
            $this->prefixes['path'] = array_merge($this->prefixes['path'], $multiplePrefixes);
            $this->prefixes['public'] = array_merge($this->prefixes['public'], $multiplePrefixes);
        }

        $this->prefixes['route'] = array_diff($this->prefixes['route'], ['']);
        $this->prefixes['path'] = array_diff($this->prefixes['path'], ['']);
        $this->prefixes['public'] = array_diff($this->prefixes['public'], ['']);

        $routePrefix = '';

        foreach ($this->prefixes['route'] as $singlePrefix) {
            $routePrefix .= Str::camel($singlePrefix) . '.';
        }

        if (!empty($routePrefix)) {
            $routePrefix = substr($routePrefix, 0, strlen($routePrefix) - 1);
        }

        $this->prefixes['route'] = $routePrefix;

        $nsPrefix = '';

        foreach ($this->prefixes['path'] as $singlePrefix) {
            $nsPrefix .= Str::title($singlePrefix) . '\\';
        }

        if (!empty($nsPrefix)) {
            $nsPrefix = substr($nsPrefix, 0, strlen($nsPrefix) - 1);
        }

        $this->prefixes['ns'] = $nsPrefix;

        $pathPrefix = '';

        foreach ($this->prefixes['path'] as $singlePrefix) {
            $pathPrefix .= Str::title($singlePrefix) . '/';
        }

        if (!empty($pathPrefix)) {
            $pathPrefix = substr($pathPrefix, 0, strlen($pathPrefix) - 1);
        }

        $this->prefixes['path'] = $pathPrefix;

        $publicPrefix = '';

        foreach ($this->prefixes['public'] as $singlePrefix) {
            $publicPrefix .= Str::camel($singlePrefix) . '/';
        }

        if (!empty($publicPrefix)) {
            $publicPrefix = substr($publicPrefix, 0, strlen($publicPrefix) - 1);
        }

        $this->prefixes['public'] = $publicPrefix;
    }

    public function overrideOptionsFromJsonFile($jsonData)
    {
        $options = self::$availableOptions;

        foreach ($options as $option) {
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

        $addOns = ['tests'];

        foreach ($addOns as $addOn) {
            if (isset($jsonData['addOns'][$addOn])) {
                $this->addOns[$addOn] = $jsonData['addOns'][$addOn];
            }
        }
    }

    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return false;
    }

    public function getAddOn($addOn)
    {
        if (isset($this->addOns[$addOn])) {
            return $this->addOns[$addOn];
        }

        return false;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function prepareAddOns()
    {
        $this->addOns['tests'] = config('app-generator.add_on.tests', false);
    }
}
