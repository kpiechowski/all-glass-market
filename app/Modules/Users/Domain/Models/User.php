<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Models;

use App\Modules\Audit\Domain\Contracts\LoggableModel;
use App\Modules\Audit\Domain\Traits\HasChangesLog;
use App\Modules\Users\Domain\Enums\RoleEnum;
use App\Modules\Users\Infrastructure\Factories\UserFactory;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role',
    'phone',
    'company_account', 'has_accepted_terms',
    'company_name', 'company_nip', 'company_address', 'shipment_address',
    'city', 'city_code',
    'shipping_note',
    // role is intentionally excluded — managed only via AssignUserRoleCommand
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, LoggableModel
{
    /** @use HasFactory<UserFactory> */
    use HasChangesLog, HasFactory, Notifiable;

    /** @var array<string> Fields excluded from audit logging */
    protected static array $notLoggable = [
        'id', 'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_account' => 'boolean',
            'has_accepted_terms' => 'boolean',
            'role' => RoleEnum::class,
        ];
    }

    public function isCompanyAccount(): bool
    {
        return $this->company_account && $this->has_accepted_terms;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [RoleEnum::Root, RoleEnum::Admin], strict: true);
    }

    public function getLoggableTitle(): string
    {
        return $this->name;
    }

    public function getLoggableResourceName(): string
    {
        return __('users::resource.label');
    }

    public function getLoggableUrl(): ?string
    {
        return UserResource::getUrl('view', ['record' => $this->getKey()]);
    }

    public function getLoggableIcon(): ?string
    {
        return 'heroicon-o-user';
    }
}
