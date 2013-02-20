<?php

$groups_index = function() use ($app) {
  $app->render('groups/index.phtml', array('section' => 'groups'));
};

$app->get('/groups', $groups_index)->name('groups');
