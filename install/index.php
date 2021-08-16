<?php

((PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 2) || PHP_MAJOR_VERSION > 7) ?: die('Sorry, PHP 7.2 or above is required to run XBackBone.');
require __DIR__.'/../vendor/autoload.php';

use App\Database\Migrator;
use App\Factories\ViewFactory;
use App\Web\Session;
use App\Web\View;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use function DI\factory;
use function DI\get;
use function DI\value;
use League\Flysystem\FileExistsException;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

define('PLATFORM_VERSION', json_decode(file_get_contents(__DIR__.'/../composer.json'))->version);
define('BASE_DIR', realpath(__DIR__.'/../').DIRECTORY_SEPARATOR);

// default config
$config = [
    'base_url' => str_replace('/install/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),
    'debug' => true,
    'db' => [
        'connection' => 'sqlite',
        'dsn' => BASE_DIR.implode(DIRECTORY_SEPARATOR, ['resources', 'database', 'xbackbone.db']),
        'username' => null,
        'password' => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path' => realpath(__DIR__.'/../').DIRECTORY_SEPARATOR.'storage',
    ],
];

$installed = false;
if (file_exists(__DIR__.'/../config.php')) {
    $installed = true;
    $config = array_replace_recursive($config, require __DIR__.'/../config.php');

    if (isset($config['storage_dir'])) { // if from older installations with no support of other than local driver
        $config['storage']['driver'] = 'local';
        $config['storage']['path'] = $config['storage_dir'];
        unset($config['storage_dir']);
    }

    if ($config['storage']['driver'] === 'local' && !is_dir($config['storage']['path'])) { // if installed with local driver, and the storage dir don't exists
        $realPath = realpath(BASE_DIR.$config['storage']['path']);
        if (is_dir($realPath) && is_writable($realPath)) { // and was a path relative to the upper folder
            $config['storage']['path'] = $realPath; // update the config
        }
    }
}

$builder = new ContainerBuilder();

$builder->addDefinitions([
    'config' => value($config),
    View::class => factory(function (Container $container) {
        return ViewFactory::createInstallerInstance($container);
    }),
    'view' => get(View::class),
    Session::class => factory(function () {
        return new Session('xbackbone_session');
    }),
    'session' => get(Session::class),
]);
$builder->addDefinitions(__DIR__.'/../bootstrap/container.php');

$app = Bridge::create($builder->build());
$app->setBasePath(parse_url($config['base_url'].'/install', PHP_URL_PATH));
$app->addRoutingMiddleware();

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

    if (!extension_loaded('zip')) {
        $session->alert('The required "zip" extension is not loaded.', 'danger');
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
})->setName('install');

$app->post('/', function (Request $request, Response $response, \DI\Container $container, Session $session) use (&$config, &$installed) {
    // disable debug in production
    unset($config['debug']);

    // Check if there is a previous installation, if not, setup the config file
    if (!$installed) {
        // config file setup
        $config['base_url'] = param($request, 'base_url');
        $config['storage']['driver'] = param($request, 'storage_driver');
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
                $config['storage']['endpoint'] = !empty(param($request, 'storage_endpoint')) ? param($request, 'storage_endpoint') : null;
                $config['storage']['bucket'] = param($request, 'storage_bucket');
                $config['storage']['path'] = param($request, 'storage_path');
                break;
            case 'dropbox':
                $config['storage']['token'] = param($request, 'storage_token');
                break;
            case 'ftp':
                if (!extension_loaded('ftp')) {
                    $session->alert('The "ftp" extension is not loaded.', 'danger');

                    return redirect($response, urlFor('/'));
                }
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
            case 'azure':
                $config['storage']['account_name'] = param($request, 'storage_account_name');
                $config['storage']['account_key'] = param($request, 'storage_account_key');
                $config['storage']['container_name'] = param($request, 'storage_container_name');
                break;
            case 'local':
            default:
                $config['storage']['path'] = param($request, 'storage_path');
                break;
        }
        $container->set('config', value($config));
    }

    $storage = $container->get('storage');
    // check if the storage is valid
    $storageTestFile = 'storage_test.txt';
    try {
        try {
            $success = $storage->write($storageTestFile, 'TEST_FILE');
        } catch (FileExistsException $fileExistsException) {
            $success = $storage->update($storageTestFile, 'TEST_FILE');
        }

        if (!$success) {
            throw new Exception('The storage is not writable.');
        }
        $storage->readAndDelete($storageTestFile);
    } catch (Exception $e) {
        $session->alert("Storage setup error: {$e->getMessage()} [{$e->getCode()}]", 'danger');

        return redirect($response, urlFor('/install'));
    }

    // Get the db instance and run migrations
    $db = $container->get('database');
    try {
        $migrator = new Migrator($db, __DIR__.'/../resources/schemas');
        $migrator->migrate();
        $migrator->reSyncQuotas($storage);
    } catch (PDOException $e) {
        $session->alert("Cannot connect to the database: {$e->getMessage()} [{$e->getCode()}]", 'danger');

        return redirect($response, urlFor('/install'));
    }

    // if not installed, create the default admin account
    if (!$installed) {
        $db->query("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES (?, 'admin', ?, 1, ?)", [param($request, 'email'), password_hash(param($request, 'password'), PASSWORD_DEFAULT), humanRandomString(5)]);
    }

    // re-apply the previous theme if is present
    $css = $db->query('SELECT `value` FROM `settings` WHERE `key` = \'css\'')->fetch()->value ?? null;
    if ($css && strpos($css, '|') !== false) {
        $container->make(\App\Web\Theme::class)->applyTheme($css);
    }

    // if is upgrading and existing installation, put it out maintenance
    if ($installed) {
        unset($config['maintenance']);

        // remove old config from old versions
        unset($config['lang']);
        unset($config['displayErrorDetails']);
    }

    // Finally write the config
    $ret = file_put_contents(__DIR__.'/../config.php', '<?php'.PHP_EOL.'return '.var_export($config, true).';');
    if ($ret === false) {
        $session->alert('The config folder is not writable ('.__DIR__.'/../config.php'.')', 'danger');

        return redirect($response, '/install');
    }

    // post install cleanup
    cleanDirectory(__DIR__.'/../resources/cache');
    cleanDirectory(__DIR__.'/../resources/sessions');

    removeDirectory(__DIR__.'/../install');

    // Installed successfully, destroy the installer session
    $session->destroy();

    return redirect($response, urlFor('/?afterInstall=true'));
});

$app->run();
