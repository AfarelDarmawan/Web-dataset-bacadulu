<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacaDuluHomeUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_local_assets_and_both_catalog_paths(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('bacadulu-home.css')
            ->assertSee('bacadulu-home.js')
            ->assertSee('Temukan datanya.')
            ->assertSee('PERUSAHAAN & ESG', false)
            ->assertSee('BPS & STATISTIK WILAYAH', false)
            ->assertSee(route('datasets.index', ['scope' => 'corporate']), false)
            ->assertSee(route('datasets.index', ['scope' => 'regional']), false)
            ->assertSee('Belum ada variabel terbit.')
            ->assertDontSee('<style', false);
    }
}
