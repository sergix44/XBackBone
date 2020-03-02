<?php


namespace App\Validators;

use App\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait ValidateUser
{

    /**
     * Partially validate a manager user request
     *
     * @param  Request  $request
     * @param  Response  $response
     * @param $routeOnFail
     * @return bool
     * @throws ValidationException
     */
    protected function validateUser(Request $request, Response $response, $routeOnFail)
    {
        if (param($request, 'email') === null && !filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL)) {
            $this->session->alert(lang('email_required'), 'danger');

            throw new ValidationException(redirect($response, $routeOnFail));
        }

        if (param($request, 'username') === null) {
            $this->session->alert(lang('username_required'), 'danger');

            throw new ValidationException(redirect($response, $routeOnFail));
        }

        if (param($request, 'password') === null) {
            $this->session->alert(lang('password_required'), 'danger');

            throw new ValidationException(redirect($response, $routeOnFail));
        }

        return true;
    }
}