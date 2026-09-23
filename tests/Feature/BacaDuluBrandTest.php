<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacaDuluBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_png_is_used_in_public_and_login_pages(): void
    {
        foreach ([route('home'), route('login'), route('admin.login')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('assets/bacadulu-logo.png', false)
                ->assertDontSee('assets/bacadulu-logo.jpeg', false);
        }
    }
}
