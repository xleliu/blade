# Blade
Use Laravel Blade templates without the full Laravel framework.（component & slot support）

### Installation

``` sh
composer require terranc/blade
```

### Usage

```php
<?php
$path = ['/view_path'];         // your view file path, it's an array
$cachePath = '/cache_path';     // compiled file path

$compiler = new \terranc\Blade\Compilers\BladeCompiler($cachePath);

// you can add a custom directive if you want
$compiler->directive('datetime', function($timestamp) {
    return preg_replace('/(\(\d+\))/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
});

$engine = new \terranc\Blade\Engines\CompilerEngine($compiler);
$finder = new \terranc\Blade\FileViewFinder($path);

// if your view file extension is not php or blade.php, use this to add it
$finder->addExtension('tpl');

// get an instance of factory
$factory = new \terranc\Blade\Factory($engine, $finder);

// render the template file and echo it
echo $factory->make('hello', ['a' => 1, 'b' => 2])->render();
```

You can enjoy almost all the features of blade with this extension.
However, remember that some of exclusive features are removed.

You can't:

- use `@inject` `@can` `@cannot` `@lang` in a template file
- add any events or middleawares

Documentation: [http://laravel.com/docs/5.4/blade](http://laravel.com/docs/5.4/blade)

Thanks for Laravel and it authors. That is a great project.


### For ThinkPHP
[https://github.com/terranc/think-blade](https://github.com/terranc/think-blade)

### Reference
[XiaoLer/blade](https://github.com/xiaoler/blade)
