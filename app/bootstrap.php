<?php
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
date_default_timezone_set('America/Chicago');
session_start();

$config = dirname(__FILE__) . '/env/config.env';
$copy = dirname(__FILE__) . '/env/config.env.example';
if (!file_exists(dirname(__FILE__) . '/env/config.env')) {
	if (!copy($copy, $config)) {
		die("Failed to copy $copy...\n");
	}
}

try {
	$config = Dotenv::create(__DIR__ . '/env/', 'config.env');
	$config->load();
	$config->required(['DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'HOST', 'PORT', 'DRIVER']);
}
catch (InvalidPathException $e) {
	die($e->getMessage());
}

/**
 * Create the database capsule
 * @var Capsule
 */
$capsule = new Capsule();
$capsule->addConnection([
	'driver' => getenv('DRIVER'),
	'host' => getenv('HOST'),
	'port' => getenv('PORT'),
	'database' => getenv('DB_NAME'),
	'username' => getenv('DB_USERNAME'),
	'password' => getenv('DB_PASSWORD'),
	'charset' => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix' => ''
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();