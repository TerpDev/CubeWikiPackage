<?php

namespace TerpDev\CubeWikiPackage\Commands;

use Illuminate\Console\Command;

class CubeWikiPackageCommand extends Command
{
    public $signature = 'cubewikipackage';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
