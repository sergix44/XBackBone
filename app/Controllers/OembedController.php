<?php

namespace App\Controllers;

use League\Flysystem\FileNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class OembedController extends Controller
{
    private $width = 720;
    private $height = 480;

    /**
     * Provides the oEmbed response for a given media sent as 'url' in the query string
     * @see https://oembed.com/
     *
     * @param Request $request
     * @param Response $response
     * @param string $userCode
     * @param string $mediaCode
     *
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     * @throws FileNotFoundException
     *
     */
    public function oembed(Request $request, Response $response): Response
    {
        if ($this->getSetting('image_embeds') !== 'on') {
            throw new HttpUnauthorizedException($request);
        }

        $query = $request->getQueryParams();
        if (empty($query['url']) || ($path = parse_url(urldecode($query['url']), PHP_URL_PATH)) === null) {
            throw new HttpNotFoundException($request);
        }

        // The url of the media should end with (userCode)/(mediaCode).
        if (!preg_match_all('/\/([0-9a-zA-Z]+)\/([0-9a-zA-Z.]+)$/', $path, $matches, PREG_SET_ORDER, 0)) {
            throw new HttpNotFoundException($request);
        }

        $media = $this->getMedia($matches[0][1], $matches[0][2]);
        if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get(
            'admin',
            false
        ))) {
            throw new HttpNotFoundException($request);
        }
        $media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);

        $url = route('public.raw', ['userCode' => $media->user_code, 'mediaCode' => $media->code, 'ext' => $media->extension]);

        $this->setOEmbedSizes($query);

        // Providing default oEmbed return.
        $oembedData = [
            'version' => '1.0',
            'type' => 'link',
            'title' => $media->filename,
            'url' => $url,
            'width' => $this->width,
            'height' => $this->height,
        ];

        $mime = $this->storage->getMimetype($media->storage_path);
        $type = explode('/', $mime)[0];
        if ($type === 'image') {
            $oembedData = array_merge($oembedData, [
                'type' => 'photo',
            ]);
        } elseif ($type === 'video') {
            $oembedData = array_merge($oembedData, [
                'type' => 'video',
                'html' => "<iframe src='{$url}' width='{$this->width}' height='{$this->height}'></iframe>",
            ]);
        }
        return json($response, $oembedData);
    }

    /**
     * @param array $query
     * @return void
     */
    private function setOEmbedSizes(array $query): void
    {
        if (!empty($query['maxwidth']) && ($maxwidth = intval($query['maxwidth'])) > 0) {
            $this->width = min($this->width, $maxwidth);
        }
        if (!empty($query['maxheight']) && ($maxheight = intval($query['maxheight'])) > 0) {
            $this->height = min($this->height, $maxheight);
        }
    }
}
