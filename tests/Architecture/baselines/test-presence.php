<?php

declare(strict_types=1);

/** Existing classes without tests; each ticket that adds the test removes its line. */
return [
    'app/Modules/Audit/Application/Commands/MarkJournalRead/MarkJournalReadCommandHandler.php',
    'app/Modules/Audit/Application/Listeners/OfferJournalListener.php',
    'app/Modules/Audit/Application/Listeners/OfferPanelNotificationListener.php',
    'app/Modules/Users/Application/Commands/AssignUserRole/AssignUserRoleCommandHandler.php',
    'app/Modules/Users/Application/Commands/ChangeUserPassword/ChangeUserPasswordCommandHandler.php',
    'app/Modules/Users/Application/Commands/CreateUser/CreateUserCommandHandler.php',
    'app/Modules/Users/Application/Commands/DeleteUser/DeleteUserCommandHandler.php',
    'app/Modules/Users/Application/Commands/UpdateUser/UpdateUserCommandHandler.php',
];
