<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dashboard del back-office: estados y cifras coherentes con los estados reales de los pedidos. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@piquantum.test')->firstOrFail();
    }

    public function test_seeded_orders_only_use_valid_statuses(): void
    {
        $invalid = Order::whereNotIn('status', array_keys(Order::STATUS_LABELS))->pluck('status', 'reference');

        $this->assertEmpty($invalid, 'Estados que no existen en la tienda: '.$invalid->toJson());
    }

    public function test_recent_orders_show_the_status_label(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        foreach (Order::latest()->take(5)->get() as $order) {
            $response->assertSee('badge-'.$order->status, false)->assertSee($order->statusLabel());
        }
    }

    public function test_revenue_and_pending_counts_use_real_statuses(): void
    {
        $paid = Order::whereIn('status', Order::PAID_STATUSES)->sum('total');
        $pending = Order::whereIn('status', Order::IN_PROGRESS)->count();
        $this->assertGreaterThan(0, $paid, 'Los pedidos de demostración cobrados deben contar como ingresos');

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertViewHas('totalRevenue', fn ($value) => (float) $value === (float) $paid)
            ->assertViewHas('pendingOrders', $pending);
    }
}
