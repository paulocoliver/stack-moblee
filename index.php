<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

// REGISTER
$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => __DIR__.'/views',
]);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'db.options' => array(
		'driver'   => 'pdo_sqlite',
		'path'     => __DIR__.'/app.db',
	),
));
/**
 * @var $db \Doctrine\DBAL\Connection
 */
$db = $app['db'];


if (!$db->getSchemaManager()->tablesExist('stackoverflow')) {
	$res = $db->exec('CREATE TABLE stackoverflow(
					   question_id INT PRIMARY KEY NOT NULL,
					   title           TEXT,
					   owner_name      TEXT,
					   score           INT,
					   creation_date   INT,
					   link 		   TEXT,
					   is_answered     INT);');
	if (!$db->getSchemaManager()->tablesExist('stackoverflow'))
		exit('Error create table stackoverflow');
}


// ROUTES
$app->get('/', function () use ($app) {
	return $app['twig']->render('index.twig', []);
});

$app->run();