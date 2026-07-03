<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;

class NormalizeCommand extends Command
{
    protected $signature = 'iran-locations:normalize
        {--dry-run : Preview normalization work without writing data}
        {--fix : Apply normalization fixes when the scanner is implemented}';

    protected $description = 'Prepare normalized location name maintenance.';

    public function handle(): int
    {
        $this->info('Laravel Iran Locations normalization');

        if ($this->option('dry-run')) {
            $this->line('Dry run requested.');
        }

        if ($this->option('fix')) {
            $this->line('Fix mode requested.');
        }

        $this->line('Normalization scanning is not implemented yet.');
        $this->line('No database records were created or modified.');

        return self::SUCCESS;
    }
}
