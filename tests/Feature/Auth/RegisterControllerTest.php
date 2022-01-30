<?php


namespace Tests\Feature\Auth;

use App\Web\Mail;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->updateSetting('register_enabled', 'on');
        Mail::fake();
    }

    /** @test */
    public function it_loads_the_register_page()
    {
        $response = $this->get(route('register'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_give_404_if_registration_are_off()
    {
        $this->updateSetting('register_enabled', 'off');

        $response = $this->get(route('register'));
        $this->assertSame(404, $response->getStatusCode());

        $response = $this->post(route('register'));
        $this->assertSame(404, $response->getStatusCode());
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
            ], 'POST');
        $this->submitForm($form);

        $response = $this->get(route('register'));
        $this->assertSame(302, $response->getStatusCode());

        $response = $this->post(route('register'));
        $this->assertSame(302, $response->getStatusCode());

        $response = $this->get(route('activate', [
            'activateToken' => 'token',
        ]));
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function it_register_a_new_user()
    {
        $response = $this->get(route('register'));
        $form = $this->getCrawler($response)
            ->selectButton('Register')
            ->form([
                'email' => 'mario@example.com',
                'username' => 'Super Mario',
                'password' => 'user',
            ], 'POST');
        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));

        $result = $this->database()->query('SELECT * FROM users WHERE email = "mario@example.com"')->fetch();
        $this->assertIsObject($result);
    }

    /** @test */
    public function it_activate_a_new_user()
    {
        $activateToken = bin2hex(random_bytes(16));

        $userId = $this->createUser([
            'active' => 0,
            'activate_token' => $activateToken,
        ]);

        $response = $this->get(route('activate', [
            'activateToken' => $activateToken,
        ]));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));

        $result = $this->database()->query('SELECT * FROM users WHERE id = ?', $userId)->fetch();
        $this->assertSame(1, (int) $result->active);
        $this->assertNull($result->activate_token);
    }

    /** @test */
    public function it_cant_activate_if_user_not_exists()
    {
        $response = $this->get(route('activate', [
            'activateToken' => 'token',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function it_fails_with_no_data()
    {
        $response = $this->get(route('register'));
        $form = $this->getCrawler($response)
            ->selectButton('Register')
            ->form([], 'POST');
        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('register'), $response->getHeaderLine('Location'));
    }
}
