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
        $this->assertSame(route('login.show'), $response->getHeader('Location'));
    }

//    /** @test */
//    public function it_login_with_correct_data()
//    {
//        $this->database()->query("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES ('admin@example.com', 'admin', ?, 1, ?)", [password_hash('admin', PASSWORD_DEFAULT), humanRandomString(5)]);
//
//        $loginPage = $this->client->request('GET', route('login.show'));
//        $form = $loginPage->selectButton('Login')->form([
//            'username' => 'admin@example.com',
//            'password' => 'admin',
//        ], 'POST');
//
//        $this->client->submit($form);
//        $this->client->followRedirect();
//        dd($this->client->getResponse());
//
//
//    }
}
