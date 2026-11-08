<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogChannelsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/log-channels-'.uniqid();
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_every_labelled_channel_is_a_daily_channel_kept_14_days_with_a_disk_safe_name(): void
    {
        foreach (config('logging.labels') as $channel => $label) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $channel);
            $this->assertSame('daily', config("logging.channels.{$channel}.driver"));
            $this->assertSame(14, (int) config("logging.channels.{$channel}.days"));
            $this->assertSame(storage_path("logs/{$channel}.log"), config("logging.channels.{$channel}.path"));
        }
    }

    public function test_every_channel_label_resolves_to_a_polish_translation(): void
    {
        $this->assertSame(['aplikacja', 'bezpieczenstwo', 'kolejka'], array_keys(config('logging.labels')));

        foreach (config('logging.labels') as $key) {
            $this->assertNotSame($key, __($key, [], 'pl'), "Missing translation for {$key}");
        }

        $this->assertSame('bezpieczeństwo', __(config('logging.labels.bezpieczenstwo'), [], 'pl'));
    }

    public function test_each_channel_writes_to_its_own_dated_file(): void
    {
        foreach (array_keys(config('logging.labels')) as $channel) {
            config(["logging.channels.{$channel}.path" => "{$this->directory}/{$channel}.log"]);
            Log::forgetChannel($channel);

            Log::channel($channel)->error("message for {$channel}");
        }

        foreach (array_keys(config('logging.labels')) as $channel) {
            $file = "{$this->directory}/{$channel}-".now()->format('Y-m-d').'.log';

            $this->assertFileExists($file);
            $this->assertStringContainsString("message for {$channel}", File::get($file));
            $this->assertCount(1, File::glob("{$this->directory}/{$channel}-*.log"));
        }
    }

    public function test_the_default_channel_logs_to_aplikacja(): void
    {
        $this->assertSame(['aplikacja'], config('logging.channels.stack.channels'));
    }
}
