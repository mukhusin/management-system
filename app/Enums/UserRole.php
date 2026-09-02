<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum UserRole: string
{
    use EnumHelpers;

    case SystemAdmin = 'system_admin';
    case TenderOfficer = 'tender_officer';
    case ProjectManager = 'project_manager';
    case DevMember = 'dev_member';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Administrator',
            self::TenderOfficer => 'Procurement / Tender Officer',
            self::ProjectManager => 'Project Manager',
            self::DevMember => 'Development Team Member',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SystemAdmin => 'red',
            self::TenderOfficer => 'blue',
            self::ProjectManager => 'green',
            self::DevMember => 'gray',
        };
    }
}
