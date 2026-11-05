<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Queries\TechnicalLog;

use App\Modules\Audit\Application\Queries\TechnicalLog\LogFiles;
use App\Modules\Audit\Application\Queries\TechnicalLog\ReadLogEntries;
use App\Modules\Audit\Domain\Enums\LogLevel;
use App\Modules\Audit\Domain\Exceptions\InvalidLogSelection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReadLogEntriesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/read-log-'.uniqid();
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function file(string $content): string
    {
        $path = $this->directory.'/aplikacja-2026-11-05.log';
        file_put_contents($path, $content);

        return $path;
    }

    private function trace(): string
    {
        return "[2026-11-05 10:00:00] local.ERROR: Boom {\"exception\":\"[object] (RuntimeException(code: 0): Boom at /app/X.php:12)\n[stacktrace]\n#0 /app/Y.php(3): X->run()\n#1 {main}\n\"}\n";
    }

    public function test_it_reads_newest_first(): void
    {
        $path = $this->file("[2026-11-05 09:00:00] local.INFO: first\n[2026-11-05 09:01:00] local.WARNING: second\n");

        $page = app(ReadLogEntries::class)->page($path, null, null, 1, 10);

        $this->assertSame(2, $page->total);
        $this->assertSame(['second', 'first'], array_map(fn ($e) => $e->message, $page->entries));
        $this->assertSame('warning', $page->entries[0]->level);
        $this->assertSame('2026-11-05 09:01:00', $page->entries[0]->time);
    }

    public function test_it_parses_a_multi_line_entry_with_a_stack_trace(): void
    {
        $path = $this->file("[2026-11-05 09:00:00] local.INFO: before\n".$this->trace()."[2026-11-05 11:00:00] local.INFO: after\n");

        $page = app(ReadLogEntries::class)->page($path, LogLevel::Error, null, 1, 10);

        $this->assertSame(1, $page->total);
        $entry = $page->entries[0];
        $this->assertSame('Boom', $entry->message);
        $this->assertStringContainsString('RuntimeException', $entry->context);
        $this->assertStringNotContainsString('#0', $entry->context);
        $this->assertSame("[stacktrace]\n#0 /app/Y.php(3): X->run()\n#1 {main}", $entry->stackTrace);
        $this->assertSame('Boom', $entry->summary());
    }

    public function test_it_pages_and_counts_without_returning_every_entry(): void
    {
        $lines = '';
        for ($i = 1; $i <= 7; $i++) {
            $lines .= "[2026-11-05 09:0{$i}:00] local.INFO: entry {$i}\n";
        }

        $page = app(ReadLogEntries::class)->page($this->file($lines), null, null, 2, 3);

        $this->assertSame(7, $page->total);
        $this->assertSame(['entry 4', 'entry 3', 'entry 2'], array_map(fn ($e) => $e->message, $page->entries));
    }

    public function test_it_reads_a_file_larger_than_one_chunk_with_correct_keys(): void
    {
        $lines = '';
        for ($i = 1; $i <= 3000; $i++) {
            $lines .= sprintf("[2026-11-05 09:00:00] local.INFO: entry %04d %s\n", $i, str_repeat('x', 40));
        }
        $path = $this->file($lines);
        $reader = app(ReadLogEntries::class);

        $page = $reader->page($path, null, null, 1, 3000);

        $this->assertSame(3000, $page->total);
        $this->assertSame('entry 0001 '.str_repeat('x', 40), $page->entries[2999]->message);
        $this->assertSame('entry 3000 '.str_repeat('x', 40), $page->entries[0]->message);
        $this->assertSame('0', $page->entries[2999]->key);
        $this->assertSame($page->entries[1500]->message, $reader->find($path, $page->entries[1500]->key)->message);
    }

    public function test_search_covers_message_and_context(): void
    {
        $path = $this->file("[2026-11-05 09:00:00] local.INFO: hello {\"order\":\"A-77\"}\n[2026-11-05 09:01:00] local.INFO: other\n");
        $reader = app(ReadLogEntries::class);

        $this->assertSame(['hello'], array_map(fn ($e) => $e->message, $reader->page($path, null, 'a-77', 1, 10)->entries));
        $this->assertSame(['other'], array_map(fn ($e) => $e->message, $reader->page($path, null, 'OTHER', 1, 10)->entries));
        $this->assertSame(0, $reader->page($path, null, 'nothing', 1, 10)->total);
    }

    public function test_find_refuses_a_non_numeric_key(): void
    {
        $this->assertNull(app(ReadLogEntries::class)->find($this->file($this->trace()), '../x'));
    }

    public function test_the_locator_builds_paths_only_from_a_configured_channel_and_a_real_day(): void
    {
        config(['logging.channels.aplikacja.path' => $this->directory.'/aplikacja.log']);
        $files = app(LogFiles::class);

        $this->assertSame($this->directory.'/aplikacja-2026-11-05.log', $files->pathFor('aplikacja', '2026-11-05'));
        $this->assertNull($files->existingFor('../passwd', '2026-11-05'));
        $this->assertNull($files->existingFor('aplikacja', '../../passwd'));

        foreach ([['../etc/passwd', '2026-11-05'], ['laravel', '2026-11-05'], ['aplikacja/../x', '2026-11-05']] as [$channel, $day]) {
            $this->assertRefused(fn () => $files->pathFor($channel, $day));
        }

        foreach (['../../etc/passwd', '2026-02-30', '2026-1-5', '2026-11-05/../x', '', '2026-11-05 '] as $day) {
            $this->assertRefused(fn () => $files->pathFor('aplikacja', $day));
        }
    }

    public function test_the_locator_refuses_a_symlink_leading_out_of_the_directory(): void
    {
        config(['logging.channels.aplikacja.path' => $this->directory.'/aplikacja.log']);
        $outside = sys_get_temp_dir().'/outside-'.uniqid().'.log';
        file_put_contents($outside, 'secret');
        symlink($outside, $this->directory.'/aplikacja-2026-11-05.log');

        try {
            $this->assertNull(app(LogFiles::class)->existingFor('aplikacja', '2026-11-05'));
        } finally {
            unlink($outside);
        }
    }

    private function assertRefused(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected InvalidLogSelection');
        } catch (InvalidLogSelection) {
            $this->addToAssertionCount(1);
        }
    }
}
