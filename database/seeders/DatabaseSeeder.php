<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Never seeds a hardcoded demo password. Create an initial admin only when
     * ADMIN_EMAIL + ADMIN_PASSWORD are set in the environment.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => env('ADMIN_NAME', 'Platform Admin'),
                    'password' => Hash::make($password),
                    'role' => User::ROLE_ADMIN,
                    'tenant_group_id' => null,
                ]
            );
            $this->command?->info("Admin upserted: {$email}");
        } elseif (! User::query()->exists()) {
            $this->command?->warn(
                'No users and ADMIN_EMAIL/ADMIN_PASSWORD not set — create an admin manually, e.g. php artisan tinker'
            );
        } else {
            $this->command?->comment('Skipping admin seed (set ADMIN_EMAIL + ADMIN_PASSWORD to upsert).');
        }

        // Reminder: never leave a known default password in production.
        User::query()
            ->where('email', 'admin@apex-xdr.com')
            ->whereNotNull('password')
            ->each(function (User $user) {
                // Soft-flag in logs only; admin must rotate if still using the old demo account.
                if (Hash::check('Admin@2024!', $user->password)) {
                    $user->forceFill([
                        'password' => Hash::make(Str::password(32)),
                    ])->save();
                    $this->command?->warn(
                        'Disabled demo password for admin@apex-xdr.com — reset that account before use.'
                    );
                }
            });

        $this->call([
            DetectionRulesSeeder::class,
        ]);
    }
}
