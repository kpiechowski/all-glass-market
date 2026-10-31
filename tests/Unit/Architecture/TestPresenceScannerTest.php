<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Architecture\TestPresenceScanner;

class TestPresenceScannerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/presence-'.uniqid();
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    public function test_it_reports_a_command_handler_without_a_test(): void
    {
        $this->touch('app/Modules/Shop/Application/Commands/AddItem/AddItemCommandHandler.php');

        $this->assertSame(
            ['app/Modules/Shop/Application/Commands/AddItem/AddItemCommandHandler.php'],
            $this->missing(),
        );
    }

    public function test_a_test_in_either_suite_satisfies_the_check(): void
    {
        $this->touch('app/Modules/Shop/Application/Commands/AddItem/AddItemCommandHandler.php');
        $this->touch('app/Modules/Shop/Domain/Policies/ItemDomainPolicy.php');
        $this->touch('tests/Feature/Modules/Shop/Application/Commands/AddItem/AddItemCommandHandlerTest.php');
        $this->touch('tests/Unit/Modules/Shop/Domain/Policies/ItemDomainPolicyTest.php');

        $this->assertSame([], $this->missing());
    }

    public function test_it_checks_each_listed_class_kind(): void
    {
        $paths = [
            'app/Modules/Shop/Domain/Entities/Item.php',
            'app/Modules/Shop/Domain/Policies/ItemDomainPolicy.php',
            'app/Modules/Shop/Infrastructure/Persistence/Mappers/ItemMapper.php',
            'app/Modules/Shop/Infrastructure/Persistence/Queries/ItemQueries.php',
            'app/Modules/Shop/Application/Listeners/ReactToItemAdded.php',
        ];
        foreach ($paths as $path) {
            $this->touch($path);
        }

        $this->assertEqualsCanonicalizing($paths, $this->missing());
    }

    public function test_it_ignores_other_classes_and_core(): void
    {
        $this->touch('app/Modules/Shop/Application/Commands/AddItem/AddItemCommand.php');
        $this->touch('app/Modules/Shop/Infrastructure/Persistence/Models/ItemModel.php');
        $this->touch('app/Core/Abstracts/SomeMapper.php');

        $this->assertSame([], $this->missing());
    }

    public function test_it_covers_integrations_too(): void
    {
        $this->touch('app/Integrations/Otomoto/Application/Listeners/PublishOffer.php');

        $this->assertSame(['app/Integrations/Otomoto/Application/Listeners/PublishOffer.php'], $this->missing());
    }

    /**
     * @return list<string>
     */
    private function missing(): array
    {
        return (new TestPresenceScanner($this->root))->missingTests();
    }

    private function touch(string $relativePath): void
    {
        $path = $this->root.'/'.$relativePath;
        is_dir(dirname($path)) || mkdir(dirname($path), 0777, true);
        file_put_contents($path, '<?php');
    }
}
