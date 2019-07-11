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

Example public/index.php file:
```php
<?php
include_once "../vendor/autoload.php";

use Yurly\Core\{Project, Init};

/**
 * Project($hostname, $namespace, $pathToNamespace, $debugMode)
 */
$projects = [
    new Project($_SERVER['HTTP_HOST'], 'Myapp', './src', Project::DEBUG_ON),
];

$app = new Init($projects);

$app->onRouteNotFound(function(array $data) {
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

> `Request` and `Response` classes can be injected into any route method. The first `Response` class found will be used to render any value returned from the `route*` method.

Example src/Controllers/Index.php file:
```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\Get;
use Yurly\Inject\Response\{Twig, Json};

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

namespace Myapp\Controllers;

use Yurly\Core\Controller;
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
    "requiredParam":"hello",
    "optionalParam":"world"
}
```

## Accepting Multiple Request Types

The generic `Request` class has helper functions that can be used to extract multiple request types.

```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
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

Yurly exposes options for middleware code to run before and after a route is called.

### @before

For each route, adding a `@before` docblock above the method declaration will run the designated methods before calling the route. This may, for instance, be used to point the user to an alternative route, or to look up additional metadata before the route code runs. Middleware may be specified as either the name of the method to call, or if outside the controller, in the form `Controller::method`. Multiple methods may be specified as a comma-delimited list and will be run in the order supplied.

To alter the route that gets rendered, middleware should return an alternative route as a string in the form `Controller::routeMethod`.

To run code before all routes in a controller are called, add a `beforeAllRoutes()` method to the controller. This will be run _before_ all `@before` docblock methods are called.

### @after

The `@after` docblock will call the designated class methods after the route has run. Each middleware method will receive a copy of the response, and may alter it before it renders to the browser. Multiple methods may be specified as a comma-delimited list and will be run in the order supplied.

To run middleware after all routes in a controller are called, add an `afterAllRoutes()` method to the controller. This code will be run _before_ all `@after` docblock methods are called.

src/Myapp/Middleware/Auth.php:
```php
<?php

namespace Myapp\Middleware;

use Yurly\Core\{Url, Context};

trait Auth
{

    public function isLoggedIn(Url $url, Context $context): ?string
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
        return null;
    }

    private function authenticate(array $roles): bool
    {
        // Add your auth code here
    }

}
```
src/Myapp/Controllers/Admin.php:
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

### MiddlewareState

Middleware methods are able to use a class called `MiddlewareState` to check the response from the previous middleware method, and to stop the router from calling subsequent middleware methods if need be.

> Calling `stop()` does not prevent the route from being called. It only prevents subsequent middleware methods from being called.

src/Myapp/Controllers/Admin.php:
```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Response\Twig;
use Yurly\Middleware\MiddlewareState;
use Myapp\Models\User;
use Myapp\Models\AdminPermissions;

class Admin extends Controller
{

    private $user;
    private $permissions;

    /**
     * @before isLoggedIn, hasPermission
     */
    public function routeDefault(Twig $response): array
    {
        return [
            'user' => $this->user,
            'permissions' => $this->permissions,
        ];
    }

    public function isLoggedIn(MiddlewareState $state): ?string
    {
        $this->user = User::getLoggedIn();
        if (!($this->user instanceof User)) {
            // Don't call hasPermission, go straight to login route
            $state->stop();
            return 'User::routeLogin';
        }
        return null;
    }

    public function hasPermission(MiddlewareState $state): ?string
    {
        $this->permissions = AdminPermissions::getPermissions($this->user);
        if (empty($this->permissions)) {
            // Go to access denied route
            $state->stop();
            return 'User::routeAccessDenied';
        }
        return null;
    }

}
```

## Custom Request/Response Classes

Custom Request and Response classes may be created to supply additional functionality not already supplied by the built-in classes. Examples include additional input sources, improved input sanitisation and output data mapping.

