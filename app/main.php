<?php

require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

require '../lib/view.php';
require 'db.php';
require '../lib/OpenVPN.php';


session_start();

$app = new \Slim\Slim(array(
  'templates.path' => __DIR__ . '/templates',
  'db' => array(
    'dsn' => 'sqlite:' . __DIR__ . '/../db/development.db',
  ),
  'view' => new LayoutView()
));
$app->view()->setLayout('layout.phtml');
DB::connect($app->config('db'));


$section = function($section) use ($app) {
  return function() use ($section, $app) {
    $app->view()->setData('section', $section);
  };
};

require 'users.php';

require 'groups.php';

$app->get('/', $section('users'), $users_index);


return $app;
