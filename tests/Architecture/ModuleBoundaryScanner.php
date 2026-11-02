<?php

declare(strict_types=1);

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ModuleBoundaryScanner
{
    private const AREA = 'App\\\\(?:Modules|Integrations)\\\\';

    private const PUBLIC_SURFACE = [
        'Application\\ModuleApi\\',
        'Domain\\Events\\',
        'Domain\\Contracts\\',
        'Domain\\Enums\\',
        'Infrastructure\\Persistence\\Models\\',
    ];

    public function __construct(private readonly string $projectRoot) {}

    /**
     * @return list<string> "file => imported class" for each import of another module's private part
     */
    public function violations(): array
    {
        $violations = [];

        foreach (['Modules', 'Integrations'] as $area) {
            $dir = "{$this->projectRoot}/app/{$area}";
            if (! is_dir($dir)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $path = substr($file->getPathname(), strlen($this->projectRoot) + 1);
                foreach ($this->privateImports($path, (string) file_get_contents($file->getPathname())) as $class) {
                    $violations[] = "{$path} => {$class}";
                }
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function privateImports(string $path, string $source): array
    {
        preg_match('#^app/(?:Modules|Integrations)/([^/]+)/#', $path, $own);
        preg_match_all('/'.self::AREA.'(\w+)\\\\([\w\\\\]+)/', $source, $matches, PREG_SET_ORDER);

        $private = [];
        foreach ($matches as [$full, $module, $rest]) {
            if ($module === $own[1] || $this->isPublicSurface($rest)) {
                continue;
            }
            $private[$full] = $full;
        }

        return array_values($private);
    }

    private function isPublicSurface(string $classPath): bool
    {
        foreach (self::PUBLIC_SURFACE as $prefix) {
            if (str_starts_with($classPath, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
