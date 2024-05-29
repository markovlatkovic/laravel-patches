<?php

namespace Rappasoft\LaravelPatches;

use Error;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Patcher
 *
 * @package Rappasoft\LaravelPatches
 */
class Patcher
{
    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param  OutputInterface  $output
     * @return $this
     */
    public function setOutput(OutputInterface $output): Patcher
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Make sure the patches table exists
     *
     * @return bool
     */
    public function patchesTableExists(): bool
    {
        return Schema::hasTable(config('laravel-patches.table_name'));
    }

    /**
     * Return the array of paths to look through for patches
     *
     * @return string[]
     */
    public function getPatchPaths(): array
    {
        return [$this->getPatchPath()];
    }

    /**
     * Get the path to the patch directory.
     *
     * @return string
     */
    public function getPatchPath(): string
    {
        return database_path('patches');
    }

    /**
     * @param  string[] $paths
     *
     * @return string[]
     */
    public function getPatchFiles(array $paths): array
    {
        return collect($paths)
            ->flatMap(fn ($path) => Str::endsWith($path, '.php') ? [$path] : $this->files->glob($path.'/*_*.php'))
            ->filter()
            ->values()
            ->keyBy(fn ($file) => $this->getPatchName($file))
            ->sortBy(fn ($_file, $key) => $key)
            ->all();
    }

    /**
     * Get the ClassName
     *
     * @param  string $name
     *
     * @return string
     */
    public function getClassName(string $name): string
    {
        return Str::studly($name);
    }

    /**
     * Get the name of the patch.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function getPatchName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Require in all the patch files in a given path.
     *
     * @param  string[]  $files
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Resolve a patch instance from a file.
     *
     * @param  string  $file
     *
     * @return object
     */
    public function resolve(string $file): object
    {
        $name = $this->getPatchName($file);
        $class = Str::studly(implode('_', array_slice(explode('_', $name), 4)));

        try {
            return new $class;
        } catch (Error $e) {
            return require $file;
        }
    }

    /**
     * Run the specified method on the patch
     *
     * @param  object  $patch
     * @param  string  $method
     *
     * @return string[]|null
     */
    public function runPatch(object $patch, string $method): ?array
    {
        if (method_exists($patch, $method)) {
            $patch->{$method}();

            return $patch->log;
        }

        return null;
    }
}
