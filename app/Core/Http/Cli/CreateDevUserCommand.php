<?php

declare(strict_types=1);

namespace App\Core\Http\Cli;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CreateDevUserCommand extends Command
{
    protected $signature = 'dev:create-user';

    protected $description = 'Create or update a developer user for any configured auth guard (non-production only)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command cannot be run in production.');

            return self::FAILURE;
        }

        // ── 1. Pick guard ────────────────────────────────────────────────────
        $guards = $this->availableGuards();

        if (empty($guards)) {
            $this->error('No Eloquent-backed guards found in config/auth.php.');

            return self::FAILURE;
        }

        $guard = count($guards) === 1
            ? $guards[0]
            : $this->choice('Which guard?', $guards, 0);

        // ── 2. Resolve model ─────────────────────────────────────────────────
        try {
            $modelClass = $this->resolveModelForGuard($guard);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line("  Guard : <fg=cyan>{$guard}</>");
        $this->line("  Model : <fg=cyan>{$modelClass}</>");
        $this->newLine();

        // ── 3. Collect user fields ───────────────────────────────────────────
        $name = $this->ask('Name', 'Admin');
        $email = $this->ask('Email', "dev+{$guard}@example.com");
        $password = $this->secret('Password (leave blank to use "password")') ?: 'password';

        $attributes = [
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ];

        if ($this->modelHasColumn($modelClass, 'is_active')) {
            $attributes['is_active'] = true;
        }

        // ── 4. Role – offer enum options when the model uses one ─────────────
        $roleEnum = $this->detectRoleEnum($modelClass);

        if ($roleEnum !== null) {
            $options = array_column($roleEnum::cases(), 'value');
            $selected = $this->choice('Role', $options, 0);
            $attributes['role'] = $selected;
        }

        // ── 5. Persist ───────────────────────────────────────────────────────
        /** @var Model $model */
        $model = $modelClass::updateOrCreate(['email' => $email], $attributes);

        $action = $model->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->info("{$action} [{$guard}] user: {$email}");

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return guard names whose provider uses the Eloquent driver.
     *
     * @return string[]
     */
    private function availableGuards(): array
    {
        $guards = [];

        foreach (array_keys(config('auth.guards', [])) as $name) {
            $providerName = config("auth.guards.{$name}.provider");
            $driver = config("auth.providers.{$providerName}.driver");

            if ($driver === 'eloquent') {
                $guards[] = $name;
            }
        }

        return $guards;
    }

    /**
     * Walk guard → provider → model and validate the class exists.
     */
    private function resolveModelForGuard(string $guard): string
    {
        $providerName = config("auth.guards.{$guard}.provider");

        if (! $providerName) {
            throw new RuntimeException("Guard [{$guard}] has no provider configured.");
        }

        $model = config("auth.providers.{$providerName}.model");

        if (! $model) {
            throw new RuntimeException("Provider [{$providerName}] has no model configured.");
        }

        if (! class_exists($model)) {
            throw new RuntimeException("Model class [{$model}] does not exist.");
        }

        return $model;
    }

    /**
     * Return the FQCN of the BackedEnum used for the `role` cast, or null.
     *
     * @return class-string<\BackedEnum>|null
     */
    private function detectRoleEnum(string $modelClass): ?string
    {
        /** @var Model $instance */
        $instance = new $modelClass;
        $casts = $instance->getCasts();

        if (! isset($casts['role'])) {
            return null;
        }

        $castClass = $casts['role'];

        return (is_string($castClass) && enum_exists($castClass) && is_a($castClass, \BackedEnum::class, true))
            ? $castClass
            : null;
    }

    /**
     * Check whether the model's table has a given column (via fillable / casts).
     */
    private function modelHasColumn(string $modelClass, string $column): bool
    {
        /** @var Model $instance */
        $instance = new $modelClass;

        return in_array($column, $instance->getFillable(), true)
            || array_key_exists($column, $instance->getCasts());
    }
}
