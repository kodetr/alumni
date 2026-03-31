<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin-user {email : Email user yang ingin dijadikan admin}')]
#[Description('Set user role menjadi admin berdasarkan email')]
class MakeAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("User dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->info("User {$email} sudah berperan sebagai admin.");

            return self::SUCCESS;
        }

        $user->update(['role' => User::ROLE_ADMIN]);

        $this->info("User {$email} berhasil dijadikan admin.");

        return self::SUCCESS;
    }
}
