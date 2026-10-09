<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de prueba (ficticios). No contienen datos personales reales.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario de back-office de PRUEBA (documentado en el README)
        $admin = User::updateOrCreate(
            ['email' => 'admin@piquantum.test'],
            ['name' => 'Administrador de prueba', 'password' => 'admin1234'],
        );
        $admin->is_admin = true;
        $admin->save(); 

        // Clientes de prueba: cada uno con su usuario para iniciar sesión
        foreach ([
            ['Laura Prueba', 'laura@example.com', 'Calle Ficticia 1', 'Madrid', '28001'],
            ['Carlos Demo', 'carlos@example.com', 'Avenida de Prueba 22', 'Sevilla', '41001'],
            ['Marta Test', 'marta@example.com', 'Plaza Inventada 5', 'Valencia', '46001'],
        ] as [$name, $email, $address, $city, $zip]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'cliente1234'],
            );

            Customer::firstOrCreate(['email' => $email], [
                'user_id' => $user->id,
                'name' => $name, 'address' => $address, 'city' => $city, 'postal_code' => $zip,
                'phone' => '600000000',
            ]);
        }

        $categories = [
            'suaves' => ['Suaves', 'Mucho sabor y un picor amable para todos los públicos', '🌶️'],
            'picantes' => ['Picantes', 'Para quienes ya disfrutan del picante de verdad', '🔥'],
            'extremas' => ['Extremas', 'Los chiles más potentes del mundo. Úsalas con cuidado', '💀'],
            'packs' => ['Packs regalo', 'Selecciones de salsas para regalar o descubrir', '🎁'],
        ];
        $cat = [];
        foreach ($categories as $slug => [$name, $desc]) {
            $cat[$slug] = Category::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $desc]);
        }

        $products = [
            ['suaves', 'SAL-002', 'Salsa Chipotle Ahumado', 'Ahumada y ligeramente dulce, ideal para barbacoas.', 'Salsa de chile chipotle (jalapeño ahumado) con tomate asado, cebolla y un toque de panela. Perfecta para carnes a la brasa, tacos y hamburguesas.', 6.50, 60, '🌶️', ['Picor' => '5.000 SHU', 'Chile' => 'Chipotle', 'Formato' => '200 ml', 'Origen' => 'México']],
            ['suaves', 'SAL-003', 'Salsa Verde Jalapeño & Lima', 'Fresca y cítrica, para el día a día.', 'Salsa verde de jalapeño fresco, lima, cilantro y ajo. Aporta frescor a nachos, ensaladas, pescados y huevos.', 5.90, 80, '🍋', ['Picor' => '3.500 SHU', 'Chile' => 'Jalapeño', 'Formato' => '200 ml', 'Origen' => 'México']],
            ['picantes', 'SAL-004', 'Sriracha Artesana', 'Clásica salsa de chile rojo y ajo fermentados.', 'Sriracha elaborada con chile rojo fermentado, ajo, vinagre de arroz y azúcar de caña. Imprescindible en ramen, arroces y wok.', 7.20, 70, '🔥', ['Picor' => '2.500 SHU', 'Chile' => 'Chile rojo', 'Formato' => '250 ml', 'Origen' => 'Tailandia']],
            ['picantes', 'SAL-005', 'Salsa Habanero Mango', 'Tropical, afrutada y con un picor intenso.', 'Combinación de habanero naranja y mango maduro con zanahoria y vinagre de sidra. Ideal para pollo, gambas y tacos al pastor.', 8.90, 45, '🥭', ['Picor' => '150.000 SHU', 'Chile' => 'Habanero', 'Formato' => '150 ml', 'Origen' => 'Península de Yucatán']],
            ['picantes', 'SAL-006', 'Salsa Scotch Bonnet Caribe', 'Picante caribeño con notas de piña y especias.', 'Salsa jamaicana de Scotch Bonnet con piña, pimienta de Jamaica y tomillo. La base perfecta para el pollo jerk.', 9.50, 35, '🌴', ['Picor' => '200.000 SHU', 'Chile' => 'Scotch Bonnet', 'Formato' => '150 ml', 'Origen' => 'Jamaica']],
            ['extremas', 'SAL-007', 'Salsa Ghost Pepper Bhut', 'Picor que crece poco a poco y no se va.', 'Salsa de Bhut Jolokia (chile fantasma) con tomate, cebolla roja y comino. Picor progresivo y muy duradero; dosificar gota a gota.', 11.90, 25, '👻', ['Picor' => '800.000 SHU', 'Chile' => 'Bhut Jolokia', 'Formato' => '100 ml', 'Origen' => 'India']],
            ['extremas', 'SAL-001', 'Salsa Carolina Reaper Inferno', 'Para valientes: más de 1.000.000 SHU.', 'Salsa elaborada con pimiento Carolina Reaper, vinagre de manzana y ajo asado. Unas gotas bastan para transformar cualquier plato.', 12.90, 30, '💀', ['Picor' => '1.000.000+ SHU', 'Chile' => 'Carolina Reaper', 'Formato' => '100 ml', 'Origen' => 'EE. UU.']],
            ['packs', 'SAL-008', 'Pack Iniciación al Picante', 'Tres salsas para subir de nivel poco a poco.', 'Incluye Verde Jalapeño & Lima, Chipotle Ahumado y Habanero Mango en formato de 100 ml. El regalo perfecto para empezar.', 19.90, 20, '🎁', ['Contenido' => '3 × 100 ml', 'Picor' => 'De 3.500 a 150.000 SHU', 'Formato' => 'Caja regalo', 'Origen' => 'Varios']],
            ['packs', 'SAL-009', 'Pack Desafío Extrem', 'Las dos salsas más potentes de la tienda.', 'Ghost Pepper Bhut y Carolina Reaper Inferno en una caja negra con guantes de nitrilo incluidos. Solo para expertos.', 22.90, 15, '☠️', ['Contenido' => '2 × 100 ml', 'Picor' => 'Hasta 1.000.000+ SHU', 'Formato' => 'Caja regalo', 'Origen' => 'Varios']],
        ];

        foreach ($products as [$catSlug, $sku, $name, $short, $desc, $price, $stock, $emoji, $specs]) {
            Product::updateOrCreate(['sku' => $sku], [
                'category_id' => $cat[$catSlug]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'short_description' => $short,
                'description' => $desc,
                'price' => $price,
                'stock' => $stock,
                'emoji' => $emoji,
                'specs' => $specs,
                'active' => true,
            ]);
            
            // 2. Asignar variantes explícitamente a los 2 SKUs
            $chipotle = Product::where('sku', 'SAL-002')->first();
            if ($chipotle) {
                $chipotle->variants()->delete();
                $chipotle->variants()->createMany([
                    ['size' => '50 ml', 'price' => 4.50, 'stock' => 30],
                    ['size' => '100 ml', 'price' => 6.50, 'stock' => 20],
                    ['size' => '200 ml', 'price' => 9.90, 'stock' => 10],
                ]);
            }

            $reaper = Product::where('sku', 'SAL-001')->first();
            if ($reaper) {
                $reaper->variants()->delete();
                $reaper->variants()->createMany([
                    ['size' => '50 ml', 'price' => 8.90, 'stock' => 15],
                    ['size' => '100 ml', 'price' => 12.90, 'stock' => 10],
                    ['size' => '200 ml', 'price' => 18.50, 'stock' => 5],
                ]);
            }

        }

    }
}
