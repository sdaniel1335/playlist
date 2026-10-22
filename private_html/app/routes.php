<?php

$routes = array(
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
    '/video/media' => array('controller' => 'playlist', 'class' => 'Playlist', 'action' => 'media'),
    '/video/add' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'add'
    ),
    '/video/edit' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'edit'
    )
  ),
  'POST' => array(
    '/video/upload-chunk' => array('controller' => 'playlist', 'class' => 'Playlist', 'action' => 'uploadChunk'),
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
    '/video/add' => array(
      'controller' => 'playlist',
      'class' => 'Playlist',
      'action' => 'addPost'
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

if (!PLAYLIST_HIDDEN) {
  unset($routes['GET']['/hidden-password'], $routes['POST']['/hidden-password'],
    $routes['POST']['/hidden-unlock'], $routes['POST']['/hidden-lock']);
}

return $routes;

