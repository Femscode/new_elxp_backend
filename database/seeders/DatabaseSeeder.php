<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'cs-elxp-admin@gmail.com'],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'first_name' => 'CS',
                'last_name' => 'Admin',
                'username' => 'cs_admin',
                'password' => \Illuminate\Support\Facades\Hash::make('CSAdmin@2026'),
                'user_type' => 'Admin',
            ]
        );
    }
}
