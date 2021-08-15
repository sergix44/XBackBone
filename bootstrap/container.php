<?php

use App\Database\DB;
use App\Web\Lang;
use Aws\S3\S3Client;
use function DI\factory;
use function DI\get;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use League\Flysystem\Filesystem;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface as Container;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

return [
    Logger::class => factory(function () {
        $logger = new Logger('app');

        $streamHandler = new RotatingFileHandler(BASE_DIR.'logs/log.txt', 10, Logger::DEBUG);

        $lineFormatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", 'Y-m-d H:i:s');
        $lineFormatter->includeStacktraces(true);

        $streamHandler->setFormatter($lineFormatter);

        $logger->pushHandler($streamHandler);

        return $logger;
    }),
    'logger' => get(Logger::class),

    DB::class => factory(function (Container $container) {
        $config = $container->get('config');

        return new DB(dsnFromConfig($config), $config['db']['username'], $config['db']['password']);
    }),
    'database' => get(DB::class),

    Filesystem::class => factory(function (Container $container) {
        $config = $container->get('config');
        switch ($config['storage']['driver']) {
            case 'local':
                return new Filesystem(new Local($config['storage']['path']));
            case 's3':
                $client = new S3Client([
                    'credentials' => [
                        'key' => $config['storage']['key'],
                        'secret' => $config['storage']['secret'],
                    ],
                    'region' => $config['storage']['region'],
                    'endpoint'  => $config['storage']['endpoint'],
                    'version' => 'latest',
                    'use_path_style_endpoint' => $config['storage']['use_path_style_endpoint'] ?? false,
                    '@http' => ['stream' => true],
                ]);

                return new Filesystem(new AwsS3Adapter($client, $config['storage']['bucket'], $config['storage']['path']));
            case 'dropbox':
                $client = new DropboxClient($config['storage']['token']);

                return new Filesystem(new DropboxAdapter($client), ['case_sensitive' => false]);
            case 'ftp':
                return new Filesystem(new FtpAdapter([
                    'host' => $config['storage']['host'],
                    'username' => $config['storage']['username'],
                    'password' => $config['storage']['password'],
                    'port' => $config['storage']['port'],
                    'root' => $config['storage']['path'],
                    'passive' => $config['storage']['passive'],
                    'ssl' => $config['storage']['ssl'],
                    'timeout' => 30,
                ]));
            case 'google-cloud':
                $client = new StorageClient([
                    'projectId' => $config['storage']['project_id'],
                    'keyFilePath' => $config['storage']['key_path'],
                ]);

                return new Filesystem(new GoogleStorageAdapter($client, $client->bucket($config['storage']['bucket'])));
            case 'azure':
                $client = BlobRestProxy::createBlobService(
                    sprintf(
                        'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;',
                        $config['storage']['account_name'],
                        $config['storage']['account_key']
                    )
                );

                return new Filesystem(new AzureBlobStorageAdapter($client, $config['storage']['container_name']));
            default:
                throw new InvalidArgumentException('The driver specified is not supported.');
        }
    }),
    'storage' => get(Filesystem::class),

    Lang::class => factory(function () {
        return Lang::build(Lang::recognize(), BASE_DIR.'resources/lang/');
    }),
    'lang' => get(Lang::class),
];
