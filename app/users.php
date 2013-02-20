<?php

$users_index = function() use ($app) {
  $users = DB::findUsers();

  $app->render('users/index.phtml', array(
    'section' => 'users',
    'users' => $users
  ));
};

$app->get('/users', $users_index)->name('users');
