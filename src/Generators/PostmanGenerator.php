<?php

namespace Thomisticus\Generator\Generators;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Extracting\Generator;
use Mpociot\ApiDoc\Matching\RouteMatcher;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionException;
use Thomisticus\Generator\Commands\PostmanGeneratorCommand;
use Thomisticus\Generator\Utils\FileUtil;

class PostmanGenerator
{
    /**
     * @var PostmanGeneratorCommand|Command
     */
    private $commandObj;

    /**
     * @var DocumentationConfig
     */
    private $docConfig;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * Name of the postman collection json file
     * @var string
     */
    private $collectionFileName;

    /**
     * @var string
     */
    private $modelNamespace;

    /**
     * @var Collection
     */
    private $groupedRoutes;

    /**
     * Array with collection, model and factory paths
     * @var array
     */
    private $paths;

    /**
     * PostmanGenerator constructor.
     * @param PostmanGeneratorCommand|Command|null $commandObj
     */
    public function __construct($commandObj)
    {
        $this->commandObj = $commandObj;

        $config = array_merge(config('apidoc'), config('app-generator.postman'));
        $this->docConfig = new DocumentationConfig($config);

        $this->baseUrl = $this->docConfig->get('base_url') ?? config('app.url');
        $this->collectionFileName = config('app-generator.postman.file_name', 'collection.json');
        $this->modelNamespace = config('app-generator.namespace.model', 'App\Models');

        $this->paths = [
            'collection' => config('app-generator.path.postman', resource_path('docs/')),
            'model' => config('app-generator.path.model', app_path('Models/')),
            'factory' => config('app-generator.path.factory', database_path('factories/'))
        ];
    }

    /**
     * Generate postman collection
     * @return bool
     * @throws Exception
     */
    public function generate()
    {
        $routeMatcher = new RouteMatcher(config('app-generator.postman.routes'), 'laravel');
        $routes = $routeMatcher->getRoutes();

        $generator = new Generator($this->docConfig);
        $parsedRoutes = $this->processRoutes($generator, $routes);

        $this->groupedRoutes = collect($parsedRoutes)
            ->groupBy('metadata.groupName')
            ->sortBy(static function ($group) {
                /* @var $group Collection */
                return $group->first()['metadata']['groupName'];
            }, SORT_NATURAL);


        $collectionContent = $this->getCollectionTextContent();
        return FileUtil::createFile($this->paths['collection'], $this->collectionFileName, $collectionContent);
    }

    /**
     * Retrieve the routes group name, based on Controller name.
     *
     * @param Route $route
     * @return string
     */
    private function getRoutesGroupName(Route $route)
    {
        $controllerName = class_basename($route->getController());
        return str_replace('Controller', '', $controllerName);
    }

