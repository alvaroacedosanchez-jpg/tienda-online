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
        User::updateOrCreate(
            ['email' => 'admin@piquantum.test'],
            ['name' => 'Administrador de prueba', 'password' => 'admin1234', 'is_admin' => true],
        );

        // Clientes de prueba
        foreach ([
            ['Laura Prueba', 'laura@example.com', 'Calle Ficticia 1', 'Madrid', '28001'],
            ['Carlos Demo', 'carlos@example.com', 'Avenida de Prueba 22', 'Sevilla', '41001'],
            ['Marta Test', 'marta@example.com', 'Plaza Inventada 5', 'Valencia', '46001'],
        ] as [$name, $email, $address, $city, $zip]) {
            Customer::firstOrCreate(['email' => $email], [
                'name' => $name, 'address' => $address, 'city' => $city, 'postal_code' => $zip,
                'phone' => '600000000',
            ]);
        }

        $categories = [
            'audio' => ['Audio', 'Auriculares y altavoces para trabajar y disfrutar', '🎧'],
            'teclados-y-ratones' => ['Teclados y ratones', 'Periféricos de entrada ergonómicos', '⌨️'],
            'escritorio' => ['Escritorio', 'Accesorios para un puesto de trabajo cómodo', '🖥️'],
        ];
        $cat = [];
        foreach ($categories as $slug => [$name, $desc]) {
            $cat[$slug] = Category::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $desc]);
        }

        $products = [
            ['audio', 'AUD-001', 'Auriculares inalámbricos Nova ANC', 'Cancelación activa de ruido y 30 h de batería.', 'Auriculares over-ear con Bluetooth 5.3, cancelación activa de ruido híbrida, micrófono con reducción de ruido para videollamadas y carga rápida USB-C (10 min = 5 h de uso).', 89.90, 25, '🎧', ['Conexión' => 'Bluetooth 5.3', 'Batería' => '30 h', 'Peso' => '255 g', 'Garantía' => '2 años']],
            ['audio', 'AUD-002', 'Altavoz portátil Pulse Mini', 'Resistente al agua IPX7, 12 h de autonomía.', 'Altavoz compacto con sonido 360°, resistencia al agua IPX7, correa de transporte y emparejamiento estéreo con un segundo altavoz.', 39.90, 40, '🔊', ['Conexión' => 'Bluetooth 5.2', 'Batería' => '12 h', 'Resistencia' => 'IPX7', 'Garantía' => '2 años']],
            ['audio', 'AUD-003', 'Auriculares de diadema con cable Studio', 'Sonido fiel para trabajo y edición, conector jack 3,5 mm.', 'Auriculares cerrados con drivers de 40 mm, almohadillas de espuma viscoelástica y cable desmontable de 1,5 m.', 54.50, 18, '🎵', ['Conexión' => 'Jack 3,5 mm', 'Driver' => '40 mm', 'Peso' => '280 g', 'Garantía' => '2 años']],
            ['teclados-y-ratones', 'TEC-001', 'Teclado mecánico compacto K65', 'Formato 65 %, switches lineales y retroiluminación.', 'Teclado mecánico hot-swap con switches lineales, retroiluminación blanca, conexión USB-C y Bluetooth, y distribución española ISO.', 74.00, 30, '⌨️', ['Formato' => '65 %', 'Switches' => 'Lineales hot-swap', 'Conexión' => 'USB-C / Bluetooth', 'Garantía' => '2 años']],
            ['teclados-y-ratones', 'TEC-002', 'Ratón ergonómico vertical Flow', 'Reduce la tensión de la muñeca en jornadas largas.', 'Ratón vertical inalámbrico de 2,4 GHz con sensor óptico de 4000 DPI, 6 botones programables y hasta 6 meses de batería.', 34.95, 50, '🖱️', ['Sensor' => '4000 DPI', 'Botones' => '6', 'Conexión' => '2,4 GHz', 'Garantía' => '2 años']],
            ['teclados-y-ratones', 'TEC-003', 'Teclado inalámbrico silencioso Quiet 3', 'Teclas de perfil bajo y pulsación silenciosa.', 'Teclado de tamaño completo con teclado numérico, pulsación silenciosa, receptor USB y 2 años de autonomía con pilas AAA.', 29.90, 45, '⌨️', ['Formato' => 'Completo', 'Conexión' => '2,4 GHz', 'Autonomía' => '24 meses', 'Garantía' => '2 años']],
            ['escritorio', 'ESC-001', 'Soporte de portátil de aluminio Elevate', 'Ajustable en altura, compatible con portátiles de 11 a 17".', 'Soporte plegable de aluminio con 6 niveles de altura y base antideslizante; mejora la ventilación y la postura.', 27.90, 60, '💻', ['Material' => 'Aluminio', 'Compatibilidad' => '11–17"', 'Peso' => '480 g', 'Garantía' => '2 años']],
            ['escritorio', 'ESC-002', 'Hub USB-C 7 en 1 Dock', 'HDMI 4K, 3 USB-A, lector SD y carga de 100 W.', 'Adaptador multipuerto con HDMI 4K@30, 3 puertos USB-A 3.0, lector SD/microSD y paso de carga USB-C PD de hasta 100 W.', 44.90, 35, '🔌', ['Puertos' => '7', 'Vídeo' => 'HDMI 4K@30', 'Carga' => 'PD 100 W', 'Garantía' => '2 años']],
            ['escritorio', 'ESC-003', 'Lámpara LED de escritorio Lumen', 'Luz regulable en brillo y temperatura de color.', 'Lámpara con brazo articulado, 5 temperaturas de color, 5 niveles de brillo, temporizador y puerto de carga USB.', 32.50, 22, '💡', ['Potencia' => '10 W', 'Temperatura' => '2700–6500 K', 'Control' => 'Táctil', 'Garantía' => '2 años']],
            ['escritorio', 'ESC-004', 'Alfombrilla XL de escritorio Desk Pad', 'Superficie suave de 90×40 cm con base de goma.', 'Alfombrilla extendida de microfibra con bordes cosidos y base antideslizante; cubre teclado y ratón.', 17.90, 80, '🟫', ['Tamaño' => '90 × 40 cm', 'Grosor' => '3 mm', 'Material' => 'Microfibra y goma', 'Garantía' => '1 año']],
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
        }
    }
}
