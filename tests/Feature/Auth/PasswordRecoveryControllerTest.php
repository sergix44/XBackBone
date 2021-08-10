<?php


namespace Tests\Feature\Auth;

use App\Web\Mail;
use Tests\TestCase;

class PasswordRecoveryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function it_loads_the_password_recovery_page()
    {
        $response = $this->get(route('recover'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_redirect_if_there_are_no_data()
    {
        $response = $this->post(route('recover.mail'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('recover'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function it_set_the_recover_token_with_correct_data()
    {
        $this->createAdminUser();

        $result = $this->database()->query('SELECT * FROM users WHERE email = "admin@example.com"')->fetch();
        $this->assertNull($result->reset_token);

        $response = $this->get(route('recover'));
        $form = $this->getCrawler($response)
            ->selectButton('Recover password')
            ->form(['email' => 'admin@example.com'], 'POST');

        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('recover'), $response->getHeaderLine('Location'));

        $result = $this->database()->query('SELECT * FROM users WHERE email = "admin@example.com"')->fetch();
        $this->assertNotNull($result->reset_token);
    }

    /** @test */
    public function recover_form_give_404_if_user_not_found()
    {
        $response = $this->get(route('recover.password.view', [
            'resetToken' => 'not-the-token',
        ]));

        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function it_show_the_recover_form_if_the_user_token_exists()
    {
        $this->createUser([
            'reset_token' => 'the-token',
        ]);

        $response = $this->get(route('recover.password.view', [
            'resetToken' => 'the-token',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_reset_the_password_if_user_has_reset_token()
    {
        $this->createUser([
            'reset_token' => 'the-token',
        ]);

        $response = $this->get(route('recover.password.view', [
            'resetToken' => 'the-token',
        ]));

        $form = $this->getCrawler($response)
            ->selectButton('Recover password')
            ->form([
                'password' => 'new-password',
                'password_repeat' => 'new-password'
            ], 'POST');

        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login.show'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function it_throws_404_if_the_token_is_wrong()
    {
        $this->createUser([
            'reset_token' => 'the-token',
        ]);

        $response = $this->get(route('recover.password.view', [
            'resetToken' => 'not-the-token',
        ]));

        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function it_give_an_error_if_the_password_is_not_the_same()
    {
        $this->createUser([
            'reset_token' => 'the-token',
        ]);

        $response = $this->get(route('recover.password.view', [
            'resetToken' => 'the-token',
        ]));

        $form = $this->getCrawler($response)
            ->selectButton('Recover password')
            ->form([
                'password' => 'new-password',
                'password_repeat' => 'not-password'
            ], 'POST');

        $response = $this->submitForm($form);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('recover.password.view', [
            'resetToken' => 'the-token',
        ]), $response->getHeaderLine('Location'));
    }
}
