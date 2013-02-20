<?php

require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

require '../lib/view.php';
require 'db.php';


$app = new \Slim\Slim(array(
  'templates.path' => __DIR__ . '/templates',
  'db' => array(
    'dsn' => 'sqlite:' . __DIR__ . '/../db/development.db',
  ),
  'view' => new LayoutView()
));
$app->view()->setLayout('layout.phtml');
DB::connect($app->config('db'));


require 'users.php';

require 'groups.php';

$app->get('/', $users_index);


return $app;
