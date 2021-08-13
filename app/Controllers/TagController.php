<?php


namespace App\Controllers;

use App\Database\Repositories\TagRepository;
use App\Web\ValidationHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class TagController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpBadRequestException
     */
    public function addTag(Request $request, Response $response): Response
    {
        $validator = $this->validateTag($request)->failIf(empty(param($request, 'tag')));

        if ($validator->fails()) {
            throw new HttpBadRequestException($request);
        }

        [$id, $limit] = make(TagRepository::class)->addTag(param($request, 'tag'), param($request, 'mediaId'));

        $this->logger->info("Tag added $id.");

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
        $validator = $this->validateTag($request);

        if ($validator->fails()) {
            throw new HttpBadRequestException($request);
        }

        $result = make(TagRepository::class)->removeTag(param($request, 'tagId'), param($request, 'mediaId'));

        if ($result === null) {
            throw new HttpNotFoundException($request);
        }

        $this->logger->info("Tag removed ".param($request, 'tagId').', from media '.param($request, 'mediaId'));

        return json($response, [
            'deleted' => $result,
        ]);
    }

    /**
     * @param  Request  $request
     * @return ValidationHelper
     */
    protected function validateTag(Request $request)
    {
        return make(ValidationHelper::class)
            ->failIf(empty(param($request, 'mediaId')))
            ->failIf($this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `id` = ?', param($request, 'mediaId'))->fetch()->count == 0)
            ->failIf(!$this->session->get('admin', false) && $this->database->query('SELECT `user_id` FROM `uploads` WHERE `id` = ? LIMIT 1', param($request, 'mediaId'))->fetch()->user_id != $this->session->get('user_id'));
    }
}
