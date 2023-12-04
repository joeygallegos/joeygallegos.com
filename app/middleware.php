<?php

use DI\Container;
use Slim\Views\Twig;
use App\Models\Config;
use Slim\Flash\Messages;
use App\Models\CacheEngine;
use App\Models\EmailEngine;
use Slim\Factory\AppFactory;
use App\Validation\Validator;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\ErrorMiddleware;
use App\Middleware\FormValidation\PreserveInputMiddleware;
use App\Middleware\FormValidation\ValidationErrorsMiddleware;
use Slim\Psr7\Factory\UriFactory;
use Slim\Views\TwigRuntimeLoader;

// create container using DI\Container
$container = new Container();
AppFactory::setContainer($container);

// app instantiation must be done after container creation when using a factory
$app = AppFactory::create();

// ----------------------------------------------
// TWIG TEMPLATE ENGINE
// ----------------------------------------------
$container->set('view', function () use ($app) {
	$twig = Twig::create('../app/views/', ['cache' => false]);
	$runtimeLoader = new TwigRuntimeLoader(
		$app->getRouteCollector()->getRouteParser(),
		(new UriFactory)->createFromGlobals($_SERVER),
		'/'
	);
	$twig->addRuntimeLoader($runtimeLoader);

	// Basically, you must instantiate an instance of Twig, then add the globals BEFORE adding to the container and adding TwigView middleware to the app. You have to add the globals, even if they’re initialized to NULL. So long as they’ve been added, you can change them later by using addGlobal(). That’s what the validator is checking in the code that I originally posted. If you’ve already started the middleware, but the global DOESN’T exist, then the exception is thrown.
	// https://discourse.slimframework.com/t/slim-4-twig-globals/3464/10

	// add variable for form errors and old form input
	$twig->getEnvironment()->addGlobal('form_errors', []);
	$twig->getEnvironment()->addGlobal('old_form_data', []);
	return $twig;
});

$app->add(new ValidationErrorsMiddleware($container));
$app->add(new PreserveInputMiddleware($container));

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// random generator
$container->set('app', function () use ($app) {
	return $app;
});

// random generator
$container->set('randomGenerator', function () {
	$factory = new RandomLib\Factory;
	return $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));
});

// ----------------------------------------------
// EMAIL ENGINE
// ----------------------------------------------
$container->set('emailEngine', function ($container) {
	return new EmailEngine(
		getenv('MAILGUN_PUBKEY'),
		getenv('MAILGUN_KEY'),
		getenv('MAILGUN_DOMAIN'),
		getenv('MAILGUN_FROM'),
		$container->get('emailEngineLogger')
	);
});

// ----------------------------------------------
// IMPLEMENT VALIDATOR
// ----------------------------------------------
$container->set('validator', function ($container) {
	return new Validator($container);
});

// ----------------------------------------------
// SLIM FLASHING
// ----------------------------------------------
$container->set('flash', function ($container) {
	return new Messages();
});

// ----------------------------------------------
// SPOTIFY
// ----------------------------------------------
$container->set('spotify', function ($container) {

	// setup the session, used for interacting with our session tokens
	$session = new SpotifyWebAPI\Session(
		getenv('SPOTIFY_CLIENT_ID'),
		getenv('SPOTIFY_CLIENT_SECRET'),
		getenv('SPOTIFY_REDIRECT_URI')
	);
	$session->setRefreshToken(Config::get('spotify_refresh_token'));
	$sessionAccessToken = $session->getAccessToken();
	$sessionAccessTokenFromDatabase = Config::get('spotify_access_token');
	$expiration = $session->getTokenExpiration();



	$container->get('spotifyLogger')->info('====================================');
	$container->get('spotifyLogger')->info('SPOTIFY ACCESSED');
	$container->get('spotifyLogger')->info('Access Token from Session: ' . $sessionAccessToken);
	$container->get('spotifyLogger')->info('Access Token from Database: ' . $sessionAccessTokenFromDatabase);
	$container->get('spotifyLogger')->info('Refresh Token from Session: ' . $session->getRefreshToken());
	$container->get('spotifyLogger')->info('Token Expiration from Session: ' . $expiration);

	// deploy new access token if DB and new one do not match
	$session->refreshAccessToken();
	if ($session->getAccessToken() != $sessionAccessTokenFromDatabase) {
		$container->get('spotifyLogger')->info('TASK: Session access token doesn\'t match storage access token - updating DB');
		Config::updateSection('spotify_access_token', $session->getAccessToken(), $container->get('spotifyLogger'));
	}

	// setup API with session access token stored in DB
	$api = new SpotifyWebAPI\SpotifyWebAPI();
	$api->setAccessToken($sessionAccessTokenFromDatabase);

	return [
		'session' => $session,
		'api' => $api
	];
});

// ----------------------------------------------
// IMPORT LOGGERS
// ----------------------------------------------
require_once dirname(__FILE__) . '/loggers.php';

// ----------------------------------------------
// ENVIRONMENT SETUP
// ----------------------------------------------
if (in_array(getenv('ENVIRONMENT'), ['assembly', 'staging'])) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('max_execution_time', 0);

	$app->add(new ErrorMiddleware($app->getCallableResolver(), $app->getResponseFactory(), true, true, true));

	$cacheEngine = new CacheEngine(
		$container->get('cacheLogger')
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
