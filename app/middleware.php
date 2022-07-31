<?php

use Slim\App;
use Carbon\Carbon;
use Monolog\Logger;
use App\Models\Config;
use Slim\Flash\Messages;
use App\Models\CacheEngine;
use App\Models\EmailEngine;
use App\Validation\Validator;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Middleware\FormValidation\PreserveInputMiddleware;
use App\Middleware\FormValidation\ValidationErrorsMiddleware;

// default settings
$slimSettings = [];
$slimSettings['addContentLengthHeader'] = false;
$slimSettings['displayErrorDetails'] = false;
$slimSettings['debug'] = false;

$app = new App([
	'settings' => $slimSettings,
	'config' => $config
]);
$container = $app->getContainer();

// random generator
$container['randomGenerator'] = function ($container) {
	$factory = new RandomLib\Factory;
	return $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));
};

// ----------------------------------------------
// CACHE ENGINE LOGGER
// ----------------------------------------------
$container['cacheLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-cacheLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// HOMEPAGE LOGGER
// ----------------------------------------------
$container['homeLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-homeLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// PROJECT LOGGER
// ----------------------------------------------
$container['projectLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-projectLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// CONTACT FORM LOGGER
// ----------------------------------------------
$container['contactFormLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-contactFormLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// EMAIL ENGINE LOGGER
// ----------------------------------------------
$container['emailEngineLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-emailEngineLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// DEBUG LOGGER
// ----------------------------------------------
$container['debugLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-debugLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// SPOTIFY LOGGER
// ----------------------------------------------
$container['spotifyLogger'] = function ($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);

	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-spotifyLog.log");
	$handler->setFormatter($formatter);

	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// TWIG TEMPLATE ENGINE
// ----------------------------------------------
$container['view'] = function ($container) {
	if (in_array(getenv('ENVIRONMENT'), ['assembly', 'staging'])) {
		$settings = [
			'auto_reload' => true
		];
	} else {
		$settings = [
			'optimizations' => -1
		];
	}

	// use config
	$settings['cache'] = getenv('APP_CACHE_DISABLED') === 'true' ? false : '../app/views/cache/';
	$settings['debug'] = getenv('APP_DEBUG') === 'true';
	$settings['charset'] = getenv('APP_CHARSET');

	// setup view
	$view = new \Slim\Views\Twig('../app/views/', $settings);

	// init and add slim specific extension
	$router = $container->get('router');
	$uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
	$view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
	return $view;
};

// ----------------------------------------------
// EMAIL ENGINE
// ----------------------------------------------
$container['emailEngine'] = function ($container) {
	return new EmailEngine(getenv('MAILGUN_PUBKEY'), getenv('MAILGUN_KEY'), getenv('MAILGUN_DOMAIN'), getenv('MAILGUN_FROM'), $container->emailEngineLogger);
};

// ----------------------------------------------
// IMPLEMENT VALIDATOR
// ----------------------------------------------
$container['validator'] = function ($container) {
	return new Validator($container);
};

// ----------------------------------------------
// SLIM FLASHING
// ----------------------------------------------
$container['flash'] = function ($container) {
	return new Messages();
};

// ----------------------------------------------
// SPOTIFY
// ----------------------------------------------
$container['spotify'] = function ($container) {

	// access token
	// BQBw-kyUQzvZCpMyqJRPWdk09zpAiQPd_mJwMGpVP8Bsf-k03vhdfsWI8dvZF6pGG-ewQ6ReVCJ4I5dJWv8Nw_eyQ7qIRiEVBtdCAT3nTeqXlLqtRvvmJVtlBCyk9jAOyc6JOzxi3l5jmr6f0j2uoCqfb7XmtVOUN0h007_b
	$session = new SpotifyWebAPI\Session(
		getenv('SPOTIFY_CLIENT_ID'),
		getenv('SPOTIFY_CLIENT_SECRET'),
		getenv('SPOTIFY_REDIRECT_URI')
	);

	$api = new SpotifyWebAPI\SpotifyWebAPI();
	$api->setAccessToken(Config::get('spotify_access_token'));

	return [
		'session' => $session,
		'api' => $api
	];
};

$app->add(new ValidationErrorsMiddleware($container));
$app->add(new PreserveInputMiddleware($container));

// ----------------------------------------------
// ENVIRONMENT SETUP
// ----------------------------------------------
if (in_array(getenv('ENVIRONMENT'), ['assembly', 'staging'])) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('max_execution_time', 0);

	$slimSettings = $container->get('settings');
	$slimSettings['displayErrorDetails'] = true;
	$slimSettings['debug'] = true;

	$cacheEngine = new CacheEngine(
		$container->cacheLogger
	);

	$cacheEngine->setSassInDirectory(getBaseDirectory() . '/public/assets/scss');
	$cacheEngine->setSassOutDirectory(getBaseDirectory() . '/public/assets/css');

	$cacheEngine->setJavascriptInFile(getBaseDirectory() . '/public/assets/scripts/main.js');
	$cacheEngine->setJavascriptOutFile(getBaseDirectory() . '/public/assets/scripts/main.min.js');

	$cacheEngine->setOneTimeBuildFiles([
		'grid.scss',
		'flexboxgrid.scss',
		'reset.scss'
	]);

	$cacheEngine->build('Crunched');
} else {
	error_reporting(0);
	ini_set('display_errors', 'Off');
	ini_set('display_startup_errors', 'Off');
}
