<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['email' => 'admin@bankflow.test']);

        $admin->name = 'BankFlow Admin';

        if (! $admin->exists) {
            $admin->password = 'password';
        }

        if (is_null($admin->email_verified_at)) {
            $admin->email_verified_at = now();
        }

        $admin->save();
    }
}
