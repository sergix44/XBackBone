<?php


namespace Tests\Feature;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{

    /** @test */
    public function it_loads_the_login_page()
    {
        $this->client->request('GET', '/login');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
