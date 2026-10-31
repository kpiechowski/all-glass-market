<?php

declare(strict_types=1);

namespace App\Core\Console\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Shared by make:module and make:integration. The using command defines
 * $moduleName, $singularName, $rootNamespace and $bindsRepository.
 */
trait ScaffoldsFromStubs
{
    /**
     * @param  array<string, string>  $files  path relative to $root => stub
     */
    private function writeStubs(string $root, array $files): void
    {
        foreach ($files as $relativePath => $stub) {
            $target = $root.DIRECTORY_SEPARATOR.$relativePath;

            File::ensureDirectoryExists(dirname($target));
            File::put($target, strtr(File::get(__DIR__.'/../../Support/Stubs/'.$stub), $this->stubTokens()));

            $this->line("  <fg=green>✓</> {$relativePath}");
        }
    }

    /**
     * Entity, port, policy, exception, model, mapper, adapter, factory and
     * migration: the persistence slice both modules and integrations start with.
     *
     * @return array<string, string>
     */
    private function persistenceFiles(): array
    {
        $x = $this->singularName;

        return [
            "Domain/Entities/{$x}.php" => 'entity.stub',
            "Domain/Exceptions/{$x}NameIsBlank.php" => 'exception.stub',
            "Domain/Policies/{$x}DomainPolicy.php" => 'domain-policy.stub',
            "Domain/Repositories/{$x}Repository.php" => 'repository.stub',
            "Infrastructure/Factories/{$x}ModelFactory.php" => 'factory.stub',
            'Infrastructure/migrations/'.now()->format('Y_m_d_His')."_create_{$this->tableName()}_table.php" => 'migration.stub',
            "Infrastructure/Persistence/Eloquent{$x}Repository.php" => 'eloquent-repository.stub',
            "Infrastructure/Persistence/Mappers/{$x}Mapper.php" => 'entity-mapper.stub',
            "Infrastructure/Persistence/Models/{$x}Model.php" => 'model.stub',
            'UserInterface/resources/lang/pl/exceptions.php' => 'lang-exceptions.stub',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function persistenceTestFiles(): array
    {
        $x = $this->singularName;
        $n = $this->moduleName;
        $area = $this->testArea();

        return [
            "Unit/{$area}/{$n}/Domain/Entities/{$x}Test.php" => 'test-entity.stub',
            "Unit/{$area}/{$n}/Domain/Policies/{$x}DomainPolicyTest.php" => 'test-policy.stub',
            "Feature/{$area}/{$n}/Infrastructure/Persistence/Mappers/{$x}MapperTest.php" => 'test-mapper.stub',
            "Feature/{$area}/{$n}/Infrastructure/Persistence/Eloquent{$x}RepositoryTest.php" => 'test-repository.stub',
        ];
    }

    private function tableName(): string
    {
        return Str::snake(Str::plural($this->singularName));
    }

    private function testArea(): string
    {
        return $this->rootNamespace === 'App\\Integrations' ? 'Integrations' : 'Modules';
    }

    /**
     * @return array<string, string>
     */
    private function stubTokens(): array
    {
        $module = "{$this->rootNamespace}\\{$this->moduleName}";

        return [
            '{{ModuleName}}' => $this->moduleName,
            '{{ModuleSnake}}' => Str::snake($this->moduleName),
            '{{SingularName}}' => $this->singularName,
            '{{SingularSnake}}' => Str::snake($this->singularName),
            '{{RootNamespace}}' => $this->rootNamespace,
            '{{Table}}' => $this->tableName(),
            '{{TestArea}}' => $this->testArea(),
            '{{EventProviderImports}}' => "use {$module}\\Application\\Providers\\{$this->moduleName}EventServiceProvider;\n",
            '{{EventProviders}}' => "\n        {$this->moduleName}EventServiceProvider::class,\n    ",
            '{{BindingImports}}' => $this->bindsRepository
                ? "use {$module}\\Domain\\Repositories\\{$this->singularName}Repository;\n"
                    ."use {$module}\\Infrastructure\\Persistence\\Eloquent{$this->singularName}Repository;\n"
                : '',
            '{{ContainerBindings}}' => $this->bindsRepository
                ? "\n        {$this->singularName}Repository::class => Eloquent{$this->singularName}Repository::class,\n    "
                : '',
        ];
    }
}
