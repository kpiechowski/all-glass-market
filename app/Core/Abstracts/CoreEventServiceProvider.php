<?php

declare(strict_types=1);

namespace App\Core\Abstracts;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;

/**
 * Base event provider for all module event service providers.
 *
 * The parent class automatically handles:
 *   - $listen   — event → listener mappings
 *   - $observers — model → observer mappings
 *   - $subscribe — event subscriber classes
 *
 * Simply define those arrays in your concrete provider:
 *
 *   protected $listen = [UserCreated::class => [SendWelcomeEmail::class]];
 *   protected $observers = [User::class => UserObserver::class];
 */
abstract class CoreEventServiceProvider extends BaseEventServiceProvider {}