### Model Lookup and Data Mapper Example

src/Myapp/Models/User.php:
```php
<?php

namespace Myapp\Models;

class User
{

    public $fname;
    public $lname;
    //... more props

    // Example find method
    public static function findById(int $id): ?self
    {
        if ($id == 1) {
            $instance = new static();
            $instance->fname = 'First name';
            $instance->lname = 'Last name';
            return $instance;
        }
        return null;
    }

}
```
src/Myapp/Inject/Request/UserFinder.php:
```php
<?php

namespace Myapp\Inject\Request;

use Yurly\Inject\Request\RouteParams;
use Myapp\Models\User;

class UserFinder extends RouteParams
{

    public function find(): ?User
    {
        $userId = filter_var($this->id, FILTER_VALIDATE_INT);
        if ($userId) {
            return User::findById($userId);
        }
        return null;
    }

}
```
src/Myapp/Inject/Response/UserJsonDataMapper.php:
```php
<?php

namespace Myapp\Inject\Response;

use Yurly\Inject\Response\Json;
use Myapp\Models\User;

class UserJsonDataMapper extends Json
{

    /**
     * Render parameters with data mapping
     */
    public function render($params = null): void
    {
        if ($params instanceof User) {
            parent::render([
                'first_name' => $params->fname,
                'last_name'  => $params->lname,
            ]);
            return;
        }

        parent::render([
            'error' => 'User Not Found'
        ]);
    }

}
```
src/Myapp/Controllers/User.php:
```php
<?php

namespace Myapp\Controllers;

use Yurly\Core\Controller;
use Myapp\Inject\Request\UserFinder;
use Myapp\Inject\Response\UserJsonDataMapper;
use Myapp\Models\User as UserModel;

class User extends Controller
{

    /**
     * @canonical /user/:id
     */
    public function routeDefault(UserFinder $request, UserJsonDataMapper $response): ?UserModel
    {
        return $request->find();
    }

}
```

## Dependency Injected Parameters

Yurly does not include native support for dependency injection outside of Request and Response classes, but it's easy enough to add a PSR-11 compatible DI solution through composer. Here's an example using [https://github.com/PHP-DI/PHP-DI]:

composer.json
```
composer require php-di/php-di
```
src/Myapp/Config.php:
```php
<?php

namespace Myapp;

use Yurly\Core\Project;
use DI\Container;

class Config
{

    public function __construct(Project $project)
    {
        $container = new Container();
        $project->addContainer($container);
    }

}
```
src/Myapp/Controllers/Index.php:
```php
class Index extends Controller
{
    /**
     * Yurly\Core\Project must always be the first parameter
     */
    public function __construct(Project $project, \Myapp\Models\User $user)
    {
        // $user is instantiated and ready
        parent::__construct($project);
    }

    public function routeWithDI(\Myapp\Models\User $user)
    {
        // $user is instantiated and ready
    }
}
```

## Custom Route Resolvers

If you have routes that don't follow the controller/method approach, it's easy to create a custom route resolver class that can handle custom routing.

Create a class called `RouteResolver` at the base of your project directory, and ensure it `implements RouteResolverInterface`. It must contain one method called `resolve` that returns a route in the format `Controller::method`. Any other return value will be ignored.

```php
<?php

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
        $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/');

        $mockRequest = $this->getRequestMock(Get::class, function($self) {
            $self->setProps(['hello' => 'World']);
        });

        $response = $this
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
        $this
            ->setProjectNamespace('Myapp')
            ->setProjectPath('./src')
            ->setUrl('/');

        $mockResponse = $this->getResponseMock(Twig::class, function($params) {
            $this->assertEquals($params, ['message' => 'Welcome!']);
        });

        $this
            ->callRouteWithMocks([
                Twig::class => $mockResponse
            ]);

        $this->assertEquals($mockResponse->statusCode, 200);
    }

}
```