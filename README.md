# Yurly

[![Build Status](https://travis-ci.org/shaggy8871/yurly.svg?branch=master)](https://travis-ci.org/shaggy8871/yurly)

Yurly is a lightweight web MVC routing library for PHP 7. It's easy to get started, requires almost zero configuration, and can run within existing projects without a major rewrite.

It also supports a multi-site implementation right out of the box.

Installation:

In composer.json:
```
"require": {
    "shaggy8871/yurly": "^2.0"
}
```

Then run:
```
composer install
```

Example public/index.php file

```php
<?php
include_once "../vendor/autoload.php";

use Yurly\Core\{Project, Init};

/**
 * Project($hostname, $namespace, $pathToNamespace)
 */
$projects = [
    new Project($_SERVER['HTTP_HOST'], 'Myapp', './src'),
];

$app = new Init($projects);

$app->onRouteNotFound(function(array \$data) {
    // Handle 404 errors here
});

// Start 'em up
$app->run();

```

## Basic Routing

By default, routes are determined based on the controller class name and method name.

| URL | Routes To | Notes |
|-----|-----------|-------|
| `/` | `Index/routeDefault` |
| `/about` | `About/routeDefault` or `Index/routeAbout` | Yurly will try both in order |
| `/about/our-story` | `About/routeOur_Story` | "-" is auto-converted to "_" |

Controllers must extend the `Yurly\Core\Controller` class, and must contain at least one method name prefixed with the word `route`.

> `Request` and `Response` classes can be injected into any route method. The first `Response` class found will be used to render any value returned from the `route*` method. If nothing is returned, no content will be output.

```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\Get;
use Yurly\Inject\Response\Twig;

class Index extends Controller
{

    /**
     * This is the home page
     */
    public function routeDefault(Get $request, Twig $response): array
    {

        return [
            'title' => 'Welcome to Yurly',
            'content' => 'You\'re on the home page. You can customize this view in Myapp/Views/Index/default.html.twig.'
        ];

    }

    /**
     * This is an example about us page
     */
    public function routeAbout(Get $request, Twig $response): array
    {

        return [
            'title' => 'About Us',
            'content' => 'You can customize this page in <Yourapp>/Views/Index/about.html.twig.'
        ];

    }

    /**
     * This is an example route with JSON response
     */
    public function routeJson(Get $request, Json $response): array
    {

        return [
            'title' => 'JSON',
            'content' => 'This will be output in JSON format',
            'params' => $request->toArray(), // be aware - unsanitised! 
        ];

    }

}

```

## Route Parameters

Route parameters can be specified using a `@canonical` docblock statement above the route.

```php
<?php

use Yurly\Inject\Request\RouteParams;
use Yurly\Inject\Response\Json;

class Example extends Controller
{

    /**
     * @canonical /example/:requiredParam(/:optionalParam)
     */
    public function routeDefault(RouteParams $request, Json $response)
    {

        return $request->toArray();

        /**
         *  You can also access route parameters via:
         *  - $request->requiredParam
         *  - $request->optionalParam
         */

    }

}

```

In the example above, calling /example/hello/world will return a JSON response as follows:

```json
{
    "requiredParam": "hello",
    "optionalParam": "world"
}
```

## Accepting Multiple Request Types

The generic `Request` class has helper functions that can be used to extract multiple request types.

```php
<?php

use Yurly\Inject\Request\Request;
use Yurly\Inject\Response\Json;

class Example extends Controller
{

    public function routeDefault(Request $request, Json $response)
    {

        // GET parameters
        $getParams = $request->get();

        // POST parameters
        $postParameters = $request->post();

        /**
         *  You can access get/post parameters via:
         *  - $getParams->paramName
         *  - $postParams->paramName
         */

    }

}

```

## Middleware

You can have Yurly call a method before each route to check if it should proceed. The `@before` docblock can be used to control or restrict the flow as needed.

Return an alternative route in the format `Controller::method` to have it render content from another controller/route method.

```php
<?php

namespace Myapp\Middleware;

use Frame\Core\{Url, Context};

trait Auth
{

    public function isLoggedIn(Url $url, Context $context)
    {

        $caller = $context->getCaller();
        $annotations = $caller->getAnnotations();

        // The @role docblock isn't required, it's a suggestion
        $roles = [];
        if (isset($annotations['role'])) {
            $roles = explode(', ', $annotations['role']);
        }

        if (!$this->authenticate($roles)) {
            return 'User::routeLogout';
        }

    }

    private function authenticate(array $roles): bool
    {

        // Add your auth code here

    }

}
```
```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\Get;
use Yurly\Inject\Response\Twig;
use Myapp\Middleware\Auth;

class Admin extends Controller
{

    use Auth;

    /**
     * @before isLoggedIn
     * @role admin
     */
    public function routeDefault(Get $request, Twig $response): array
    {

        return [
            'message' => 'Welcome!'
        ];

    }

}

```

## Custom Request/Response Classes

Custom Request and Response classes may be created to supply additional functionality not already supplied by the built-in classes. Examples include additional input sources, improved input sanitisation and custom models.

```php
<?php

namespace Myapp\Inject\Request;

use Yurly\Core\Context;
use Yurly\Inject\Request\Request;

class FilteredRequest extends Request
{

    public function __construct(Context $context)
    {

        parent::__construct($context);

        // Filter inputs
        foreach($this->get() as &$param) {
            $param = filter_var($param, FILTER_SANITIZE_STRING);
        }
        foreach($this->post() as &$param) {
            $param = filter_var($param, FILTER_SANITIZE_STRING);
        }

    }

}
```
```php
<?php

namespace Myapp\Controllers;

use Myapp\Inject\Request\FilteredRequest;
use Yurly\Core\Controller;
use Yurly\Inject\Response\Json;

class Rest extends Controller
{

    public function routeDefault(FilteredRequest $request, Json $response): array
    {

        return [
            'filteredInputs' => $request->get()->toArray() // now filtered
        ];

    }

}

```

## Custom Route Resolvers

If you have routes that don't follow the controller/method approach, it's easy to create a custom route resolver class that can handle custom routing.

Create a class called `RouteResolver` at the base of your project directory, and ensure it `implements RouteResolverInterface`. It must contain one method called `resolve` that returns a route in the format `Controller::method`. Any other return value will be ignored.

```php
namespace Myapp;

use Yurly\Core\{Project, Url};
use Yurly\Core\Interfaces\RouteResolverInterface;
use Yurly\Core\Utils\RegExp;

class RouteResolver implements RouteResolverInterface
{

    public function resolve(Project $project, Url $url)
    {

        $routes = [
            [
                'match' => new RegExp('/^\/[a-z0-9]+\/product\/[a-z0-9]+\/?$/'),
                'route' => 'Products::routeSearch',
            ],
            //... add your match/routes here
        ];

        foreach($routes as $route) {
            if ($route['match']->matches($url->requestUri)) {
                return $route['route'];
            }
        }

    }

}
```

## Multi-site Setup

In composer.json, add a `psr-4` autoload for each unique namespace, for example:

```json

{
    "name": "example",
    "require": {
        "php": ">=7.2.0",
        "shaggy8871/yurly": "^2.0",
        "twig/twig": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8"
    },
    "autoload": {
        "psr-4": {
            "Site1\\": "./src/Site1/",
            "Site2\\": "./src/Site2/"
        }
    }
}

```

In public/index.php, enter a project row for each unique domain:

```php
<?php
include_once "../vendor/autoload.php";

use Yurly\Core\{Project, Init};

$projects = [
    new Project('www.site1.com', 'Site1', './src'),
    new Project('www.site1.com', 'Site2', './src'),
];

$app = new Init($projects);

// Start 'em up
$app->run();

```

Create a `Controllers` directory within both `/src/Site1` and `/src/Site2` and add your Index.php controller class. You may also create `Models`, `Views` and any other directories as may be required.

If you need to support multiple hosts for a project, you can either pass in an array of hosts, or use a `RegExp` helper class as follows:

```php
// Array of hosts per project:
$projects = [
    new Project(['www.site1.com', 'dev.site1.com'], 'Site1', './src'),
    new Project(['www.site2.com', 'dev.site2.com'], 'Site2', './src'),
];
```

or

```php
use Yurly\Core\Utils\RegExp;

// RegExp helper class:
$projects = [
    new Project(new RegExp('/^.*\.site1\.com$/'), 'Site1', './src'),
    new Project(new RegExp('/^.*\.site2\.com$/'), 'Site2', './src'),
];
```

## Using `ymake` Helper

Yurly ships with a helper application called `ymake`. You can use the helper to create a project, set of controller, model and view files, or an index.php file.

1. Add an autoload namespace to composer.json, for example:

```json
    "autoload": {
        "psr-4": {
            "Site1\\": "./src/Site1/",
            "Site2\\": "./src/Site2/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\Site1\\": "./tests/Site1/",
            "Tests\\Site2\\": "./tests/Site2/"
        }
    }
```

2. Run the appropriate command to generate scripts:

| Command | Creates | Notes |
|-----|-----------|-------|
| `vendor/bin/ymake project` | A full project, including an Index controller |
| `vendor/bin/ymake controller` | A controller, model and view | Project must exist first |
| `vendor/bin/ymake index` | An index.php file |
| `vendor/bin/ymake test` | A unit test class | Requires `autoload-dev` in composer.json

You will be prompted for further details based on the command used.

## Unit testing

Yurly extends PHPUnit's TestCase class with additional methods to help with testing of routes. Here's a simple example:

```php
<?php

namespace Tests\Controllers;

use Yurly\Test\TestCase;

class ExampleTest extends TestCase
{

    public function testRoute()
    {

        $response = $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/')
            ->getRouteResponse();

        $this->assertEquals($response, ['message' => 'Welcome!']);

    }

}
```

If you prefer to capture the full route response output, just call the route as follows:

```php
<?php

namespace Tests\Controllers;

use Yurly\Test\TestCase;

class ExampleTest extends TestCase
{

    public function testRoute()
    {

        $this->expectOutputString('<h1>Welcome to Yurly!</h1>');

        $response = $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/')
            ->callRoute();

    }

}
```

You can mock request classes in order to test your controllers with different inputs.

> The class type declared in the route method cannot be changed.

```php
<?php

namespace Tests\Controllers;

use Yurly\Test\TestCase;
use Yurly\Inject\Request\Get;

class ExampleTest extends TestCase
{

    public function testRouteWithRequestMock()
    {

        $mockRequest = $this->getRequestMock(Get::class, function($self) {
            $self->setProps(['hello' => 'World']);
        });

        $response = $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/')
            ->getRouteResponse([
                Get::class => $mockRequest
            ]);

        $this->assertEquals($response, ['message' => 'World']);

    }

}
```

You can mock the response class as well, and capture the output before it renders.

> You cannot pass a mock Request class to `getRouteResponse` as it already uses one to capture the output. Instead, use the `callRouteWithMocks` method.

```php
<?php

namespace Tests\Controllers;

use Yurly\Test\TestCase;
use Yurly\Inject\Response\Twig;

class ExampleTest extends TestCase
{

    public function testRouteWithResponseMock()
    {

        $mockResponse = $this->getResponseMock(Twig::class, function($params) {
            $this->assertEquals($params, ['message' => 'Welcome!']);
        });

        $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/')
            ->callRouteWithMocks([
                Twig::class => $mockResponse
            ]);

    }

}
```