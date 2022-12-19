<?php


namespace Tests\Feature\Auth;

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
        $this->createAdminUser();

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
        $this->updateSetting('register_enabled', 'on');

        $response = $this->get(route('login.show'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Register', $this->getCrawler($response)->text());
    }

    /** @test */
    public function it_redirect_to_home_if_logged_in()
    {
        $this->createAdminUser();

        $response = $this->get(route('login.show'));
        $form = $this->getCrawler($response)
            ->selectButton('Login')
            ->form([
                'username' => 'admin@example.com',
                'password' => 'admin',
                'remember' => 'on',
            ], 'POST');

        $this->submitForm($form);

        $response = $this->get(route('login'));
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function it_redirects_to()
    {
        $this->app->getContainer()->get('session')->set('redirectTo', route('profile'));
        $this->createAdminUser();

        $response = $this->get(route('login.show'));
        $form = $this->getCrawler($response)
            ->selectButton('Login')
            ->form([
                'username' => 'admin@example.com',
                'password' => 'admin',
                'remember' => 'on',
            ], 'POST');

        $redirect = $this->submitForm($form)->getHeaderLine('Location');
        $this->assertSame(route('profile'), $redirect);
    }

    /** @test */
    public function it_logout_the_user()
    {
        $this->createAdminUser();

        $response = $this->get(route('login.show'));
        $form = $this->getCrawler($response)
            ->selectButton('Login')
            ->form([
                'username' => 'admin@example.com',
                'password' => 'admin',
                'remember' => 'on',
            ], 'POST');

        $this->submitForm($form);
        $this->assertSame(200, $response->getStatusCode());

        $response = $this->post(route('logout'));
        $this->assertSame(302, $response->getStatusCode());

        $response = $this->get(route('home'));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login.show'), $response->getHeaderLine('Location'));

        $this->assertFalse($this->app->getContainer()->get('session')->get('logged'));
    }
}
