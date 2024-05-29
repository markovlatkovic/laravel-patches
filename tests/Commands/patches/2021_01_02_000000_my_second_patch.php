<?php

use Rappasoft\LaravelPatches\Patch;

class MySecondPatch extends Patch
{
    /**
     * Run the patch.
     *
     * @return void
     */
    public function up(): void
    {
        $this->log('Hello Second!');
    }

    /**
     * Reverse the patch.
     *
     * @return void
     */
    public function down(): void
    {
        \Log::info('Goodbye Second');
    }
}
