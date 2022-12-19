<?php


namespace Tests;

use App\Database\DB;
use App\Database\Migrator;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

trait WithApplication
{
    /** @var \Slim\App $app */
    protected $app;

    public function createApplication()
    {
        $this->app = require BASE_DIR . 'bootstrap/app.php';

        $migrator = new Migrator($this->app->getContainer()->get('database'), BASE_DIR . 'resources/schemas');
        $migrator->migrate();

        return $this->app;
    }

    /**
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param array $headers
     * @param null $body
     * @param array $cookies
     * @param array $files
     * @return ResponseInterface
     */
    public function request($method, $uri, $parameters = [], $headers = [], $body = null, $cookies = [], $files = [])
    {
        $request = (new ServerRequest($method, $uri, $headers, $body))
            ->withParsedBody($parameters)
            ->withQueryParams($parameters)
            ->withUploadedFiles(ServerRequest::normalizeFiles($files))
            ->withCookieParams($cookies);

        $response = $this->app->handle($request);

        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        return $response;
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $headers
     * @return ResponseInterface
     */
    public function get(string $uri, array $parameters = [], $cookies = [], $headers = [], $files = [])
    {
        return $this->request('GET', $uri, $parameters, $headers, null, $cookies, $files);
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $headers
     * @param array $files
     * @return ResponseInterface
     */
    public function post(string $uri, array $parameters = [], $cookies = [], $headers = [], $files = [])
    {
        return $this->request('POST', $uri, $parameters, $headers, http_build_query($parameters), $cookies, $files);
    }

    /**
     * @param Form $form
     * @param array $headers
     * @param array $cookies
     * @return ResponseInterface
     */
    public function submitForm(Form $form, $headers = [], $cookies = [])
    {
        return $this->request($form->getMethod(), $form->getUri(), $form->getValues(), $headers, http_build_query($form->getValues()), $cookies, $form->getFiles());
    }

    /**
     * @return DB
     */
    public function database()
    {
        return $this->app->getContainer()->get('database');
    }

    /**
     * @param ResponseInterface $response
     * @return Crawler
     */
    public function getCrawler($response)
    {
        return new Crawler($response->getBody()->getContents());
    }
}
