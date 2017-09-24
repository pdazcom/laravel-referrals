# Laravel package boilerplate
A simple boilerplate to create new laravel packages.

## Installation
Before to start you'll need to clone/download this package locally and then run from the terminal
```bash
$ composer install
```

This will install all the dependencies

## Composer.json

Basic structure:

```json
{
    "name": "namespace/package-name",
    "description": "Package description",
    "type": "library",
    "version": "1.0.0-dev",
    "license": "MIT",
    "require": {
        "laravel/framework": "^5.4.29"
    },
    "require-dev": {
        "vlucas/phpdotenv": "^2.4",
        "orchestra/testbench": "^3.4"
    },
    "autoload": {
        "psr-4": {
            "NamespaceHolder\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "NamespaceHolder\\Tests\\": "tests/"
        }
    }
}
```

## Version
According to the composer docs the [version](https://getcomposer.org/doc/04-schema.md#version):

>must follow the format of X.Y.Z or vX.Y.Z with an optional suffix of
>-dev, -patch (-p), -alpha (-a), -beta (-b) or -RC. The patch, alpha, beta and
>RC suffixes can also be followed by a number.
>Examples:
> * 1.0.0
> * 1.0.2
> * 0.1.0
> * 0.2.5
> * 1.0.0-dev
> * 1.0.0-alpha3
> * 1.0.0-beta2
> * 1.0.0-RC5
> * v2.0.4-p1

## Licence
Is recommended to include a `.LICENCE` file to every new project. If you are working in a open source project, then you can pick one from the `extra` folder, move it to the root folder and rename the file to `.LICENCE`.

Here you can find more info about how to [How to Choose an open source licence](https://choosealicense.com/)

## Testing
This boilerplate uses orchestral/testbench](https://github.com/orchestral/testbench) which is a "Laravel Testing Helper for Packages Development".

After install the dependencies you can run all the tests by excecuting the follow command:

```bash
$ vendor/bin/phpunit
```

The output should look similar to this:

```bash
.                                                                  1 / 1 (100%)

Time: 84 ms, Memory: 12.00MB

OK (1 test, 1 assertion)


```

All the test files should be inside the `tests/` directory. Here is an example:

```php

<?php

namespace NamespaceHolder\Tests\Unit;

use NamespaceHolder\Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function example_test_method()
    {
        $this->assertTrue(true);
    }
}

```

## Installing as a dependency on a laravel project
Is very likely you'll need to install this package locally to test the integration. You can do so by adding the follow to the `composer.json` file in your laravel project.

```json
    "repositories": [
        {
            "type": "path",
            "url": "path/to/package/folder"
        }
    ],
```

Then, in your laravel project root path you can just run:

```bash
$ composer require namespace/package-name
```

## Configuration
Since we are trying to building a new laravel package, is a good idea to pull all the configuration files inside the `/config` folder to keep a laravel-like folder structure.

## Bootstrapping
Ideally you'll build this new package using [#TDD](https://en.wikipedia.org/wiki/Test-driven_development), so in order to load all the dependencies a bootstrap.php was added inside the tests directory with the escencial configuration.

```php
<?php

require __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('UTC');

```

## Service Provider
With laravel is really easy to integrate or install any package. Is recomended to use a service provider if you want to bind things into laravel's service container.
Here you can find more info about the [Laravel service providers](https://laravel.com/docs/5.4/packages#service-providers)

```php
<?php

namespace NamespaceHolder\Providers;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register()
    {
        //
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        // If you need to copy a config file to the laravel project
        $this->publishes([
            __DIR__.'/path/to/config/file.php' => config_path('file.php'),
        ]);
    }
}

```

## Laravel Package Auto discovering
This is a new feature added recently to the laravel framework, now you can just install this package thru composer and is going to be automatically registered in the laravel project. To do so you need to add this section in the package `composer.json` file:

```json
    "extra": {
        "laravel": {
            "providers": [
                "NamespaceHolder\\Providers\\PackageServiceProvider"
            ]
        }
    }
```

And you can also register multiple alias with:
```json
    "extra": {
        "laravel": {
            "providers": [
                "NamespaceHolder\\Providers\\PackageServiceProvider"
            ]
        },
        "aliases": {
            "Bar": "Foo\\Bar\\Facade"
        }
    }
```

## Git
A .gitignore file is included with the most common and basic setup
```
vendor/
composer.lock
phpunit.xml
node_modules/
.idea
```

## Make it yours!
You just need to edit your personal info in the `composer.json` file, and run a quick search into the package folder to change the `NamespaceHolder` string by your custom namespace ant that's it.

Have fun! 🎊

## Credits

Thanks to [Daniel Coulbourne](https://twitter.com/DCoulbourne) and [Matt Stauffer](https://twitter.com/stauffermatt). This package was inspired by their work on [tightenco/ziggy](https://github.com/tightenco/ziggy) a package to use Laravel routes in Javascript.