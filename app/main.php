<?php

require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

require '../lib/view.php';


$app = new \Slim\Slim(array(
  'templates.path' => __DIR__ . '/templates',
  'view' => new LayoutView()
));
$app->view()->setLayout('layout.phtml');

$app->get('/', function() use ($app) {
  $app->render('dashboard.phtml');
});

$app->get('/users', function() use ($app) {
  $app->render('users/index.phtml');
})->name('users');

$app->get('/groups', function() use ($app) {

})->name('groups');

return $app;
