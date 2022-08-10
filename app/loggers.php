<?php

use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ----------------------------------------------
// CACHE ENGINE LOGGER
// ----------------------------------------------
$container->set('cacheLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-cacheLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// HOMEPAGE LOGGER
// ----------------------------------------------
$container->set('homeLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-homeLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// PROJECT LOGGER
// ----------------------------------------------
$container->set('projectLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-projectLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// CONTACT FORM LOGGER
// ----------------------------------------------
$container->set('contactFormLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-contactFormLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// EMAIL ENGINE LOGGER
// ----------------------------------------------
$container->set('emailEngineLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-emailEngineLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// DEBUG LOGGER
// ----------------------------------------------
$container->set('debugLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-debugLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});

// ----------------------------------------------
// SPOTIFY LOGGER
// ----------------------------------------------
$container->set('spotifyLogger', function () {
    $logger = new Logger('App');
    $carbon = new Carbon;
    $formatter = new LineFormatter(null, null, false, true);

    $handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-spotifyLog.log");
    $handler->setFormatter($formatter);

    $logger->pushHandler($handler);
    return $logger;
});
