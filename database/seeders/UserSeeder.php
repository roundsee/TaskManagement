<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Andi Wijaya', 'email' => 'owner@koperasi.test', 'role' => 'owner', 'department' => 'Direksi'],
            ['name' => 'Rina Marlina', 'email' => 'hrd@koperasi.test', 'role' => 'hrd', 'department' => 'HRD'],
            ['name' => 'Dewi Anggraini', 'email' => 'ba@koperasi.test', 'role' => 'ba', 'department' => 'IT'],
            ['name' => 'Budi Santoso', 'email' => 'dev@koperasi.test', 'role' => 'developer', 'department' => 'IT'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.kredit@koperasi.test', 'role' => 'staff', 'department' => 'Kredit'],
            ['name' => 'Agus Priyanto', 'email' => 'agus.operasional@koperasi.test', 'role' => 'staff', 'department' => 'Operasional'],
        ];

        foreach ($users as $data) {
            $user = User::create([
                ...$data,
                'password' => 'password',
            ]);
            $user->assignRole($data['role']);
        }
    }
}
