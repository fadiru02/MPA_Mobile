<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashierSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => (string) env('CASHIER_EMAIL', 'kasir@angkringan.test')],
            [
                'name' => 'Kasir Angkringan',
                'password' => (string) env('CASHIER_PASSWORD', 'password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
