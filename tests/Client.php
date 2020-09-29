<?php


namespace Tests;

use GuzzleHttp\Psr7\ServerRequest;
use Slim\App;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Response;

class Client extends AbstractBrowser
{
    private $app;

    public function __construct(App $app, $server = [], History $history = null, CookieJar $cookieJar = null)
    {
        parent::__construct($server, $history, $cookieJar);
        $this->app = $app;
    }

    protected function doRequest($request)
    {
        $response = $this->app->handle(new ServerRequest($request->getMethod(), $request->getUri(), [], $request->getContent()));

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }
        return new Response($body->getContents(), $response->getStatusCode(), $response->getHeaders());
    }
}
