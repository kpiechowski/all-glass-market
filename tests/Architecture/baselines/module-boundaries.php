<?php

declare(strict_types=1);

/** Known cross-module imports of private classes, removed in tickets 05 and 06. */
return [
    'app/Modules/Audit/Domain/Models/ChangesLog.php => App\\Modules\\Users\\Domain\\Models\\User',
    'app/Modules/Audit/Domain/Models/UserJournal.php => App\\Modules\\Users\\Domain\\Models\\User',
    'app/Modules/Users/Domain/Models/User.php => App\\Modules\\Audit\\Domain\\Traits\\HasChangesLog',
    'app/Modules/Users/UserInterface/Filament/Resources/Users/UserResource.php => App\\Modules\\Audit\\UserInterface\\Filament\\RelationManagers\\ChangesLogsRelationManager',
];
