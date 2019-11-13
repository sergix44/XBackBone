<?php
(PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 1) ?: die('Sorry, PHP 7.1 or above is required to run XBackBone.');
require __DIR__.'/../vendor/autoload.php';

use App\Database\DB;
use App\Database\Migrator;
use App\Factories\ViewFactory;
use App\Web\Session;
use App\Web\View;
use Aws\S3\S3Client;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\FileExistsException;
use Spatie\Dropbox\Client as DropboxClient;
use League\Flysystem\Filesystem;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function DI\factory;
use function DI\get;
use function DI\value;

define('PLATFORM_VERSION', json_decode(file_get_contents(__DIR__.'/../composer.json'))->version);
define('BASE_DIR', realpath(__DIR__.'/../').DIRECTORY_SEPARATOR);

// default config
$config = [
    'base_url' => str_replace('/install/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),
    'debug' => true,
    'db' => [
        'connection' => 'sqlite',
        'dsn' => realpath(__DIR__.'/../').implode(DIRECTORY_SEPARATOR, ['resources', 'database', 'xbackbone.db']),
        'username' => null,
        'password' => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path' => realpath(__DIR__.'/../').DIRECTORY_SEPARATOR.'storage',
    ],
];

if (file_exists(__DIR__.'/../config.php')) {
    $config = array_replace_recursive($config, require __DIR__.'/../config.php');
}

$builder = new ContainerBuilder();

$builder->addDefinitions([
    'config' => value($config),

    Session::class => factory(function () {
        return new Session('xbackbone_session');
    }),
    'session' => get(Session::class),

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
                    'version' => 'latest',
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
            default:
                throw new InvalidArgumentException('The driver specified is not supported.');
        }
    }),

    View::class => factory(function ($container) {
        return ViewFactory::createInstallerInstance($container);
    }),
]);

$app = Bridge::create($builder->build());
$app->addRoutingMiddleware();
$app->setBasePath('/install');

$app->get('/', function (Response $response, View $view, Session $session) {

    if (!extension_loaded('gd')) {
        $session->alert('The required "gd" extension is not loaded.', 'danger');
    }

    if (!extension_loaded('intl')) {
        $session->alert('The required "intl" extension is not loaded.', 'danger');
    }

    if (!extension_loaded('json')) {
        $session->alert('The required "json" extension is not loaded.', 'danger');
    }

    if (!extension_loaded('fileinfo')) {
        $session->alert('The required "fileinfo" extension is not loaded.', 'danger');
    }

    if (!is_writable(__DIR__.'/../resources/cache')) {
        $session->alert('The cache folder is not writable ('.__DIR__.'/../resources/cache'.')', 'danger');
    }

    if (!is_writable(__DIR__.'/../resources/database')) {
        $session->alert('The database folder is not writable ('.__DIR__.'/../resources/database'.')', 'danger');
    }

    if (!is_writable(__DIR__.'/../resources/sessions')) {
        $session->alert('The sessions folder is not writable ('.__DIR__.'/../resources/sessions'.')', 'danger');
    }

    $installed = file_exists(__DIR__.'/../config.php');

    return $view->render($response, 'install.twig', [
        'installed' => $installed,
    ]);
});

