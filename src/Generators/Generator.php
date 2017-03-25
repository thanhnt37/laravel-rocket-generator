<?php

namespace LaravelRocket\Generator\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\View\Factory as ViewFactory;

abstract class Generator
{

    /** @var \Illuminate\Config\Repository */
    protected $config;

    /** @var \Illuminate\Filesystem\Filesystem */
    protected $files;

    /** @var \Illuminate\View\Factory */
    protected $view;

    /** @var  string */
    protected $errorString;

    /** @var  bool */
    protected $overwrite;

    /**
     *
     * @param \Illuminate\Config\Repository $config
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Illuminate\View\Factory $view
     */
    public function __construct(
        ConfigRepository $config,
        Filesystem $files,
        ViewFactory $view
    )
    {
        $this->config = $config;
        $this->files = $files;
        $this->view = $view;
    }

    /**
     * @param string $name
     * @param string|null $baseDirectory
     */
    abstract public function generate($name, $baseDirectory = null);

    /**
     * @param bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $this->overwrite = $overwrite;
    }

    public function shouldOverwrite()
    {
        return $this->overwrite;
    }

    /**
     * @param array $data
     * @param string $stubPath
     * @return string
     */
    protected function replace($data, $stubPath)
    {
        $stub = $this->files->get($stubPath);

        foreach ($data as $key => $value) {
            $templateKey = '%%'.strtoupper($key).'%%';
            $stub = str_replace($templateKey, $value, $stub);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $filePath
     * @return bool
     */
    protected function replaceFile($data, $filePath)
    {
        if (!$this->files->exists($filePath)) {
            return false;
        }
        $content = $this->files->get($filePath);
        foreach ($data as $key => $value) {
            $content = str_replace($key, $value.$key, $content);
        }
        $this->files->put($filePath, $content);

        return true;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    protected function getClassName($name)
    {
        return array_slice(explode('\\', $name), 0, -1);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function alreadyExists($path)
    {
        return $this->files->exists($path);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * @param  string $class
     * @return string
     */
    protected function convertClassToPath($class)
    {
        return base_path(str_replace('\\', '/', $class).'.php');
    }

    /**
     * @param string $string
     */
    protected function error($string)
    {
        $this->errorString = $string;
    }

    /**
     * @param string $modelName
     * @param string $classPath
     * @param string $stabFilePath
     * @param array $additionalData
     * @return bool
     */
    protected function generateFile($modelName, $classPath, $stabFilePath, $additionalData = [])
    {
        if ($this->alreadyExists($classPath)) {
            if ($this->shouldOverwrite()) {
                $this->files->delete($classPath);
            } else {
                $this->error($classPath.' already exists.');

                return false;
            }
        }

        $this->makeDirectory($classPath);

        $content = $this->replace([
            'MODEL' => $modelName,
            'model' => strtolower($modelName),
        ] + $additionalData, $stabFilePath);

        if( empty($content) ) {
            return false;
        }

        $this->files->put($classPath, $content);

        return true;
    }

}