<?php


namespace Tests;

use App\Database\Migrator;
use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Response;

class Client extends AbstractBrowser
{
    protected function doRequest($request)
    {
        /** @var \Slim\App $app */
        $app = require BASE_DIR.'bootstrap/app.php';

        $migrator = new Migrator($app->getContainer()->get('database'), BASE_DIR.'resources/schemas');
        $migrator->migrate();

        $response = $app->handle(new ServerRequest($request->getMethod(), $request->getUri(), [], $request->getContent()));

        return new Response($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());
    }
}
