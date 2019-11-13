<?php


namespace App\Web;


use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\ServerRequestCreatorFactory;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class View
{
    /**
     * @var Environment
     */
    private $twig;


    /**
     * View constructor.
     * @param  Environment  $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param  Response  $response
     * @param  string  $view
     * @param  array|null  $parameters
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(Response $response, string $view, ?array $parameters = [])
    {
        $body = $this->twig->render($view, $parameters);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * @param  string  $view
     * @param  array|null  $parameters
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function string(string $view, ?array $parameters = [])
    {
        return $this->twig->render($view, $parameters);
    }

}