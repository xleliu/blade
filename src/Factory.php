<?php

namespace Xiaoler\Blade;

use Xiaoler\Blade\Support\Arr;
use Xiaoler\Blade\Support\Str;
use InvalidArgumentException;
use Xiaoler\Blade\Contracts\Arrayable;
use Xiaoler\Blade\Engines\EngineResolver;

class Factory
{
    use Concerns\ManagesComponents,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks;

    /**
     * The engine implementation.
     *
     * @var \Xiaoler\Blade\Engines\EngineResolver
     */
    protected $engines;

    /**
     * The view finder implementation.
     *
     * @var \Xiaoler\Blade\ViewFinderInterface
     */
    protected $finder;

    /**
     * Data that should be available to all templates.
     *
     * @var array
     */
    protected $shared = [];

    /**
     * The extension to engine bindings.
     *
     * @var array
     */
    protected $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
        'css' => 'file',
    ];

    /**
     * The number of active rendering operations.
     *
     * @var int
     */
    protected $renderCount = 0;

    /**
     * Create a new view factory instance.
     *
     * @param  \Xiaoler\Blade\Engines\EngineResolver  $engines
     * @param  \Xiaoler\Blade\ViewFinderInterface  $finder
     * @return void
     */
    public function __construct(EngineResolver $engines, ViewFinderInterface $finder)
    {
        $this->finder = $finder;
        $this->engines = $engines;

        $this->share('__env', $this);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $path
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Xiaoler\Blade\View
     */
    public function file($path, $data = [], $mergeData = [])
    {
        $data = array_merge($mergeData, $this->parseData($data));

        return $this->viewInstance($path, $path, $data);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Xiaoler\Blade\View
     */
    public function make($view, $data = [], $mergeData = [])
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view)
        );

        // Next, we will create the view instance and call the view creator for the view
        // which can set any data, etc. Then we will return the view instance back to
        // the caller for rendering or performing other view manipulations on this.
        $data = array_merge($mergeData, $this->parseData($data));

        return $this->viewInstance($view, $path, $data);
    }

    /**
     * Get the rendered content of the view based on a given condition.
     *
     * @param  bool  $condition
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return string
     */
    public function renderWhen($condition, $view, $data = [], $mergeData = [])
    {
        if (! $condition) {
            return '';
        }

        return $this->make($view, $this->parseData($data), $mergeData)->render();
    }

    /**
     * Get the rendered contents of a partial from a loop.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  string  $iterator
     * @param  string  $empty
     * @return string
     */
    public function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->make(
                    $view, ['key' => $key, $iterator => $value]
                )->render();
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // view. Alternatively, the "empty view" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
        else {
            $result = Str::startsWith($empty, 'raw|')
                        ? substr($empty, 4)
                        : $this->make($empty)->render();
        }

        return $result;
    }

    /**
     * Normalize a view name.
     *
     * @param  string $name
     * @return string
     */
    protected function normalizeName($name)
    {
        return ViewName::normalize($name);
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Create a new view instance from the given arguments.
     *
     * @param  string  $view
     * @param  string  $path
     * @param  array  $data
     * @return \Xiaoler\Blade\View
     */
    protected function viewInstance($view, $path, $data)
    {
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }

    /**
     * Determine if a given view exists.
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            $this->finder->find($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate view engine for the given path.
     *
     * @param  string  $path
     * @return \Xiaoler\Blade\Engines\EngineInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path)
    {
        if (! $extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: $path");
        }

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    /**
     * Get the extension used by the view file.
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($value) use ($path) {
            return Str::endsWith($path, '.'.$value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Increment the rendering counter.
     *
     * @return void
     */
    public function incrementRender()
    {
        $this->renderCount++;
    }

    /**
     * Decrement the rendering counter.
     *
     * @return void
     */
    public function decrementRender()
    {
        $this->renderCount--;
    }

    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering()
    {
        return $this->renderCount == 0;
    }

    /**
     * Add a location to the array of view locations.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->finder->addLocation($location);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Prepend a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function prependNamespace($namespace, $hints)
    {
        $this->finder->prependNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Register a valid view extension and its engine.
     *
     * @param  string    $extension
     * @param  string    $engine
     * @param  \Closure  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge([$extension => $engine], $this->extensions);
    }

    /**
     * Flush all of the factory state like sections and stacks.
     *
     * @return void
     */
    public function flushState()
    {
        $this->renderCount = 0;

        $this->flushSections();
        $this->flushStacks();
    }

    /**
     * Flush all of the section contents if done rendering.
     *
     * @return void
     */
    public function flushStateIfDoneRendering()
    {
        if ($this->doneRendering()) {
            $this->flushState();
        }
    }

    /**
     * Get the extension to engine bindings.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
     *
     * @return \Xiaoler\Blade\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }

    /**
     * Get the view finder instance.
     *
     * @return \Xiaoler\Blade\ViewFinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Set the view finder instance.
     *
     * @param  \Xiaoler\Blade\ViewFinderInterface  $finder
     * @return void
     */
    public function setFinder(ViewFinderInterface $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Flush the cache of views located by the finder.
     *
     * @return void
     */
    public function flushFinderCache()
    {
        $this->getFinder()->flush();
    }

    /**
     * Get an item from the shared data.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function shared($key, $default = null)
    {
        return Arr::get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the environment.
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }
}
