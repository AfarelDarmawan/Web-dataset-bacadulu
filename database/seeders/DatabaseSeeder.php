<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('bacadulu.admin.email');
        $password = (string) config('bacadulu.admin.password');

        if (app()->environment('production') && (
            $email === 'admin@bacadulu.test'
            || $password === 'ChangeMe123!'
            || mb_strlen($password) < 12
        )) {
            throw new RuntimeException(
                'Production seed dibatalkan: ganti BACADULU_ADMIN_EMAIL dan gunakan BACADULU_ADMIN_PASSWORD minimal 12 karakter.'
            );
        }

        $admin = User::firstOrNew(['email' => $email]);

        if (! $admin->exists) {
            $admin->name = (string) config('bacadulu.admin.name');
            $admin->password = Hash::make($password);
            $admin->email_verified_at = now();
        } elseif ($this->command && ! Hash::check($password, $admin->password)) {
            $this->command->warn(
                'Password admin yang sudah ada tidak diubah. Gunakan: php artisan bacadulu:admin-sync --reset-password'
            );
        }

        $admin->role = 'admin';
        $admin->status = 'active';
        $admin->save();
    }
}