$app->post('/', function (Request $request, Response $response, Filesystem $storage, Session $session) use (&$config) {

    // Check if there is a previous installation, if not, setup the config file
    $installed = true;
    if (!file_exists(__DIR__.'/../config.php')) {
        $installed = false;

        // config file setup
        $config['base_url'] = param($request, 'base_url');
        $config['storage']['driver'] = param($request, 'storage_driver');
        unset($config['debug']);
        $config['db']['connection'] = param($request, 'connection');
        $config['db']['dsn'] = param($request, 'dsn');
        $config['db']['username'] = param($request, 'db_user');
        $config['db']['password'] = param($request, 'db_password');


        // setup storage configuration
        switch ($config['storage']['driver']) {
            case 's3':
                $config['storage']['key'] = param($request, 'storage_key');
                $config['storage']['secret'] = param($request, 'storage_secret');
                $config['storage']['region'] = param($request, 'storage_region');
                $config['storage']['bucket'] = param($request, 'storage_bucket');
                $config['storage']['path'] = param($request, 'storage_path');
                break;
            case 'dropbox':
                $config['storage']['token'] = param($request, 'storage_token');
                break;
            case 'ftp':
                $config['storage']['host'] = param($request, 'storage_host');
                $config['storage']['username'] = param($request, 'storage_username');
                $config['storage']['password'] = param($request, 'storage_password');
                $config['storage']['port'] = param($request, 'storage_port');
                $config['storage']['path'] = param($request, 'storage_path');
                $config['storage']['passive'] = param($request, 'storage_passive') === '1';
                $config['storage']['ssl'] = param($request, 'storage_ssl') === '1';
                break;
            case 'google-cloud':
                $config['storage']['project_id'] = param($request, 'storage_project_id');
                $config['storage']['key_path'] = param($request, 'storage_key_path');
                $config['storage']['bucket'] = param($request, 'storage_bucket');
                break;
            case 'local':
            default:
                $config['storage']['path'] = param($request, 'storage_path');
                break;
        }

        // check if the storage is valid
        $storageTestFile = 'storage_test.xbackbone.txt';
        try {
            try {
                $success = $storage->write($storageTestFile, 'XBACKBONE_TEST_FILE');
            } catch (FileExistsException $fileExistsException) {
                $success = $storage->update($storageTestFile, 'XBACKBONE_TEST_FILE');
            }

            if (!$success) {
                throw new Exception('The storage is not writable.');
            }
            $storage->readAndDelete($storageTestFile);
        } catch (Exception $e) {
            $session->alert("Storage setup error: {$e->getMessage()} [{$e->getCode()}]", 'danger');
            return redirect($response, '/install');
        }

        $ret = file_put_contents(__DIR__.'/../config.php', '<?php'.PHP_EOL.'return '.var_export($config, true).';');
        if ($ret === false) {
            $session->alert('The config folder is not writable ('.__DIR__.'/../config.php'.')', 'danger');
            return redirect($response, '/install');
        }
    }

    // if from older installations with no support of other than local driver
    // update the config
    if ($installed && isset($config['storage_dir'])) {
        $config['storage']['driver'] = 'local';
        $config['storage']['path'] = $config['storage_dir'];
        unset($config['storage_dir']);
    }


    // Build the dns string and run the migrations
    try {

        $firstMigrate = false;
        if ($config['db']['connection'] === 'sqlite' && !file_exists(__DIR__.'/../'.$config['db']['dsn'])) {
            touch(__DIR__.'/../'.$config['db']['dsn']);
            $firstMigrate = true;
        }

        $dsn = $config['db']['connection'] === 'sqlite' ? __DIR__.'/../'.$config['db']['dsn'] : $config['db']['dsn'];
        $db = new DB($config['db']['connection'].':'.$dsn, $config['db']['username'], $config['db']['password']);

        $migrator = new Migrator($db, __DIR__.'/../resources/schemas', $firstMigrate);
        $migrator->migrate();
    } catch (PDOException $e) {
        $session->alert("Cannot connect to the database: {$e->getMessage()} [{$e->getCode()}]", 'danger');
        return redirect($response, '/install');
    }

    // if not installed, create the default admin account
    if (!$installed) {
        $db->query("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES (?, 'admin', ?, 1, ?)", [param($request, 'email'), password_hash(param($request, 'password'), PASSWORD_DEFAULT), humanRandomString(5)]);
    }

    // post install cleanup
    cleanDirectory(__DIR__.'/../resources/cache');
    cleanDirectory(__DIR__.'/../resources/sessions');

    //removeDirectory(__DIR__.'/../install');

    // if is upgrading and existing installation, put it out maintenance
    if ($installed) {
        unset($config['maintenance']);

        $ret = file_put_contents(__DIR__.'/../config.php', '<?php'.PHP_EOL.'return '.var_export($config, true).';');
        if ($ret === false) {
            $session->alert('The config folder is not writable ('.__DIR__.'/../config.php'.')', 'danger');
            return redirect($response, '/install');
        }
    }

    // Installed successfully, destroy the installer session
    session_destroy();
    return redirect($response, "{$config['base_url']}/?afterInstall=true");
});

$app->run();