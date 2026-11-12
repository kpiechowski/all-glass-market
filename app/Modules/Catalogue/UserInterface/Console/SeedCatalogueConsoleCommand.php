<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Console;

use App\Modules\Catalogue\Application\ModuleApi\CatalogueTestData;
use App\Modules\Catalogue\Domain\Enums\CatalogueSeedSize;
use Illuminate\Console\Command;

class SeedCatalogueConsoleCommand extends Command
{
    protected $signature = 'seed-katalog {size=m : s, m or l} {--force : Skip the confirmation}';

    public function __construct()
    {
        $this->description = __('catalogue::console.seed_catalogue.description');

        parent::__construct();
    }

    public function handle(CatalogueTestData $testData): int
    {
        if ($this->laravel->environment('production')) {
            $this->error(__('catalogue::console.seed_catalogue.production_refused'));

            return self::FAILURE;
        }

        $size = CatalogueSeedSize::tryFrom((string) $this->argument('size'));

        if ($size === null) {
            $this->error(__('catalogue::console.seed_catalogue.invalid_size', ['size' => (string) $this->argument('size')]));

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(__('catalogue::console.seed_catalogue.confirm'))) {
            $this->warn(__('catalogue::console.seed_catalogue.cancelled'));

            return self::FAILURE;
        }

        $counts = $testData->reload($size);

        $this->info(__('catalogue::console.seed_catalogue.done', ['size' => $size->value]));
        $this->table([], array_map(
            fn (string $kind, int $count): array => [__('catalogue::console.seed_catalogue.rows.'.$kind), $count],
            array_keys($counts),
            $counts,
        ));

        return self::SUCCESS;
    }
}
