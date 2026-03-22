<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales\PaymentMethod;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'nombre'     => 'EFECTIVO',
                'codigo_sri' => '01',
                'activo'     => true,
            ],
            [
                'nombre'     => 'TRANSFERENCIA',
                'codigo_sri' => '17',
                'activo'     => true,
            ],
            [
                'nombre'     => 'TARJETA DE CRÉDITO',
                'codigo_sri' => '19',
                'activo'     => true,
            ],
            [
                'nombre'     => 'TARJETA DE DÉBITO',
                'codigo_sri' => '16',
                'activo'     => true,
            ],
            [
                'nombre'     => 'CHEQUE',
                'codigo_sri' => '20',
                'activo'     => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['nombre' => $method['nombre']],
                [
                    'codigo_sri' => $method['codigo_sri'],
                    'activo'     => $method['activo'],
                ]
            );
        }
    }
}
