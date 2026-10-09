<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Genera las ilustraciones SVG de los productos y de sus variantes de tamaño
 * a partir de la plantilla resources/views/images/bottle.blade.php.
 * Los archivos resultantes se guardan en el repositorio (public/images/products).
 */
#[Signature('piquantum:product-images {--path= : Carpeta base de salida (por defecto, public/)}')]
#[Description('Genera las ilustraciones SVG de productos y variantes')]
class GenerateProductImages extends Command
{
    /** Datos de presentación por SKU: nombre corto, subtítulo, color de la salsa, picor (0-5), formato y botes del pack. */
    private const STYLES = [
        'SAL-001' => ['REAPER', 'Carolina Reaper', '#6b0f22', 5, '100 ml'],
        'SAL-002' => ['CHIPOTLE', 'Ahumada', '#8a3b17', 1, '200 ml'],
        'SAL-003' => ['VERDE', 'Jalapeño y lima', '#4f8a2b', 1, '200 ml'],
        'SAL-004' => ['SRIRACHA', 'Artesana', '#c8261b', 1, '250 ml'],
        'SAL-005' => ['HABANERO', 'Mango', '#e8780f', 3, '150 ml'],
        'SAL-006' => ['CARIBE', 'Scotch Bonnet', '#d9a400', 3, '150 ml'],
        'SAL-007' => ['GHOST', 'Bhut Jolokia', '#9b1c31', 4, '100 ml'],
        'SAL-008' => ['INICIACIÓN', '3 salsas · 100 ml', '#2f6f4f', 0, null, ['#4f8a2b', '#8a3b17', '#e8780f']],
        'SAL-009' => ['DESAFÍO', '2 salsas extremas', '#1d2433', 0, null, ['#9b1c31', '#6b0f22']],
    ];

    /** Tamaño relativo del bote según su capacidad. */
    private const SCALES = [50 => 0.72, 100 => 0.84, 150 => 0.92, 200 => 1.0, 250 => 1.0];

    public function handle(): int
    {
        $base = rtrim($this->option('path') ?: public_path(), '/');
        $count = 0;

        foreach (Product::with('variants')->orderBy('sku')->get() as $product) {
            [$name, $subtitle, $color, $heat, $format, $pack] = (self::STYLES[$product->sku] ?? [
                mb_strtoupper(mb_substr($product->name, 0, 10)), '', '#667085', 0, null,
            ]) + [5 => null];

            // Imagen principal del producto
            $this->write($base, $product->defaultImagePath(), [
                'title' => $product->name,
                'name' => $name, 'subtitle' => $subtitle, 'color' => $color, 'heat' => $heat,
                'size' => $format, 'scale' => $this->scaleFor($format), 'pack' => $pack,
            ]);
            $count++;

            // Una imagen por variante: mismo bote, del tamaño de la variante
            foreach ($product->variants as $variant) {
                $this->write($base, $variant->defaultImagePath(), [
                    'title' => "{$product->name} ({$variant->size})",
                    'name' => $name, 'subtitle' => $subtitle, 'color' => $color, 'heat' => $heat,
                    'size' => $variant->size, 'scale' => $this->scaleFor($variant->size), 'pack' => null,
                ]);
                $count++;
            }
        }

        $this->info("{$count} imágenes generadas en {$base}/images/products");

        return self::SUCCESS;
    }

    private function scaleFor(?string $size): float
    {
        $ml = (int) $size;

        return self::SCALES[$ml] ?? 1.0;
    }

    private function write(string $base, string $relativePath, array $data): void
    {
        $file = $base.'/'.$relativePath;
        File::ensureDirectoryExists(dirname($file));
        File::put($file, trim(view('images.bottle', $data)->render())."\n");
    }
}
