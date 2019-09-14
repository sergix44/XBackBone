<?php
(PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 1) ?: die('Sorry, PHP 7.1 or above is required to run XBackBone.');
require __DIR__ . '/../vendor/autoload.php';

use App\Database\DB;
use App\Web\Session;
use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\FileExistsException;
use Spatie\Dropbox\Client as DropboxClient;
use League\Flysystem\Filesystem;
use Slim\App;
use Slim\Container;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

define('PLATFORM_VERSION', json_decode(file_get_contents(__DIR__ . '/../composer.json'))->version);

// default config
$config = [
	'base_url' => str_replace('/install/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),
	'displayErrorDetails' => true,
	'db' => [
		'connection' => 'sqlite',
		'dsn' => realpath(__DIR__ . '/../') . implode(DIRECTORY_SEPARATOR, ['resources', 'database', 'xbackbone.db']),
		'username' => null,
		'password' => null,
	],
	'storage' => [
		'driver' => 'local',
		'path' => realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'storage',
	],
];

if (file_exists(__DIR__ . '/../config.php')) {
	$config = array_replace_recursive($config, require __DIR__ . '/../config.php');
}

$container = new Container(['settings' => $config]);

$container['session'] = function ($container) {
	return new Session('xbackbone_session');
};

$container['view'] = function ($container) use (&$config) {
	$view = new Twig([__DIR__ . '/templates', __DIR__ . '/../resources/templates'], [
		'cache' => false,
		'autoescape' => 'html',
		'debug' => $config['displayErrorDetails'],
		'auto_reload' => $config['displayErrorDetails'],
	]);

	// Instantiate and add Slim specific extension
	$router = $container->get('router');
	$uri = Uri::createFromEnvironment(new Environment($_SERVER));
	$view->addExtension(new Slim\Views\TwigExtension($router, $uri));

	$view->getEnvironment()->addGlobal('config', $config);
	$view->getEnvironment()->addGlobal('request', $container->get('request'));
	$view->getEnvironment()->addGlobal('alerts', $container->get('session')->getAlert());
	$view->getEnvironment()->addGlobal('session', $container->get('session')->all());
	$view->getEnvironment()->addGlobal('PLATFORM_VERSION', PLATFORM_VERSION);
	return $view;
};

$container['storage'] = function ($container) use (&$config) {

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
};

function migrate($config) {
	$firstMigrate = false;
	if ($config['db']['connection'] === 'sqlite' && !file_exists(__DIR__ . '/../' . $config['db']['dsn'])) {
		touch(__DIR__ . '/../' . $config['db']['dsn']);
		$firstMigrate = true;
	}

	try {
		DB::doQuery('SELECT 1 FROM `migrations` LIMIT 1');
	} catch (PDOException $exception) {
		$firstMigrate = true;
	}

	if ($firstMigrate) {
		DB::raw()->exec(file_get_contents(__DIR__ . '/../resources/schemas/migrations.sql'));
	}

	$files = glob(__DIR__ . '/../resources/schemas/' . DB::driver() . '/*.sql');

	$names = array_map(function ($path) {
		return basename($path);
	}, $files);

	$in = str_repeat('?, ', count($names) - 1) . '?';

	$inMigrationsTable = DB::doQuery("SELECT * FROM `migrations` WHERE `name` IN ($in)", $names)->fetchAll();


	foreach ($files as $file) {

		$continue = false;
		$exists = false;

		foreach ($inMigrationsTable as $migration) {
			if (basename($file) === $migration->name && $migration->migrated) {
				$continue = true;
				break;
			} else if (basename($file) === $migration->name && !$migration->migrated) {
				$exists = true;
				break;
			}
		}
		if ($continue) continue;

		$sql = file_get_contents($file);
		try {
			DB::raw()->exec($sql);
			if (!$exists) {
				DB::doQuery('INSERT INTO `migrations` VALUES (?,?)', [basename($file), 1]);
			} else {
				DB::doQuery('UPDATE `migrations` SET `migrated`=? WHERE `name`=?', [1, basename($file)]);
			}
		} catch (PDOException $exception) {
			if (!$exists) {
				DB::doQuery('INSERT INTO `migrations` VALUES (?,?)', [basename($file), 0]);
			}
			throw $exception;
		}
	}
}

$app = new App($container);

$app->get('/', function (Request $request, Response $response) {

	if (!extension_loaded('gd')) {
		$this->session->alert('The required "gd" extension is not loaded.', 'danger');
	}

	if (!extension_loaded('intl')) {
		$this->session->alert('The required "intl" extension is not loaded.', 'danger');
	}

	if (!extension_loaded('json')) {
		$this->session->alert('The required "json" extension is not loaded.', 'danger');
	}

	if (!is_writable(__DIR__ . '/../resources/cache')) {
		$this->session->alert('The cache folder is not writable (' . __DIR__ . '/../resources/cache' . ')', 'danger');
	}

	if (!is_writable(__DIR__ . '/../resources/database')) {
		$this->session->alert('The database folder is not writable (' . __DIR__ . '/../resources/database' . ')', 'danger');
	}

	if (!is_writable(__DIR__ . '/../resources/sessions')) {
		$this->session->alert('The sessions folder is not writable (' . __DIR__ . '/../resources/sessions' . ')', 'danger');
	}

	$installed = file_exists(__DIR__ . '/../config.php');

	return $this->view->render($response, 'install.twig', [
		'installed' => $installed,
	]);
});

$app->post('/', function (Request $request, Response $response) use (&$config) {

	// Check if there is a previous installation, if not, setup the config file
	$installed = true;
	if (!file_exists(__DIR__ . '/../config.php')) {
		$installed = false;

		// config file setup
		$config['base_url'] = $request->getParam('base_url');
		$config['storage']['driver'] = $request->getParam('storage_driver');
		unset($config['displayErrorDetails']);
		$config['db']['connection'] = $request->getParam('connection');
		$config['db']['dsn'] = $request->getParam('dsn');
		$config['db']['username'] = $request->getParam('db_user');
		$config['db']['password'] = $request->getParam('db_password');


		// setup storage configuration
		switch ($config['storage']['driver']) {
			case 's3':
				$config['storage']['key'] = $request->getParam('storage_key');
				$config['storage']['secret'] = $request->getParam('storage_secret');
				$config['storage']['region'] = $request->getParam('storage_region');
				$config['storage']['bucket'] = $request->getParam('storage_bucket');
				$config['storage']['path'] = $request->getParam('storage_path');
				break;
			case 'dropbox':
				$config['storage']['token'] = $request->getParam('storage_token');
				break;
			case 'ftp':
				$config['storage']['host'] = $request->getParam('storage_host');
				$config['storage']['username'] = $request->getParam('storage_username');
				$config['storage']['password'] = $request->getParam('storage_password');
				$config['storage']['port'] = $request->getParam('storage_port');
				$config['storage']['path'] = $request->getParam('storage_path');
				$config['storage']['passive'] = $request->getParam('storage_passive') === '1';
				$config['storage']['ssl'] = $request->getParam('storage_ssl') === '1';
				break;
			case 'google-cloud':
				$config['storage']['project_id'] = $request->getParam('storage_project_id');
				$config['storage']['key_path'] = $request->getParam('storage_key_path');
				$config['storage']['bucket'] = $request->getParam('storage_bucket');
				break;
			case 'local':
			default:
				$config['storage']['path'] = $request->getParam('storage_path');
				break;
		}

		// check if the storage is valid
		$storageTestFile = 'storage_test.xbackbone.txt';
		try {
			try {
				$success = $this->storage->write($storageTestFile, 'XBACKBONE_TEST_FILE');
			} catch (FileExistsException $fileExistsException) {
				$success = $this->storage->update($storageTestFile, 'XBACKBONE_TEST_FILE');
			}

			if (!$success) {
				throw new Exception('The storage is not writable.');
			}
			$this->storage->readAndDelete($storageTestFile);
		} catch (Exception $e) {
			$this->session->alert("Storage setup error: {$e->getMessage()} [{$e->getCode()}]", 'danger');
			return redirect($response, '/install');
		}

		$ret = file_put_contents(__DIR__ . '/../config.php', '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';');
		if ($ret === false) {
			$this->session->alert('The config folder is not writable (' . __DIR__ . '/../config.php' . ')', 'danger');
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

		$dsn = $config['db']['connection'] === 'sqlite' ? __DIR__ . '/../' . $config['db']['dsn'] : $config['db']['dsn'];
		DB::setDsn($config['db']['connection'] . ':' . $dsn, $config['db']['username'], $config['db']['password']);

		migrate($config);
	} catch (PDOException $e) {
		$this->session->alert("Cannot connect to the database: {$e->getMessage()} [{$e->getCode()}]", 'danger');
		return redirect($response, '/install');
	}

	// if not installed, create the default admin account
	if (!$installed) {
		DB::doQuery("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES (?, 'admin', ?, 1, ?)", [$request->getParam('email'), password_hash($request->getParam('password'), PASSWORD_DEFAULT), substr(md5(microtime()), rand(0, 26), 5)]);
	}

	// post install cleanup
	cleanDirectory(__DIR__ . '/../resources/cache');
	cleanDirectory(__DIR__ . '/../resources/sessions');

	removeDirectory(__DIR__ . '/../install');

	// if is upgrading and existing installation, put it out maintenance
	if ($installed) {
		unset($config['maintenance']);

		$ret = file_put_contents(__DIR__ . '/../config.php', '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';');
		if ($ret === false) {
			$this->session->alert('The config folder is not writable (' . __DIR__ . '/../config.php' . ')', 'danger');
			return redirect($response, '/install');
		}
	}

	// Installed successfully, destroy the installer session
	session_destroy();
	return $response->withRedirect("{$config['base_url']}/?afterInstall=true");
});

$app->run();