<?php


namespace Tests\Feature;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{

    /** @test */
    public function it_loads_the_login_page()
    {
        $response = $this->get(route('login.show'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_redirect_to_login_with_no_data()
    {
        $response = $this->post(route('login.show'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login.show'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function it_login_with_correct_data()
    {
        $this->database()->query("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES ('admin@example.com', 'admin', ?, 1, ?)", [password_hash('admin', PASSWORD_DEFAULT), humanRandomString(5)]);

        $response = $this->get(route('login.show'));
        $form = $this->getCrawler($response)
            ->selectButton('Login')
            ->form([
                'username' => 'admin@example.com',
                'password' => 'admin',
            ], 'POST');

        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));

        $response = $this->get(route('home'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_hide_register_by_default()
    {
        $response = $this->get(route('login.show'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('Register', $this->getCrawler($response)->text());
    }

    /** @test */
    public function it_show_register_when_enabled()
    {
        $this->database()->query("INSERT INTO `settings`(`key`, `value`) VALUES ('register_enabled', 'on')");

        $response = $this->get(route('login.show'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Register', $this->getCrawler($response)->text());
    }
}
