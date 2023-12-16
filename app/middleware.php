<?php

use DI\Container;
use Carbon\Carbon;
use Slim\Views\Twig;
use App\Models\Config;
use Slim\Flash\Messages;
use App\Models\CacheEngine;
use App\Models\EmailEngine;
use Slim\Factory\AppFactory;
use App\Validation\Validator;
use Slim\Views\TwigMiddleware;
use Slim\Psr7\Factory\UriFactory;
use Slim\Views\TwigRuntimeLoader;
use Slim\Middleware\ErrorMiddleware;
use App\Middleware\FormValidation\PreserveInputMiddleware;
use App\Middleware\FormValidation\ValidationErrorsMiddleware;

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

	$logger = $container->get('spotifyLogger');

	// setup the session, used for interacting with our session tokens
	$session = new SpotifyWebAPI\Session(
		getenv('SPOTIFY_CLIENT_ID'),
		getenv('SPOTIFY_CLIENT_SECRET'),
		getenv('SPOTIFY_REDIRECT_URI')
	);

	// Refresh token
	$refreshTokenFromDatabase = Config::get('spotify_refresh_token');
	$session->setRefreshToken($refreshTokenFromDatabase);

	// Access token
	$sessionAccessToken = $session->getAccessToken();
	$sessionAccessTokenFromDatabase = Config::get('spotify_access_token');
	$expirationForAccessToken = $session->getTokenExpiration();

	$logger->debug('====================================');
	$logger->debug('SPOTIFY ACCESSED');
	$logger->debug('Access Token from Session: ' . $sessionAccessToken);
	$logger->debug('Access Token from Database: ' . $sessionAccessTokenFromDatabase);
	$logger->debug('Refresh Token from Session: ' . $session->getRefreshToken());
	$logger->debug('Access Token Expiration from Session: ' . $expirationForAccessToken);

	// Use previously requested tokens fetched from database
	if (!isNullOrEmptyString($sessionAccessTokenFromDatabase)) {
		$logger->debug('Setting session object access and refresh token from database values');
		$session->setAccessToken($sessionAccessTokenFromDatabase);
		$session->setRefreshToken($refreshTokenFromDatabase);
	} else {
		// Or request a new access token
		$logger->debug('Getting new access token using database refresh token');
		$session->refreshAccessToken($refreshTokenFromDatabase);
	}

	$options = [
		'auto_refresh' => true
	];

	// setup API with options and session data we have
	$api = new SpotifyWebAPI\SpotifyWebAPI($options, $session);

	// Remember to grab the tokens afterwards, they might have been updated
	$newAccessToken = $session->getAccessToken();
	$newRefreshToken = $session->getRefreshToken();

	// Update the API object with the new access token
	$api->setAccessToken($newAccessToken);

	// Update DB if the values are not the same
	if (Config::get('spotify_access_token') != $newAccessToken) {
		Config::updateSection('spotify_access_token', $newAccessToken, $logger);
		Config::updateSection('debug_spotify_changed_at', (string)Carbon::now(), $logger);
	}

	if (Config::get('spotify_refresh_token') != $newRefreshToken) {
		Config::updateSection('spotify_refresh_token', $newRefreshToken, $logger);
		Config::updateSection('debug_spotify_changed_at', (string)Carbon::now(), $logger);
	}

	// Setup API with session access token stored in DB
	$logger->debug('Setup API with session access token stored in DB');
	$logger->debug($sessionAccessTokenFromDatabase);

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
