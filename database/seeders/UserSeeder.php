<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $homeStation = Station::active()->orderBy('id')->first();

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@id5.local', 'role' => 'administrator', 'employee_id' => 'EMP-0001', 'position' => 'System Administrator', 'department' => 'IT Division'],
            ['name' => 'Executive User', 'email' => 'executive@id5.local', 'role' => 'executive', 'employee_id' => 'EMP-0002', 'position' => 'Bureau Commander', 'department' => 'Executive Office'],
            ['name' => 'Staff Officer User', 'email' => 'staff@id5.local', 'role' => 'staff_officer', 'employee_id' => 'EMP-0003', 'position' => 'Staff Officer', 'department' => 'Administration'],
            ['name' => 'Investigation Officer User', 'email' => 'investigation@id5.local', 'role' => 'investigation_officer', 'employee_id' => 'EMP-0004', 'position' => 'Investigation Officer', 'department' => 'Investigation Division'],
            ['name' => 'Suppression Officer User', 'email' => 'suppression@id5.local', 'role' => 'suppression_officer', 'employee_id' => 'EMP-0005', 'position' => 'Suppression Officer', 'department' => 'Suppression Division'],
            ['name' => 'Checkpoint Officer User', 'email' => 'checkpoint@id5.local', 'role' => 'immigration_checkpoint_officer', 'employee_id' => 'EMP-0006', 'position' => 'Checkpoint Officer', 'department' => 'Checkpoint Division'],
            ['name' => 'IT Officer User', 'email' => 'it@id5.local', 'role' => 'it_officer', 'employee_id' => 'EMP-0007', 'position' => 'IT Officer', 'department' => 'IT Division'],
        ];

        foreach ($users as $data) {
            $role = Role::where('slug', $data['role'])->first();

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'employee_id' => $data['employee_id'],
                    'position' => $data['position'],
                    'department' => $data['department'],
                    'is_active' => true,
                    'station_id' => $data['role'] === 'immigration_checkpoint_officer' ? $homeStation?->id : null,
                ]
            );

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
