<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'administrator', 'description' => 'Full system administration access'],
            ['name' => 'Executive', 'slug' => 'executive', 'description' => 'Bureau leadership and command oversight'],
            ['name' => 'Staff Officer', 'slug' => 'staff_officer', 'description' => 'General administrative staff'],
            ['name' => 'Investigation Officer', 'slug' => 'investigation_officer', 'description' => 'Investigation and case management duties'],
            ['name' => 'Suppression Officer', 'slug' => 'suppression_officer', 'description' => 'Suppression operations duties'],
            ['name' => 'Immigration Checkpoint Officer', 'slug' => 'immigration_checkpoint_officer', 'description' => 'Checkpoint operations duties'],
            ['name' => 'IT Officer', 'slug' => 'it_officer', 'description' => 'Information technology support'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
