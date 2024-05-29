<?php

use Rappasoft\LaravelPatches\Patch;

class MyFirstPatch extends Patch
{
    /**
     * Run the patch.
     *
     * @return void
     */
    public function up(): void
    {
        $this->log('Hello First!');
    }

    /**
     * Reverse the patch.
     *
     * @return void
     */
    public function down(): void
    {
        \Log::info('Goodbye First');
    }
}
