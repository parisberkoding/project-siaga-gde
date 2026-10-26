<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Super Admin',
                'role' => 'superadmin',
                'jabatan' => 'Super Administrator',
                'email' => 'superadmin@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Admin BBM',
                'role' => 'admin',
                'jabatan' => 'Administrator BBM',
                'email' => 'admin@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'manager',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Manager Operasional',
                'role' => 'manager',
                'jabatan' => 'Manager',
                'email' => 'manager@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester1',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Dicky Nugraha',
                'role' => 'requester',
                'jabatan' => 'Staff Operasional',
                'email' => 'dicky.nugraha@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester2',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Ujang Dayat',
                'role' => 'requester',
                'jabatan' => 'Staff Lapangan',
                'email' => 'ujang.dayat@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester3',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Yana Kusyana',
                'role' => 'requester',
                'jabatan' => 'Teknisi Lapangan',
                'email' => 'yana.kusyana@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester4',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Ahmad Sopian',
                'role' => 'requester',
                'jabatan' => 'Staff Maintenance',
                'email' => 'ahmad.sopian@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester5',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Budi Santoso',
                'role' => 'requester',
                'jabatan' => 'Supervisor Lapangan',
                'email' => 'budi.santoso@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester6',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Cecep Hermawan',
                'role' => 'requester',
                'jabatan' => 'Staff Logistik',
                'email' => 'cecep.hermawan@geodipa.com',
                'added_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
