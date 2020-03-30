<?php


namespace App\Controllers;

use App\Database\Queries\TagQuery;
use App\Web\ValidationChecker;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class TagController extends Controller
{
    const PER_MEDIA_LIMIT = 10;

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpBadRequestException
     */
    public function addTag(Request $request, Response $response): Response
    {
        $validator = $this->validateTag($request);

        if ($validator->fails()) {
            throw new HttpBadRequestException($request);
        }

        [$id, $limit] = make(TagQuery::class)->addTag(param($request, 'tag'), param($request, 'mediaId'));

        return json($response, [
            'limitReached' => $limit,
            'tagId' => $id,
            'href' => queryParams(['tag' => $id]),
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpBadRequestException
     * @throws HttpNotFoundException
     */
    public function removeTag(Request $request, Response $response): Response
    {
        $validator = $this->validateTag($request)->removeRule('tag.notEmpty');

        if ($validator->fails()) {
            throw new HttpBadRequestException($request);
        }

        $result = make(TagQuery::class)->removeTag(param($request, 'tagId'), param($request, 'mediaId'));

        if (!$result) {
            throw new HttpNotFoundException($request);
        }

        return $response;
    }

    protected function validateTag(Request $request)
    {
        return ValidationChecker::make()
            ->rules([
                'tag.notEmpty' => !empty(param($request, 'tag')),
                'mediaId.notEmpty' => !empty(param($request, 'mediaId')),
                'media.exists' => $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `id` = ?', param($request, 'mediaId'))->fetch()->count > 0,
                'sameUserOrAdmin' => $this->session->get('admin', false) || $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', param($request, 'mediaId'))->fetch()->user_id === $this->session->get('user_id'),
            ]);
    }
}
