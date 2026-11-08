<?php

declare(strict_types=1);

/** System reasons that reach Change entries; Audit shows them as "System: {reason}". */
return [
    'password_reset_requested' => 'Prośba o reset hasła (niezalogowany)',
    'password_reset_redeemed' => 'Reset hasła przez link (niezalogowany)',
    'invitation_accepted' => 'Przyjęcie zaproszenia (niezalogowany)',
    'console_grant_super_admin' => 'Konsola: nadanie roli Super Admin',
    'console_revoke_super_admin' => 'Konsola: odebranie roli Super Admin',
];
