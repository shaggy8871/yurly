# Yurly

[![Build Status](https://travis-ci.org/shaggy8871/yurly.svg?branch=master)](https://travis-ci.org/shaggy8871/yurly)

Yurly is a lightweight web MVC routing library for PHP 7. It's easy to get started, requires almost zero configuration, and can run within existing projects without a major rewrite.

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

[Grab the sample Yurly application](https://github.com/shaggy8871/yurly-sample-app)

Example index.php file

```php
<?php
include_once "../vendor/autoload.php";

/*
 * Format:
 *    domain => [namespace, path, debugMode]
 */
$projects = [
    $_SERVER['HTTP_HOST'] => ['Myapp', 'src', true],
];

$app = new Yurly\Core\Init($projects);

// Start 'em up
$app->run();

```

## Controller example:

By default, routes are determined based on the controller class name and method name.

| URL | Routes To | Notes |
|-----|-----------|-------|
| `/` | `Index/routeDefault` |
| `/about` | `Index/routeAbout` or `About/routeIndex` | Yurly will try both |
| `/about/our-story` | `About/routeOur_Story` | "-" is auto-converted to "_" |

Controllers must extend the `Yurly\Core\Controller` class, and must contain at least one method name prefixed with the word `route`.

> `Request` and `Response` classes can be injected into any method. The first `Response` class found will be used to render any value returned from the `route*` method. If nothing is returned, no content will be output.

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

## Route parameters:

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

## Accepting multiple request types:

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

## Multi-site example:

In composer.json, add a `psr-4` autoload for each unique namespace, for example:

```json

{
    "name": "example",
    "require": {
        "php": ">=7.2.0",
        "shaggy8871/yurly": "^1.0",
        "twig/twig": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8"
    },
    "autoload": {
        "psr-4": {
            "Site1\\": "src/Site1/",
            "Site2\\": "src/Site2/"
        }
    }
}

```

In index.php, enter a project row for each unique domain:

```php
<?php
include_once "../vendor/autoload.php";

$projects = [
    'www.site1.com' => ['Site1', 'src', true],
    'www.site2.com' => ['Site2', 'src', true],
];

$app = new Yurly\Core\Init($projects);

// Start 'em up
$app->run();

```

create a `Controllers` directory within both `/src/Site1` and `/src/Site2` and add your Index.php controller class. You may also create `Models`, `Views` and any other directories as may be required.
