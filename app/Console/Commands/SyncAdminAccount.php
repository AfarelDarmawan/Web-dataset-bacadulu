<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncAdminAccount extends Command
{
    protected $signature = 'bacadulu:admin-sync
                            {--reset-password : Ganti password akun admin dengan BACADULU_ADMIN_PASSWORD dari .env}';

    protected $description = 'Menyelaraskan akun administrator BacaDulu dengan konfigurasi .env';

    public function handle(): int
    {
        $name = trim((string) config('bacadulu.admin.name'));
        $email = mb_strtolower(trim((string) config('bacadulu.admin.email')));
        $password = (string) config('bacadulu.admin.password');
        $resetPassword = (bool) $this->option('reset-password');

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('BACADULU_ADMIN_NAME atau BACADULU_ADMIN_EMAIL di .env tidak valid.');

            return self::FAILURE;
        }

        $admin = User::query()->where('email', $email)->first();
        $needsPassword = ! $admin || $resetPassword;

        if ($needsPassword && ! $this->passwordIsAcceptable($password)) {
            $this->components->error(
                'BACADULU_ADMIN_PASSWORD wajib 12–128 karakter serta mengandung huruf besar, huruf kecil, angka, dan simbol.'
            );

            return self::FAILURE;
        }

        if (app()->environment('production') && (
            $email === 'admin@bacadulu.test'
            || ($needsPassword && $password === 'ChangeMe123!')
        )) {
            $this->components->error('Kredensial admin contoh tidak boleh digunakan di production.');

            return self::FAILURE;
        }

        $admin ??= new User();
        $admin->name = $name;
        $admin->email = $email;
        $admin->role = 'admin';
        $admin->status = 'active';
        $admin->email_verified_at ??= now();

        if ($needsPassword) {
            $admin->password = Hash::make($password);
        }

        $admin->save();

        $this->components->info('Akun admin berhasil diselaraskan.');
        $this->line('Email: '.$admin->email);
        $this->line('Status: aktif');
        $this->line('Password: '.($needsPassword ? 'diperbarui dari .env' : 'tidak diubah'));

        return self::SUCCESS;
    }

    private function passwordIsAcceptable(string $password): bool
    {
        $length = mb_strlen($password);

        return $length >= 12
            && $length <= 128
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^a-zA-Z0-9]/', $password) === 1;
    }
}
