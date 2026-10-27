<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            ['name' => 'Camisa de algodón', 'description' => 'Camisa casual de algodón 100%, talla M.', 'price' => 19.99, 'stock' => 50],
            ['name' => 'Pantalón jeans', 'description' => 'Pantalón de mezclilla corte recto.', 'price' => 29.99, 'stock' => 40],
            ['name' => 'Zapatos deportivos', 'description' => 'Zapatos para correr, suela ligera.', 'price' => 45.50, 'stock' => 25],
            ['name' => 'Mochila urbana', 'description' => 'Mochila resistente al agua, 20 litros.', 'price' => 34.00, 'stock' => 30],
            ['name' => 'Gorra deportiva', 'description' => 'Gorra ajustable con visera curva.', 'price' => 12.99, 'stock' => 60],
            ['name' => 'Audífonos inalámbricos', 'description' => 'Audífonos bluetooth con estuche de carga.', 'price' => 39.99, 'stock' => 20],
            ['name' => 'Reloj digital', 'description' => 'Reloj resistente al agua con cronómetro.', 'price' => 24.99, 'stock' => 35],
            ['name' => 'Botella térmica', 'description' => 'Botella de acero inoxidable, 750 ml.', 'price' => 15.99, 'stock' => 45],
            ['name' => 'Chaqueta impermeable', 'description' => 'Chaqueta ligera resistente a la lluvia.', 'price' => 55.00, 'stock' => 15],
            ['name' => 'Lentes de sol', 'description' => 'Lentes con protección UV400.', 'price' => 18.50, 'stock' => 40],
        ];

        foreach ($productos as $producto) {
            Product::create([
                'name' => $producto['name'],
                'slug' => Str::slug($producto['name']).'-'.Str::random(6),
                'description' => $producto['description'],
                'price' => $producto['price'],
                'stock' => $producto['stock'],
                'active' => true,
            ]);
        }
    }
}
