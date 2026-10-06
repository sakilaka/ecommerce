<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontCatalogPageTest extends TestCase
{
    public function test_product_catalog_page_renders_the_vue_shell(): void
    {
        $this->get('/products?q=phone')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_product_detail_page_renders_the_vue_shell(): void
    {
        $this->get('/products/premium-phone')
            ->assertOk()
            ->assertViewIs('welcome');
    }
}
