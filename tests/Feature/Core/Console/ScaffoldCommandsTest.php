<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ScaffoldCommandsTest extends TestCase
{
    private const MODULE = 'ScaffoldProbe';

    private const INTEGRATION = 'ScaffoldProbeApi';

    protected function tearDown(): void
    {
        $this->removeScaffold('Modules', self::MODULE);
        $this->removeScaffold('Integrations', self::INTEGRATION);

        parent::tearDown();
    }

    public function test_make_module_generates_the_module_layout(): void
    {
        $this->assertSame(0, Artisan::call('make:module', ['name' => self::MODULE, '--no-interaction' => true]));

        $root = app_path('Modules/ScaffoldProbe');

        foreach ([
            'ScaffoldProbeModuleServiceProvider.php',
            'Domain/Entities/ScaffoldProbe.php',
            'Domain/Enums/ScaffoldProbePermission.php',
            'Domain/Exceptions/ScaffoldProbeNameIsBlank.php',
            'Domain/Policies/ScaffoldProbeDomainPolicy.php',
            'Domain/Repositories/ScaffoldProbeRepository.php',
            'Application/Providers/ScaffoldProbeEventServiceProvider.php',
            'Infrastructure/Factories/ScaffoldProbeModelFactory.php',
            'Infrastructure/Persistence/EloquentScaffoldProbeRepository.php',
            'Infrastructure/Persistence/Mappers/ScaffoldProbeMapper.php',
            'Infrastructure/Persistence/Models/ScaffoldProbeModel.php',
            'Infrastructure/Persistence/Observers/ScaffoldProbeObserver.php',
            'Infrastructure/Persistence/Queries/ScaffoldProbeQueries.php',
            'UserInterface/resources/lang/pl/resources.php',
            'UserInterface/resources/lang/pl/enums.php',
            'UserInterface/resources/lang/pl/exceptions.php',
        ] as $file) {
            $this->assertFileExists("{$root}/{$file}");
        }

        $this->assertCount(1, File::files("{$root}/Infrastructure/migrations"));
        $this->assertSame([], $this->gitkeeps($root));
        $this->assertGeneratedTestsPass('Modules', self::MODULE);
        $this->assertArchitectureHolds();
    }

    public function test_make_integration_generates_the_integration_layout(): void
    {
        $this->assertSame(0, Artisan::call('make:integration', ['name' => self::INTEGRATION, '--no-interaction' => true]));

        $root = app_path('Integrations/ScaffoldProbeApi');

        foreach ([
            'ScaffoldProbeApiIntegrationServiceProvider.php',
            'Application/Listeners/ScaffoldProbeApiEventListener.php',
            'Application/Providers/ScaffoldProbeApiEventServiceProvider.php',
            'Domain/Entities/ScaffoldProbeApi.php',
            'Domain/Repositories/ScaffoldProbeApiRepository.php',
            'Infrastructure/Api/ScaffoldProbeApiApiClient.php',
            'Infrastructure/Mappers/ScaffoldProbeApiPayloadMapper.php',
            'Infrastructure/Persistence/Mappers/ScaffoldProbeApiMapper.php',
            'Infrastructure/Persistence/Models/ScaffoldProbeApiModel.php',
        ] as $file) {
            $this->assertFileExists("{$root}/{$file}");
        }

        $this->assertDirectoryDoesNotExist("{$root}/Domain/Mappers");
        $this->assertSame([], $this->gitkeeps($root));
        $this->assertGeneratedTestsPass('Integrations', self::INTEGRATION);
        $this->assertArchitectureHolds();
    }

    private function assertGeneratedTestsPass(string $area, string $name): void
    {
        $this->assertProcessPasses(['vendor/bin/phpunit', "tests/Unit/{$area}/{$name}", "tests/Feature/{$area}/{$name}"]);
    }

    private function assertArchitectureHolds(): void
    {
        $this->assertProcessPasses(['vendor/bin/deptrac', 'analyse', '--no-progress']);
        $this->assertProcessPasses(['vendor/bin/phpunit', 'tests/Unit/Architecture']);
    }

    /** @param  array<int, string>  $command */
    private function assertProcessPasses(array $command): void
    {
        $process = new Process([PHP_BINARY, ...$command], base_path());
        $process->setTimeout(240)->run();

        $this->assertTrue($process->isSuccessful(), implode(' ', $command)."\n".$process->getOutput().$process->getErrorOutput());
        $this->assertStringNotContainsString('No tests executed', $process->getOutput());
    }

    /** @return array<int, string> */
    private function gitkeeps(string $root): array
    {
        return collect(File::allFiles($root, true))
            ->filter(fn ($file) => $file->getFilename() === '.gitkeep')
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    private function removeScaffold(string $area, string $name): void
    {
        File::deleteDirectory(app_path("{$area}/{$name}"));
        File::deleteDirectory(base_path("tests/Unit/{$area}/{$name}"));
        File::deleteDirectory(base_path("tests/Feature/{$area}/{$name}"));

        foreach ([app_path($area), base_path("tests/Unit/{$area}"), base_path("tests/Feature/{$area}")] as $parent) {
            if (is_dir($parent) && File::isEmptyDirectory($parent)) {
                File::deleteDirectory($parent);
            }
        }
    }
}
