<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario administrador
        $user = User::updateOrCreate(
            ['email' => 'admin@caseapp.com'],
            [
                'name' => 'Administrador General',
                'email' => 'admin@caseapp.com',
                'password' => bcrypt('4dm1n*2025'), 
            ]
        );

        // Obtener todos los roles existentes
        $roles = Role::pluck('name')->toArray();

        // Asignar todos los roles
        $user->syncRoles($roles);

        echo "Usuario admin creado con todos los roles.\n";
    }
}
