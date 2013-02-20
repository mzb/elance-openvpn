<?php

$users_index = function() use ($app) {
  $users = DB::findUsers();

  $app->render('users/index.phtml', array(
    'users' => $users
  ));
};

$users_show = function($id) use ($app) {
  $user = DB::findUserById($id);
  $app->render('users/show.phtml', array(
    'user' => $user
  ));
};

$users_update = function($id) use ($app) {
  $user = DB::findUserById($id);
  $user->fullname = $app->request()->params('fullname');
  DB::updateUser($user);

  $app->flash('success', 'Changes saved');
  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$users_toggle_suspend = function($id) use ($app) {
  $user = DB::findUserById($id);
  $user->suspended = !$user->suspended;
  DB::updateUser($user);

  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$app->get('/users', $section('users'), $users_index)->name('users');
$app->get('/users/:id', $section('users'), $users_show)->name('users.show');
$app->post('/users/:id', $section('users'), $users_update)->name('users.update');
$app->post('/users/:id/toggle_suspend', $section('users'), $users_toggle_suspend)->name('users.toggle_suspend');
