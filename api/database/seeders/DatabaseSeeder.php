<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea (o actualiza) el usuario del panel. Es idempotente: se ejecuta
     * en cada arranque del contenedor sin duplicar datos.
     */
    public function run(): void
    {
        $email = config('signing.demo_user.email');
        $password = config('signing.demo_user.password');

        if (blank($password)) {
            throw new RuntimeException('DEMO_USER_PASSWORD no está definida.');
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Administrador demo', 'password' => $password],
        );
    }
}
