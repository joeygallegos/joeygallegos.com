<?php

use App\Controllers\HomeController;
use App\Controllers\ContactController;
use App\Controllers\ProjectController;
use App\Controllers\SpotifyController;
use App\Controllers\InterfaceController;
use App\Controllers\SiteListenerController;
use App\Middleware\FormValidation\SetFormSpam;
use App\Middleware\FormValidation\CheckFormSpam;

$app->get('/debug/', InterfaceController::class . ':getDebugPage')->setName('debug');
$app->get('/test/', InterfaceController::class . ':getTestPage')->setName('test');

// ----------------------------------------------
// HOME
// ----------------------------------------------
$app->get('/', HomeController::class . ':getHomePage')->setName('home');
$app->get('/about', HomeController::class . ':getAboutPage')->setName('about-get');
$app->get('/faq', HomeController::class . ':getFaqPage')->setName('faq-get');
$app->get('/experience', HomeController::class . ':getExperiencePage')->setName('experience-get');
$app->get('/style', HomeController::class . ':getStylePage')->setName('style-get');

$app->get('/portfolio/{descriptor}', ProjectController::class . ':getProjectPage')->setName('project');

// ----------------------------------------------
// SOCIAL PAGE
// ----------------------------------------------
$app->get('/social', function ($request, $response, $args) {
	return $this->view->render($response, 'social.twig', []);
})->setName('social');

// ----------------------------------------------
// CONTACT PAGE
// ----------------------------------------------
$app->get('/contact-me', ContactController::class . ':getContactPage')
	->setName('contact-get')
	->add(new SetFormSpam($container));

$app->post('/contact/{hash}', ContactController::class . ':postContactPage')
	->setName('contact-post')
	->add(new CheckFormSpam($container));

$app->get('/spotify', SpotifyController::class . ':getSpotifyAuth')
	->setName('spotify-get');

$app->get('/api/listen', SiteListenerController::class . ':getResponseFromSite')
	->setName('api-site-listen-get');
