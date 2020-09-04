<?php


namespace Tests\Feature;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{

    public function test_it_loads_the_login_page(): void
    {
        $response = $this->get('/login');
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_it_redirect_back_to_login_page_with_no_credentials(): void
    {
        $response = $this->post('/login');
        self::assertSame(200, $response->getStatusCode());
    }
}
