<?php

namespace Thomisticus\Generator\Generators;

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
     * @var Collection
     */
    private $groupedRoutes;

    /**
     * PostmanGenerator constructor.
     * @param PostmanGeneratorCommand|Command $commandObj
     */
    public function __construct($commandObj)
    {
        $this->commandObj = $commandObj;
        $this->docConfig = new DocumentationConfig(config('apidoc'));
        $this->baseUrl = $this->docConfig->get('base_url') ?? config('app.url');
    }

    /**
     * Generate postman collection
     */
    public function generate()
    {
        $routeMatcher = new RouteMatcher($this->docConfig->get('routes'), $this->docConfig->get('router'));
        $routes = $routeMatcher->getRoutes();

        $generator = new Generator($this->docConfig);
        $parsedRoutes = $this->processRoutes($generator, $routes);

        $this->groupedRoutes = collect($parsedRoutes)
            ->groupBy('metadata.groupName')
            ->sortBy(static function ($group) {
                /* @var $group Collection */
                return $group->first()['metadata']['groupName'];
            }, SORT_NATURAL);

        $collection = $this->getCollectionTextContent();
        $collectionPath = "resources/collection.json";
        file_put_contents($collectionPath, $collection);

        $this->commandObj->info("Postman collection file written in: {$collectionPath}");
    }

    /**
     * Retrieve the routes group name, based on Controller name.
     * @param Route $route
     * @return string
     */
    private function getRoutesGroupName(Route $route)
    {
        $controllerName = class_basename($route->getController());
        return str_replace('Controller', '', $controllerName);
    }

    /**
     * @param \Mpociot\ApiDoc\Extracting\Generator $generator
     * @param array $routes
     *
     * @return array
     */
    private function processRoutes(Generator $generator, array $routes)
    {
        $parsedRoutes = [];
        $processedRoutesCount = 0;
        foreach ($routes as $routeItem) {
            $route = $routeItem['route'];
            if (!$this->isValidRoute($route) || !$this->isRouteVisibleForDocumentation($route->getAction())) {
                continue;
            }

            try {
                $parsedRoute = $generator->processRoute($route, $routeItem['apply'] ?? []);
                $parsedRoute['metadata']['groupName'] = $this->getRoutesGroupName($route);

                $parsedRoutes[] = $parsedRoute;
                $processedRoutesCount++;
            } catch (\Exception $e) {
                /** @var Route $route */
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
        if (!$middleware || $middleware[0] !== 'api') {
            return false;
        }

        $action = Utils::getRouteClassAndMethodNames($route->getAction());
        if (is_array($action)) {
            $action = implode('@', $action);
        }

        return !is_callable($action) && !is_null($action);
    }

    /**
     * @param array $action
     *
     * @return bool
     * @throws ReflectionException
     */
    private function isRouteVisibleForDocumentation(array $action)
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
     * Creates the json text content for postman collection
     *
     * @return false|string
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
                'name' => config('apidoc.postman.name') ?: config('app.name') . ' API',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => config('apidoc.postman.description') ?: '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
            ],
            'item' => $this->groupedRoutes->map(function ($routes, $groupName) {
                return [
                    'name' => $groupName,
                    'description' => '',
                    'item' => $routes->map(function ($route) {
                        $mode = 'raw';

                        return [
                            'name' => $route['metadata']['title'] != '' ? $route['metadata']['title'] : url($route['uri']),
                            'request' => [
                                'url' => url($route['uri']) . (collect($route['queryParameters'])->isEmpty()
                                        ? ''
                                        : ('?' . implode('&',
                                                collect($route['queryParameters'])->map(function ($parameter, $key) {
                                                    return urlencode($key) . '=' . urlencode($parameter['value'] ?? '');
                                                })->all()))),
                                'method' => $route['methods'][0],
                                'header' => collect($route['headers'])
                                    ->union([
                                        'Accept' => 'application/json',
                                    ])
                                    ->map(function ($value, $header) {
                                        return [
                                            'key' => $header,
                                            'value' => $value,
                                        ];
                                    })
                                    ->values()->all(),
                                'body' => [
                                    'mode' => $mode,
                                    $mode => json_encode($route['cleanBodyParameters'], JSON_PRETTY_PRINT),
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
