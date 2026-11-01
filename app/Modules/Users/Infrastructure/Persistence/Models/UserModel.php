<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Factories\UserModelFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * Read-only model of a User, and the application's authenticatable.
 *
 * The 'users' columns map to the entity as: shipment_address = pickup address,
 * city_code = postal code, shipping_note = Otomoto shipping text.
 * The Account-type lock ends 30 minutes after account_type_changed_at (RULE-07).
 */
#[Hidden(['password', 'remember_token'])]
final class UserModel extends ReadOnlyModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    /** @use HasFactory<UserModelFactory> */
    use Authenticatable, Authorizable, CanResetPassword, HasFactory, MustVerifyEmail, Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    /** WF-05: a Deactivated User cannot sign in. */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    protected static function newFactory(): UserModelFactory
    {
        return UserModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'seller_declaration_accepted_at' => 'datetime',
            'account_type_changed_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'extra_permissions' => 'array',
        ];
    }

    /**
     * The framework itself writes the remember token and a rehashed password
     * while signing in. Those two are the only writes allowed outside the
     * repository adapter; ticket 03 owns sign-in.
     */
    public function save(array $options = []): bool
    {
        $authPlumbing = ['remember_token', 'password', 'updated_at'];

        if ($this->exists && array_diff(array_keys($this->getDirty()), $authPlumbing) === []) {
            return self::writing(fn (): bool => parent::save($options));
        }

        return parent::save($options);
    }
}
