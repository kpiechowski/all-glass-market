<?php

declare(strict_types=1);

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TestPresenceScanner
{
    private const KINDS = [
        '#/Application/Commands/.+CommandHandler\.php$#',
        '#/Domain/Entities/[^/]+\.php$#',
        '#/Domain/Policies/[^/]+DomainPolicy\.php$#',
        '#/Mappers/[^/]+Mapper\.php$#',
        '#/Persistence/Queries/[^/]+Queries\.php$#',
        '#/Application/Listeners/[^/]+\.php$#',
    ];

    public function __construct(private readonly string $projectRoot) {}

    /**
     * @return list<string> app-relative paths of classes that have no mirrored test
     */
    public function missingTests(): array
    {
        return array_values(array_filter(
            $this->classesNeedingTests(),
            fn (string $path): bool => ! $this->hasTest($path),
        ));
    }

    public function hasTest(string $appRelativePath): bool
    {
        $mirrored = substr($appRelativePath, strlen('app/'), -strlen('.php')).'Test.php';

        return is_file("{$this->projectRoot}/tests/Unit/{$mirrored}")
            || is_file("{$this->projectRoot}/tests/Feature/{$mirrored}");
    }

    /**
     * @return list<string>
     */
    private function classesNeedingTests(): array
    {
        $found = [];

        foreach (['Modules', 'Integrations'] as $area) {
            $dir = "{$this->projectRoot}/app/{$area}";
            if (! is_dir($dir)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                $path = substr($file->getPathname(), strlen($this->projectRoot) + 1);
                foreach (self::KINDS as $kind) {
                    if (preg_match($kind, $path) === 1) {
                        $found[] = $path;
                        break;
                    }
                }
            }
        }

        sort($found);

        return $found;
    }
}
