<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_public_and_explains_the_prototype(): void
    {
        $this->get('/privacidad')
            ->assertOk()
            ->assertSee('Privacidad y datos del prototipo')
            ->assertSee('prototipo académico');
    }

    public function test_layout_links_to_privacy_and_has_skip_link(): void
    {
        $this->get('/soporte')
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee('Saltar al contenido');
    }

    public function test_unknown_url_shows_custom_404(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('Página no encontrada');
    }

    public function test_robots_txt_blocks_private_areas(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach (['/admin', '/mi-cuenta', '/checkout'] as $path) {
            $this->assertStringContainsString("Disallow: $path", $robots);
        }
    }
}
