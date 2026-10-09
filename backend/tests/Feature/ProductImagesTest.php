<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function chipotle(): Product
    {
        return Product::where('sku', 'SAL-002')->with('variants')->firstOrFail();
    }

    public function test_every_product_and_variant_has_an_existing_image(): void
    {
        foreach (Product::all() as $product) {
            $this->assertNotNull($product->image, "{$product->sku} sin imagen");
            $this->assertFileExists(public_path($product->image));
        }
        foreach (ProductVariant::all() as $variant) {
            $this->assertNotNull($variant->image);
            $this->assertFileExists(public_path($variant->image));
        }
    }

    public function test_product_page_starts_with_first_variant_and_lists_variant_data(): void
    {
        $product = $this->chipotle();
        $first = $product->variants->first();

        $response = $this->get(route('product.show', $product))->assertOk();

        // Imagen, precio y stock iniciales: los de la primera variante
        $response->assertSee('id="product-image" src="'.$first->imageUrl().'"', false);
        $response->assertSee(number_format($first->price, 2, ',', '.').' €');

        // Cada opción lleva los datos que usa el script para cambiar la imagen, el precio y el stock
        foreach ($product->variants as $variant) {
            $response->assertSee('data-image="'.$variant->imageUrl().'"', false);
            $response->assertSee('data-stock="'.$variant->stock.'"', false);
        }
    }

    public function test_catalog_cards_show_product_images(): void
    {
        $this->get('/catalogo')->assertOk()->assertSee($this->chipotle()->imageUrl(), false);
    }

    public function test_cart_shows_the_chosen_variant_thumbnail(): void
    {
        $product = $this->chipotle();
        $variant = $product->variants->last();

        $this->post(route('cart.add', $product), ['variant_id' => $variant->id]);

        $this->get('/carrito')->assertOk()->assertSee($variant->imageUrl(), false);
    }

    /** Regresión: el carrito enviaba el id de la variante en vez de la clave "producto_variante". */
    public function test_cart_line_with_variant_can_be_updated_and_removed(): void
    {
        $product = $this->chipotle();
        $variant = $product->variants->first();
        $key = "{$product->id}_{$variant->id}";

        $this->post(route('cart.add', $product), ['quantity' => 1, 'variant_id' => $variant->id]);
        $this->get('/carrito')->assertSee(route('cart.update', $key), false);

        $this->patch(route('cart.update', $key), ['quantity' => 3]);
        $this->assertSame(3, session('cart')[$key]['quantity']);

        $this->delete(route('cart.remove', $key));
        $this->assertArrayNotHasKey($key, session('cart'));
    }

    public function test_command_generates_valid_svg_files(): void
    {
        $dir = sys_get_temp_dir().'/piquantum-images-test';
        File::deleteDirectory($dir);

        $this->artisan('piquantum:product-images', ['--path' => $dir])->assertSuccessful();

        $product = $this->chipotle();
        foreach ([$product->defaultImagePath(), $product->variants->first()->defaultImagePath()] as $path) {
            $this->assertFileExists("$dir/$path");
            $this->assertStringStartsWith('<svg', File::get("$dir/$path"));
        }

        File::deleteDirectory($dir);
    }
}
