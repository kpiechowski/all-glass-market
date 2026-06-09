<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas\UserForm;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas\UserInfolist;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
