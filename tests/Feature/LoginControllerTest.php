<?php


namespace Tests\Feature;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{

    /** @test */
    public function it_loads_the_login_page()
    {
        $response = $this->get('/login');
        $this->assertSame(200, $response->getStatusCode());
    }
}
