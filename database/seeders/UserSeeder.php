<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's user accounts + roles.
     *
     * There is no public registration — every account is created here.
     * The system administrator comes from ADMIN_* in .env; team members
     * get TEAM_MEMBER_PASSWORD. Accounts are matched by email so re-running
     * never overwrites an existing user.
     */
    public function run(): void
    {
        $memberPassword = config('tracker.seed_member_password');

        $accounts = [
            [env('ADMIN_NAME', 'Admin'), env('ADMIN_EMAIL', 'admin@example.com'), env('ADMIN_PASSWORD', 'password'), UserRole::SystemAdmin],
            ['Maalim', 'maalim@emrec.co.tz', $memberPassword, UserRole::TenderOfficer],
            ['Mukhusin', 'backend1developer@gmail.com', $memberPassword, UserRole::ProjectManager],
            ['PhD. Simba', 'simba@emrec.co.tz', $memberPassword, UserRole::ProjectManager],
            ['PhD. Sanga', 'sanga@emrec.co.tz', $memberPassword, UserRole::DevMember],
        ];

        foreach ($accounts as [$name, $email, $password, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'role' => $role],
            );

            // Keep the seeded admin's role authoritative even if the row predates RBAC.
            if ($role === UserRole::SystemAdmin && $user->role !== UserRole::SystemAdmin) {
                $user->update(['role' => UserRole::SystemAdmin]);
            }
        }
    }
}
