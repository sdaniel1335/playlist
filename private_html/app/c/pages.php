<?php

class Pages extends App
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    $user_id = auth_user_id();
    $hidden_mode = isset($_SESSION['_playlist_hidden_unlocked'])
      && $_SESSION['_playlist_hidden_unlocked'] === true;

    $stmt = $this->db->prepare(
      'SELECT id FROM '
      . $this->table('playlist_hidden')
      . ' WHERE user_id = ? LIMIT 1'
    );

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($hidden_password_id);
    $has_hidden_password = $stmt->fetch();
    $stmt->close();

    if ($hidden_mode && ! $has_hidden_password) {
      unset($_SESSION['_playlist_hidden_unlocked']);
      $hidden_mode = false;
    }

    $hidden = $hidden_mode ? 1 : 0;
    $videos = array();

    $stmt = $this->db->prepare(
      'SELECT id, url, uri, title, hidden, created_at FROM '
      . $this->table('playlist')
      . ' WHERE user_id = ? AND hidden = ?'
      . ' ORDER BY created_at DESC, id DESC'
    );

    $stmt->bind_param('ii', $user_id, $hidden);
    $stmt->execute();
    $stmt->bind_result(
      $id,
      $url,
      $uri,
      $video_title,
      $video_hidden,
      $created_at
    );

    while ($stmt->fetch()) {
      $videos[] = array(
        'id' => (int) $id,
        'url' => $url,
        'uri' => $uri,
        'title' => $video_title,
        'hidden' => (int) $video_hidden,
        'created_at' => $created_at
      );
    }

    $stmt->close();

    $requested_id = isset($_GET['video'])
      ? (int) $_GET['video']
      : 0;

    $current_video = null;

    if ($requested_id > 0) {
      foreach ($videos as $video) {
        if ($video['id'] === $requested_id) {
          $current_video = $video;
          break;
        }
      }
    }

    if ($current_video === null && ! empty($videos)) {
      $current_video = $videos[0];
      $this->redirect('/?video=' . $current_video['id']);
    }

    if ($current_video === null && $requested_id > 0) {
      $this->redirect('/');
    }

    $this->set(
      array(
        'title' => APP_TITLE,
        'videos' => $videos,
        'current_video' => $current_video,
        'hidden_mode' => $hidden_mode,
        'has_hidden_password' => (bool) $has_hidden_password
      )
    );

    $this->render('pages/index');
  }

  public function hiddenUnlockPost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    $password = isset($_POST['hidden_password'])
      ? $_POST['hidden_password']
      : '';

    if ($password === '') {
      $this->flash('error', 'Hidden password is required.');
      $this->redirect('/');
    }

    $user_id = auth_user_id();

    $stmt = $this->db->prepare(
      'SELECT password_hash FROM '
      . $this->table('playlist_hidden')
      . ' WHERE user_id = ? LIMIT 1'
    );

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    $found = $stmt->fetch();
    $stmt->close();

    if (! $found) {
      $this->flash('error', 'Set a hidden password first.');
      $this->redirect('/');
    }

    if (! auth_password_check($password, $password_hash)) {
      $this->flash('error', 'Invalid hidden password.');
      $this->redirect('/');
    }

    $_SESSION['_playlist_hidden_unlocked'] = true;
    session_regenerate_id(true);

    $this->flash('success', 'Hidden videos unlocked.');
    $this->redirect('/');
  }

  public function hiddenLockPost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    unset($_SESSION['_playlist_hidden_unlocked']);
    session_regenerate_id(true);

    $this->flash('success', 'Hidden videos locked.');
    $this->redirect('/');
  }
}
