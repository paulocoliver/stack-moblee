<?php
require_once __DIR__.'/vendor/autoload.php';

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

$app->get('/stack_moblee/v1/question', function() use($app, $db) {
	try {

		$page  = (int) !empty($_GET['page']) ? $_GET['page'] : 1;
		$rpp   = (int) !empty($_GET['rpp']) ? $_GET['rpp'] : 10;
		$sort  = !empty($_GET['sort']) ? $_GET['sort'] : 'question_id';
		$score = (!empty($_GET['score']) || $_GET['score'] == 0) ? (int)$_GET['score'] : '';

		if (!empty($sort) && !in_array($sort, ['question_id', 'title', 'owner_name', 'score', 'creation_date', 'link', 'is_answered']))
			throw new Exception('sort invalid');

		$sql = $db->createQueryBuilder()
			->select('*')
			->from('stackoverflow')
			->orderBy($sort, 'ASC');

		$paramsWhere = [];
		if (is_int($score)) {
			$sql->where('score > ?');
			$paramsWhere = [$score];
		}

		if (!empty($page))
			$sql->setFirstResult($page); # offset

		if (!empty($rpp))
			$sql->setMaxResults($rpp); # limit

		$stackoverflow = $db->fetchAll($sql, $paramsWhere);

		$last_update = file_get_contents('last_update.txt');
		$result = [
			'success' => 1,
			'last_update' => $last_update,
			'content' => $stackoverflow,
		];

	} catch (Exception $e) {
		$result = ['success' => 0, 'message' => 'Desculpe ocorreu um erro. '.$e->getMessage()];
	}
	return $app->json($result);
});

$app->get('/get-api-stackoverflow', function() use($app, $db) {
	try {
		$client = new GuzzleHttp\Client();
		$response = $client->request('GET', 'https://api.stackexchange.com/2.2/search/advanced?page=1&pagesize=99&order=desc&sort=activity&accepted=True&tagged=php&site=stackoverflow');
		if ($response->getStatusCode() != 200)
			throw new Exception('Status response error: '.$response->getStatusCode());

		$res = json_decode($response->getBody());
		if (empty($res->items))
			throw new Exception('A API não retornou dados');

		$db->exec('DELETE FROM stackoverflow;');
		foreach ($res->items as $item) {
			$db->insert('stackoverflow', [
				'question_id' => $item->question_id,
				'title' => $item->title,
				'owner_name' => $item->owner->display_name,
				'score' => $item->score,
				'creation_date' =>  $item->creation_date,
				'link' => $item->link,
				'is_answered' => $item->is_answered,
			]);
		}

		file_put_contents('last_update.txt', time());

		$result = ['success' => 1];

	} catch (Exception $e) {
		$result = ['success' => 0, 'message' => 'Desculpe ocorreu um erro. '.$e->getMessage()];
	}
	return $app->json($result);
});

$app->run();