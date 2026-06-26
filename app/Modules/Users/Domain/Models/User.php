<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Models;

use App\Modules\Users\Infrastructure\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password',
    'phone',
    'company_account', 'has_accepted_terms',
    'company_name', 'company_nip', 'company_address', 'shipment_address',
    'city', 'city_code',
    'shipping_information',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function isCompanyAccount(): bool
    {
        return $this->company_account && $this->has_accepted_terms;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
