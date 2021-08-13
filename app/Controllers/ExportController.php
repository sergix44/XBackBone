<?php


namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use League\Flysystem\FileNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class ExportController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int|null  $id
     * @return Response
     * @throws \ZipStream\Exception\OverflowException
     */
    public function downloadData(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        $medias = $this->database->query('SELECT `uploads`.`filename`, `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $user->id);

        $this->logger->info("User $user->id, $user->name, exporting data...");

        set_time_limit(0);
        ob_end_clean();

        $options = new Archive();
        $options->setSendHttpHeaders(true);

        $zip = new ZipStream($user->username.'-'.time().'-export.zip', $options);

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            try {
                $zip->addFileFromStream($media->filename, $filesystem->readStream($media->storage_path));
            } catch (FileNotFoundException $e) {
                $this->logger->error('Cannot export file', ['exception' => $e]);
            }
        }
        $zip->finish();
        exit(0);
    }
}
