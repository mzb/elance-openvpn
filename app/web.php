<?php

require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
require '../lib/view.php';
require 'core.php';


Core::init(array(
  'dsn' => 'sqlite:' . __DIR__ . '/../db/development.db',
));

session_start();

$app = new \Slim\Slim(array(
  'templates.path' => __DIR__ . '/templates',
  'view' => new LayoutView(),
  'debug' => false,
  'log.enabled' => true,
  'log.level' => \Slim\Log::DEBUG
));
$app->view()->setLayout('layout.phtml');

$app->error(function($e) use ($app) {
  $app->getLog()->error($e);
  if ($e instanceof RecordNotFound) {
    $app->notFound();
  }
});


$section = function($section) use ($app) {
  return function() use ($section, $app) {
    $app->view()->setData('section', $section);
  };
};

$users_index = function() use ($app) {
  $app->render('users/index.phtml', array(
    'users' => Core::list_users()
  ));
};

$users_show = function($id) use ($app) {
  $app->render('users/show.phtml', array(
    'user' => Core::get_user($id)
  ));
};

$users_update = function($id) use ($app) {
  Core::update_user($id, $app->request()->params('fullname'));
  $app->flash('success', 'Changes saved');
  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$users_toggle_suspend = function($id) use ($app) {
  Core::toggle_user_suspend($id);
  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$users_new = function() use ($app) {
  $user = new User();

  if ('POST' == $app->request()->getMethod()) {
    list($user, $errors) = Core::create_user(
      $app->request()->params('username'), 
      $app->request()->params('fullname')
    );

    if (!$errors) {
      $app->flash('success', 'User added');
      $app->redirect($app->urlFor('users'));
    }
  }

  $app->render('users/new.phtml', array(
    'user' => $user,
    'errors' => isset($errors) ? $errors : array()
  ));
};

$users_delete = function($id) use ($app) {
  Core::delete_user($id);
  $app->flash('success', 'User deleted');
  $app->redirect($app->urlFor('users'));
};

$users_config = function($id, $os) use ($app) {
  /* $user = DB::findUserById($id); */
  /* if (!$user) $app->notFound(); */

  /* $config = OpenVPN::getConfig($id, $os); */

  /* // FIXME: NOT WORKING! */
  /* $app->response()->header('Content-Type', 'application/octet-stream'); */
  /* $app->response()->header('Content-Length', filesize($config)); */
  /* $app->response()->header('Content-Disposition', */ 
  /*   sprintf('attachment; filename="%s"', basename($config)) */
  /* ); */
  /* $app->response()->header('Pragma', 'no-cache'); */
  /* readfile($config); */
};

$app->get('/', $section('users'), $users_index);
$app->get('/users', $section('users'), $users_index)->name('users');
$app->get('/users/new', $section('users'), $users_new)->name('users.new');
$app->post('/users/new', $section('users'), $users_new)->name('users.create');
$app->get('/users/:id', $section('users'), $users_show)->name('users.show');
$app->post('/users/:id', $section('users'), $users_update)->name('users.update');
$app->delete('/users/:id', $section('users'), $users_delete)->name('users.delete');
$app->post('/users/:id/toggle_suspend', $section('users'), $users_toggle_suspend)->name('users.toggle_suspend');
$app->get('/users/:id/config/:os', $section('users'), $users_config)->name('users.config');


$groups_index = function() use ($app) {
  $app->render('groups/index.phtml', array(
    'groups' => Core::list_groups()
  ));
};

$groups_show = function($id) use ($app) {
  $app->render('groups/show.phtml', array(
    'group' => Core::get_group($id)
  ));
};

$groups_update = function($id) use ($app) {
  $group = DB::findgroupById($id);
  if (!$group) $app->notFound();
  $group->fullname = $app->request()->params('fullname');
  DB::updategroup($group);

  $app->flash('success', 'Changes saved');
  $app->redirect($app->urlFor('groups.show', array('id' => $id)));
};

$groups_new = function() use ($app) {
  $group = new Group();

  if ('POST' == $app->request()->getMethod()) {
    $group->name = $app->request()->params('name');
    $group->description = $app->request()->params('description');

    if (!($errors = DB::saveGroup($group))) {
      $app->flash('success', 'Group added');
      $app->redirect($app->urlFor('groups'));
    }
  }

  $app->render('groups/new.phtml', array(
    'group' => $group,
    'errors' => isset($errors) ? $errors : array()
  ));
};

$groups_delete = function($id) use ($app) {
  $group = DB::findgroupById($id);
  if (!$group) $app->notFound();
  DB::deletegroup($group);

  $app->flash('success', 'group deleted');
  $app->redirect($app->urlFor('groups'));
};


$app->get('/groups', $section('groups'), $groups_index)->name('groups');
$app->get('/groups/new', $section('groups'), $groups_new)->name('groups.new');
$app->post('/groups/new', $section('groups'), $groups_new)->name('groups.create');
$app->get('/groups/:id', $section('groups'), $groups_show)->name('groups.show');
$app->post('/groups/:id', $section('groups'), $groups_update)->name('groups.update');
$app->delete('/groups/:id', $section('groups'), $groups_delete)->name('groups.delete');


return $app;