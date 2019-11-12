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
     * @param  Container  $container
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $container)
    {
        $config = $container->get('config');
        $loader = new FilesystemLoader(BASE_DIR.'resources/templates');

        $twig = new Environment($loader, [
            'cache' => BASE_DIR.'resources/cache/twig',
            'autoescape' => 'html',
            'debug' => $config['debug'],
            'auto_reload' => $config['debug'],
        ]);

        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $request = $serverRequestCreator->createServerRequestFromGlobals();

        $twig->addGlobal('config', $config);
        $twig->addGlobal('request', $request);
        $twig->addGlobal('alerts', $container->get('session')->getAlert());
        $twig->addGlobal('session', $container->get('session')->all());
        $twig->addGlobal('current_lang', $container->get('lang')->getLang());
        $twig->addGlobal('PLATFORM_VERSION', PLATFORM_VERSION);

        $twig->addFunction(new TwigFunction('route', 'route'));
        $twig->addFunction(new TwigFunction('lang', 'lang'));
        $twig->addFunction(new TwigFunction('urlFor', 'urlFor'));
        $twig->addFunction(new TwigFunction('asset', 'asset'));
        $twig->addFunction(new TwigFunction('mime2font', 'mime2font'));
        $twig->addFunction(new TwigFunction('queryParams', 'queryParams'));
        $twig->addFunction(new TwigFunction('isDisplayableImage', 'isDisplayableImage'));

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