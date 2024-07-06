<?php

namespace Rappasoft\LaravelPatches\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Rappasoft\LaravelPatches\Models\Patch;
use Rappasoft\LaravelPatches\Patcher;
use Rappasoft\LaravelPatches\Repository;

/**
 * Class RollbackCommand
 *
 * @package Rappasoft\LaravelPatches\Commands
 */
class RollbackCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:rollback {--step=0: The number of patches to be reverted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last patch';

    /**
     * PatchCommand constructor.
     *
     * @param  Patcher  $patcher
     * @param  Repository  $repository
     */
    public function __construct(protected Patcher $patcher, protected Repository $repository)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->rollback();

        return 0;
    }

    /**
     * Rollback the appropriate patches
     *
     * @return string[]
     * @throws FileNotFoundException
     */
    protected function rollback(): array
    {
        $patches = $this->getPatchesForRollback();

        if (! count($patches)) {
            $this->info('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->rollbackPatches($patches);
    }

    /**
     * Decide which patch files to rollback and run their down methods
     *
     * @param  Patch[]  $patches
     *
     * @return string[]
     * @throws FileNotFoundException
     */
    protected function rollbackPatches(array $patches): array
    {
        $rolledBack = [];

        $this->patcher->requireFiles($files = $this->patcher->getPatchFiles($this->patcher->getPatchPaths()));

        foreach ($patches as $patch) {
            if (! $file = Arr::get($files, $patch->patch)) {
                $this->line("<fg=red>Patch not found:</> {$patch->patch}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown($file, $patch);
        }

        return $rolledBack;
    }

    /**
     * Decide which patch files to choose for rollback based on passed in options
     *
     * @return Patch[]
     */
    protected function getPatchesForRollback(): array
    {
        $steps = (int)$this->option('step');

        return $steps > 0 ? $this->repository->getPatches($steps) : $this->repository->getLast();
    }

    /**
     * Run the down method on the patch
     *
     * @param  string  $file
     * @param  object  $patch
     */
    protected function runDown(string $file, object $patch): void
    {
        $instance = $this->patcher->resolve($file);

        $name = $this->patcher->getPatchName($file);

        $this->line("<comment>Rolling back:</comment> {$name}");

        $startTime = microtime(true);

        $this->patcher->runPatch($instance, 'down');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        $this->repository->delete($patch);

        $this->line("<info>Rolled back:</info>  {$name} ({$runTime}ms)");
    }
}
