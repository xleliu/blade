# Blade

This is a view template engine which fetch from Laravel view template - Blade.

What's more important, it's independent without depend on Laravel Container or any others.


### Installation：

With Composer, you should just do it:

``` sh
composer require xiaoler/blade
```

If you want to use it without composer, you should add all the files in folder `src` to you projects,
them `require` them in you code.


### Usage：

``` php
<?php
$path = ['/view_path']; // you view file path, it's array.
$cachePath = '/cache_path'; // compiled file path.

$engine = new \Xiaoler\Blade\Engines\CompilerEngine($cachePath);
$finder = new \Xiaoler\Blade\FileViewFinder($path);
// if you view file extension is not php or blade.php, use this to add it.
$finder->addExtension('tpl');
// get an instance of factory.
$factory = new \Xiaoler\Blade\Factory($engine, $finder);
// render template file and echo it.
echo $factory->make('hello', ['a' => 1, 'b' => 2])->render();
```

Almostly you can use all features of blade. but some of exclusively features are removed.

You can't:

- use `@inject` `@can` `@cannot` in template file;
- define your own custom directives;
- add any events or middleaware;

Documentation: [http://laravel.com/docs/5.1/blade](http://laravel.com/docs/5.1/blade)

Thanks for Laravel and it's author, that is a great project.
