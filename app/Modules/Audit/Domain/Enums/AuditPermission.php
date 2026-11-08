<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

use App\Core\Contracts\Permission;

/**
 * Audit's four Permissions (spec §3, D-41). Only RecordHistory is granted to a
 * Role (Admin); the others are held by Super Admin through its wildcard, or
 * granted to a User as an Extra Permission.
 */
enum AuditPermission: string implements Permission
{
    case RecordHistory = 'audit.record_history';
    case AuditLog = 'audit.audit_log';
    case TechnicalLogs = 'audit.technical_logs';
    case RestoreChanges = 'audit.restore_changes';
}
