<?php

use App\Database\DB;
use App\Web\Lang;
use Aws\S3\S3Client;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Adapter;
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
        $driver = $config['storage']['driver'];
        if ($driver === 'local') {
            return new Filesystem(new Local($config['storage']['path']));
        } elseif ($driver === 's3') {
            $client = new S3Client([
                'credentials' => [
                    'key' => $config['storage']['key'],
                    'secret' => $config['storage']['secret'],
                ],
                'region' => $config['storage']['region'],
                'endpoint' => $config['storage']['endpoint'],
                'version' => 'latest',
                'use_path_style_endpoint' => $config['storage']['use_path_style_endpoint'] ?? false,
                '@http' => ['stream' => true],
            ]);

            $adapter = new AwsS3Adapter($client, $config['storage']['bucket'], $config['storage']['path']);
        } elseif ($driver === 'dropbox') {
            $client = new DropboxClient($config['storage']['token']);

            $adapter = new DropboxAdapter($client);
        } elseif ($driver === 'ftp') {
            $adapter = new FtpAdapter([
                'host' => $config['storage']['host'],
                'username' => $config['storage']['username'],
                'password' => $config['storage']['password'],
                'port' => $config['storage']['port'],
                'root' => $config['storage']['path'],
                'passive' => $config['storage']['passive'],
                'ssl' => $config['storage']['ssl'],
                'timeout' => 30,
            ]);
        } elseif ($driver === 'google-cloud') {
            $client = new StorageClient([
                'projectId' => $config['storage']['project_id'],
                'keyFilePath' => $config['storage']['key_path'],
            ]);

            $adapter = new GoogleStorageAdapter($client, $client->bucket($config['storage']['bucket']));
        } elseif ($driver === 'azure') {
            $client = BlobRestProxy::createBlobService(
                sprintf(
                    'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;',
                    $config['storage']['account_name'],
                    $config['storage']['account_key']
                )
            );

            $adapter = new AzureBlobStorageAdapter($client, $config['storage']['container_name']);
        } else {
            throw new InvalidArgumentException('The driver specified is not supported.');
        }

        $cache = new Adapter(new Local(BASE_DIR.'resources/cache/fs'), 'file', 300); // 5min
        return new Filesystem(new CachedAdapter($adapter, $cache));
    }),
    'storage' => get(Filesystem::class),

    Lang::class => factory(function () {
        return Lang::build(Lang::recognize(), BASE_DIR.'resources/lang/');
    }),
    'lang' => get(Lang::class),
];
