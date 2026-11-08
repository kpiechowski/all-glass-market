<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Architecture\ModuleBoundaryScanner;

class ModuleBoundaryScannerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/boundary-'.uniqid();
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    public function test_it_reports_an_import_of_another_modules_private_class(): void
    {
        $this->write('app/Modules/Shop/Application/Commands/A.php', 'use App\Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;');

        $this->assertSame(
            ['app/Modules/Shop/Application/Commands/A.php => App\Modules\Users\Infrastructure\Persistence\Mappers\UserMapper'],
            $this->violations(),
        );
    }

    public function test_it_allows_the_documented_surface(): void
    {
        $this->write('app/Modules/Shop/Application/A.php', implode("\n", [
            'use App\Modules\Users\Application\ModuleApi\RoleRegistry;',
            'use App\Modules\Users\Domain\Events\UserCreated;',
            'use App\Modules\Users\Domain\Contracts\Loggable;',
            'use App\Modules\Users\Domain\Enums\RoleEnum;',
            'use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;',
            'use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;',
        ]));

        $this->assertSame([], $this->violations());
    }

    public function test_it_allows_a_module_to_use_itself(): void
    {
        $this->write('app/Modules/Shop/Application/A.php', 'use App\Modules\Shop\Infrastructure\Persistence\Mappers\ItemMapper;');

        $this->assertSame([], $this->violations());
    }

    public function test_it_treats_integrations_like_modules_and_catches_inline_names(): void
    {
        $this->write('app/Integrations/Otomoto/Application/A.php', 'new \App\Modules\Shop\Domain\Entities\Item();');

        $this->assertSame(
            ['app/Integrations/Otomoto/Application/A.php => App\Modules\Shop\Domain\Entities\Item'],
            $this->violations(),
        );
    }

    /**
     * @return list<string>
     */
    private function violations(): array
    {
        return (new ModuleBoundaryScanner($this->root))->violations();
    }

    private function write(string $relativePath, string $body): void
    {
        $path = $this->root.'/'.$relativePath;
        is_dir(dirname($path)) || mkdir(dirname($path), 0777, true);
        file_put_contents($path, "<?php\n".$body);
    }
}
