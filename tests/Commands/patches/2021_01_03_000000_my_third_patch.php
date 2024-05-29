<?php

use Rappasoft\LaravelPatches\Patch;

return new class extends Patch {
    /**
     * Run the patch.
     *
     * @return void
     */
    public function up(): void
    {
        $this->log('Hello Third!');
    }

    /**
     * Reverse the patch.
     *
     * @return void
     */
    public function down(): void
    {
        \Log::info('Goodbye Third');
    }
};
