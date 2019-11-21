<?php

namespace App\Web;

use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * View constructor.
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Response   $response
     * @param string     $view
     * @param array|null $parameters
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return Response
     */
    public function render(Response $response, string $view, ?array $parameters = [])
    {
        $body = $this->twig->render($view, $parameters);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * @param string     $view
     * @param array|null $parameters
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return string
     */
    public function string(string $view, ?array $parameters = [])
    {
        return $this->twig->render($view, $parameters);
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
