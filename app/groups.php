<?php

$groups_index = function() use ($app) {
  $groups = DB::findGroups();

  $app->render('groups/index.phtml', array(
    'groups' => $groups
  ));
};

$groups_show = function($id) use ($app) {
  $group = DB::findgroupById($id);
  if (!$group) $app->notFound();
  $app->render('groups/show.phtml', array(
    'group' => $group
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

