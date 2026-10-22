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
    $hidden_mode = PLAYLIST_HIDDEN && isset($_SESSION['_playlist_hidden_unlocked'])
      && $_SESSION['_playlist_hidden_unlocked'] === true;

    $has_hidden_password = false;
    if (PLAYLIST_HIDDEN) {
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
    }

    if ($hidden_mode && ! $has_hidden_password) {
      unset($_SESSION['_playlist_hidden_unlocked']);
      $hidden_mode = false;
    }

    $hidden = $hidden_mode ? 1 : 0;
    $videos = array();
    $image_count_sql = array();
    for ($i = 1; $i <= 10; $i++) {
      $image_count_sql[] = '(image' . $i . ' IS NOT NULL)';
    }

    $stmt = $this->db->prepare(
      'SELECT id, url, uri, title, hidden, created_at, filename, (cover IS NOT NULL), '
      . implode(' + ', $image_count_sql) . ' FROM '
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
      $created_at,
      $filename,
      $has_cover,
      $image_count
    );

    while ($stmt->fetch()) {
      $videos[] = array(
        'id' => (int) $id,
        'url' => $url,
        'uri' => $uri,
        'title' => $video_title,
        'hidden' => (int) $video_hidden,
        'created_at' => $created_at,
        'filename' => $filename,
        'has_cover' => (bool) $has_cover,
        'image_count' => (int) $image_count
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
      $this->flash('error', lang('LANG_HIDDEN_PASSWORD_IS_REQUIRED'));
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
      $this->flash('error', lang('LANG_SET_A_HIDDEN_PASSWORD_FIRST'));
      $this->redirect('/');
    }

    if (! auth_password_check($password, $password_hash)) {
      $this->flash('error', lang('LANG_INVALID_HIDDEN_PASSWORD'));
      $this->redirect('/');
    }

    $_SESSION['_playlist_hidden_unlocked'] = true;
    session_regenerate_id(true);

    $this->flash('success', lang('LANG_HIDDEN_VIDEOS_UNLOCKED'));
    $this->redirect('/');
  }

  public function hiddenLockPost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    unset($_SESSION['_playlist_hidden_unlocked']);
    session_regenerate_id(true);

    $this->flash('success', lang('LANG_HIDDEN_VIDEOS_LOCKED'));
    $this->redirect('/');
  }
}
