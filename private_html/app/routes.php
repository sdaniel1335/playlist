<?php

return array(
  'GET' => array(
    '/' => array(
      'controller' => 'pages',
      'class' => 'Pages',
      'action' => 'index'
    ),
    '/login' => array(
      'controller' => 'auth',
      'class' => 'Auth',
      'action' => 'login'
    ),
    '/password' => array(
      'controller' => 'auth',
      'class' => 'Auth',
      'action' => 'password'
    ),
    '/hidden-password' => array(
      'controller' => 'settings',
      'class' => 'Settings',
      'action' => 'hiddenPassword'
    ),
    '/video/edit' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'edit'
    )
  ),
  'POST' => array(
    '/login' => array(
      'controller' => 'auth',
      'class' => 'Auth',
      'action' => 'loginPost'
    ),
    '/logout' => array(
      'controller' => 'auth',
      'class' => 'Auth',
      'action' => 'logout'
    ),
    '/password' => array(
      'controller' => 'auth',
      'class' => 'Auth',
      'action' => 'passwordPost'
    ),
    '/hidden-password' => array(
      'controller' => 'settings',
      'class' => 'Settings',
      'action' => 'hiddenPasswordPost'
    ),
    '/hidden-unlock' => array(
      'controller' => 'pages',
      'class' => 'Pages',
      'action' => 'hiddenUnlockPost'
    ),
    '/hidden-lock' => array(
      'controller' => 'pages',
      'class' => 'Pages',
      'action' => 'hiddenLockPost'
    ),
    '/video/edit' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'editPost'
    ),
    '/video/delete' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'deletePost'
    )
  )
);