    /**
     * @param Generator $generator
     * @param array $routes
     * @return array
     */
    private function processRoutes(Generator $generator, array $routes)
    {
        $parsedRoutes = [];
        $processedRoutesCount = 0;
        foreach ($routes as $routeItem) {
            try {
                /** @var Route $route */
                $route = $routeItem['route'];
                if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route->getAction())) {
                    $parsedRoute = $generator->processRoute($route, $routeItem['apply'] ?? []);
                    $parsedRoute['metadata']['groupName'] = $this->getRoutesGroupName($route);

                    $parsedRoutes[] = $parsedRoute;
                    $processedRoutesCount++;
                }
            } catch (\Exception $e) {
                $messageFormat = '%s route: [%s] %s';
                $routeMethods = implode(',', $generator->getMethods($route));
                $routePath = $generator->getUri($route);

                $this->commandObj->warn(
                    sprintf($messageFormat, 'Skipping', $routeMethods, $routePath) . ' - ' . $e->getMessage()
                );
            }
        }

        $this->commandObj->info($processedRoutesCount . " routes processed.");

        return $parsedRoutes;
    }

    /**
     * Validate the route. It will just consider the routes with the api middleware
     * @param Route $route
     *
     * @return bool
     */
    private function isValidRoute(Route $route)
    {
        $middleware = $route->middleware();
        if (empty($middleware) || $middleware[0] !== 'api') {
            return false;
        }

        $action = Utils::getRouteClassAndMethodNames($route->getAction());
        if (is_array($action)) {
            $action = implode('@', $action);
        }

        return !is_callable($action) && !is_null($action);
    }

    /**
     * @param array|null $action
     * @return bool
     * @throws ReflectionException
     */
    private function isRouteVisibleForDocumentation($action)
    {
        list($class, $method) = Utils::getRouteClassAndMethodNames($action);
        $reflection = new ReflectionClass($class);

        if (!$reflection->hasMethod($method)) {
            return false;
        }

        $comment = $reflection->getMethod($method)->getDocComment();

        if ($comment) {
            $phpdoc = new DocBlock($comment);

            return collect($phpdoc->getTags())
                ->filter(function ($tag) {
                    return $tag->getName() === 'hideFromAPIDocumentation';
                })
                ->isEmpty();
        }

        return true;
    }

    /**
     * Retrieves the name of the request.
     *
     * @param array $route
     * @return string
     */
    private function getRequestName($route)
    {
        // return $route['metadata']['title'] != '' ? $route['metadata']['title'] : $route['uri'];
        return $route['uri'];
    }

    /**
     * Treats the request url
     *
     * @param array $route
     * @return string
     */
    private function getRequestUrl($route)
    {
        $url = url($route['uri']);

        if (empty($route['queryParameters'])) {
            return $url;
        }

        $parameters = collect($route['queryParameters'])->map(function ($parameter, $key) {
            return urlencode($key) . '=' . urlencode($parameter['value'] ?? '');
        })->all();

        return $url . '?' . implode('&', $parameters);
    }

    /**
     * Treats the Request headers
     *
     * @param array $route
     * @return array
     */
    private function getRequestHeader($route)
    {
        return collect($route['headers'])->union([
            'Accept' => 'application/json',
        ])
            ->map(function ($value, $key) {
                return compact('key', 'value');
            })
            ->values()->all();
    }

    /**
     * Generates the request body.
     * First it will try to find a factory with the same name of the route group (which is based in the controller name)
     * Eg: if the group is "User", it will try to create he body based on UserFactory.
     * Otherwise will get the cleanBodyParameters of each method.
     *
     * @param array $route
     * @return array|false|string
     */
    private function getRequestBody($route)
    {
        $body = '';
        try {
            $modelName = $route['metadata']['groupName'];
            $factoryName = $modelName . 'Factory';

            if (
                in_array($route['methods'][0], ['POST', 'PUT']) &&
                file_exists($this->paths['model'] . $modelName . '.php') &&
                file_exists($this->paths['factory'] . $factoryName . '.php')
            ) {
                $body = factory($this->modelNamespace . '\\' . $modelName)->make();
                $body = $body ? $body->toJson(JSON_PRETTY_PRINT) : [];
            }
        } catch (Exception $e) {
            $body = '';
        }

        if (empty($body) && !empty($route['cleanBodyParameters'])) {
            $body = json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT);
        }

        return $body;
    }

    /**
     * Generates the json text content for postman collection
     *
     * @return false|string
     * @throws Exception
     */
    public function getCollectionTextContent()
    {
        URL::forceRootUrl($this->baseUrl);
        if (Str::startsWith($this->baseUrl, 'https://')) {
            URL::forceScheme('https');
        }

        $collection = [
            'variables' => [],
            'info' => [
                'name' => config('app-generator.postman.name') ?: config('app.name') . ' API',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => config('app-generator.postman.description') ?: '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->groupedRoutes->map(function ($routes, $groupName) {
                return [
                    'name' => $groupName,
                    'description' => '',
                    'item' => $routes->map(function ($route) {
                        $mode = 'raw';
                        return [
                            'name' => $this->getRequestName($route),
                            'request' => [
                                'url' => $this->getRequestUrl($route),
                                'method' => $route['methods'][0],
                                'header' => $this->getRequestHeader($route),
                                'body' => [
                                    'mode' => $mode,
                                    $mode => $this->getRequestBody($route)
                                ],
                                'description' => $route['metadata']['description'],
                                'response' => [],
                            ],
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];

        return json_encode($collection, JSON_PRETTY_PRINT);
    }
}
