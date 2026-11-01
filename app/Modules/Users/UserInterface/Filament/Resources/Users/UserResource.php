<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\Infrastructure\Persistence\Queries\UserQueries;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas\UserForm;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas\UserInfolist;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = UserModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('users::resources.user.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users::resources.user.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(UserQueries::class)->listing();
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(UsersPermission::ManageUsers);
    }

    public static function canCreate(): bool
    {
        return CurrentActor::can(UsersPermission::ManageUsers);
    }

    /** RULE-08: a Super Admin is read-only for an Admin. The handler checks again. */
    public static function canEdit(Model $record): bool
    {
        return CurrentActor::can(UsersPermission::ManageUsers)
            && $record instanceof UserModel
            && RoleDisplay::canManage($record);
    }

    /** There is no delete in V2 (D-39). */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * Seam: the audit history (Audit) and the "Powiązane oferty" tab (Offers, D-05) attach here.
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
