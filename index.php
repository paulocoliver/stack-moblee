<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

// ROUTES
$app->get('/', function () use ($app) {
	return $app['twig']->render('index.twig', []);
});

$app->run();