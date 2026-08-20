<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('LANDLORD_EMAIL', 'admin@folkra.co');
        $password = env('LANDLORD_PASSWORD', 'password');

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $existing->update([
                'role'      => 'landlord',
                'tenant_id' => null,
                'client_id' => null,
                'password'  => Hash::make($password),
            ]);
            $this->command->info("Landlord updated: {$email}");
            return;
        }

        User::create([
            'name'      => 'FolkraCS Admin',
            'email'     => $email,
            'password'  => Hash::make($password),
            'role'      => 'landlord',
            'tenant_id' => null,
            'client_id' => null,
        ]);

        $this->command->info("Landlord created: {$email} / {$password}");
    }
}
