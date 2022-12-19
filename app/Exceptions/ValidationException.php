<?php


namespace App\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class ValidationException extends Exception
{
    /**
     * @var Response
     */
    private $response;

    public function __construct(Response $response, $message = "", Throwable $previous = null)
    {
        parent::__construct($message, $response->getStatusCode(), $previous);
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function response(): Response
    {
        return $this->response;
    }
}
