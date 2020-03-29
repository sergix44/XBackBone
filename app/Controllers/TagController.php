<?php


namespace App\Controllers;

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

        $tag = $this->database->query('SELECT * FROM `tags` WHERE `name` = ? LIMIT 1', param($request, 'tag'))->fetch();

        $connectedIds = $this->database->query('SELECT `tag_id` FROM `uploads_tags` WHERE `upload_id` = ?', [
            param($request, 'mediaId'),
        ])->fetchAll(PDO::FETCH_COLUMN, 0);

        if (!$tag && count($connectedIds) < self::PER_MEDIA_LIMIT) {
            $this->database->query('INSERT INTO `tags`(`name`) VALUES (?)', param($request, 'tag'));

            $tagId = $this->database->getPdo()->lastInsertId();

            $this->database->query('INSERT INTO `uploads_tags`(`upload_id`, `tag_id`) VALUES (?, ?)', [
                param($request, 'mediaId'),
                $tagId,
            ]);

            return json($response, [
                'limitReached' => false,
                'tagId' => $tagId,
            ]);
        }

        if (count($connectedIds) >= self::PER_MEDIA_LIMIT || in_array($tag->id, $connectedIds)) {
            return json($response, [
                'limitReached' => true,
                'tagId' => null,
            ]);
        }

        $this->database->query('INSERT INTO `uploads_tags`(`upload_id`, `tag_id`) VALUES (?, ?)', [
            param($request, 'mediaId'),
            $tag->id,
        ]);

        return json($response, [
            'limitReached' => false,
            'tagId' => $tag->id,
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
        $validator = $this->validateTag($request)
        ->removeRule('tag.notEmpty');

        if ($validator->fails()) {
            throw new HttpBadRequestException($request);
        }

        $tag = $this->database->query('SELECT * FROM `tags` WHERE `id` = ? LIMIT 1', param($request, 'tagId'))->fetch();

        if (!$tag) {
            throw new HttpNotFoundException($request);
        }

        $this->database->query('DELETE FROM `uploads_tags` WHERE `upload_id` = ? AND `tag_id` = ?', [
            param($request, 'mediaId'),
            $tag->id,
        ]);

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `uploads_tags` WHERE `tag_id` = ?', $tag->id)->fetch()->count == 0) {
            $this->database->query('DELETE FROM `tags` WHERE `id` = ? ', $tag->id);
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
