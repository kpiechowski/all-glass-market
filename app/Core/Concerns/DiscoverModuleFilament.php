<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use Filament\Panel;

trait DiscoverModuleFilament
{
    /**
     * Relative path from app_path() to the directory containing all modules.
     * Override in your PanelProvider when modules live elsewhere.
     */
    protected string $modulesPath = 'Modules';

    protected function discoverModuleFilament(Panel $panel): void
    {
        $root = app_path($this->modulesPath);

        if (!is_dir($root)) {
            return;
        }

        foreach (glob("{$root}/*/UserInterface/Filament", GLOB_ONLYDIR) as $base) {
            $relative = str_replace(app_path() . '/', '', $base);
            $ns = 'App\\' . str_replace('/', '\\', $relative);

            foreach (['Resources', 'Pages', 'Widgets', 'Clusters'] as $type) {
                $dir = "{$base}/{$type}";

                if (!is_dir($dir)) {
                    continue;
                }

                match ($type) {
                    'Resources' => $panel->discoverResources(in: $dir, for: "{$ns}\\{$type}"),
                    'Pages' => $panel->discoverPages(in: $dir, for: "{$ns}\\{$type}"),
                    'Widgets' => $panel->discoverWidgets(in: $dir, for: "{$ns}\\{$type}"),
                    'Clusters' => $panel->discoverClusters(in: $dir, for: "{$ns}\\{$type}"),
                };
            }
        }
    }
}
