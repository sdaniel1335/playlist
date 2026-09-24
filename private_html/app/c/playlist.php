<?php

class Playlist extends App
{
  public function edit()
  {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
      header('HTTP/1.1 404 Not Found');
      die('Video not found.');
    }

    $user_id = auth_user_id();

    $stmt = $this->db->prepare(
      'SELECT id, url, uri, title, hidden FROM '
      . $this->table('playlist')
      . ' WHERE id = ? AND user_id = ? LIMIT 1'
    );

    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $stmt->bind_result(
      $video_id,
      $url,
      $uri,
      $video_title,
      $hidden
    );

    $found = $stmt->fetch();
    $stmt->close();

    if (! $found) {
      header('HTTP/1.1 404 Not Found');
      die('Video not found.');
    }

    $this->set(
      array(
        'title' => 'Edit video',
        'video' => array(
          'id' => (int) $video_id,
          'url' => $url,
          'uri' => $uri,
          'title' => $video_title,
          'hidden' => (int) $hidden
        )
      )
    );

    $this->render('playlist/edit');
  }

  public function editPost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    $uri = isset($_POST['uri']) ? trim($_POST['uri']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $hidden = isset($_POST['hidden']) ? 1 : 0;

    if ($id <= 0 || $url === '' || $title === '') {
      $this->flash('error', 'URL and title are required.');
      $this->redirect('/video/edit?id=' . $id);
    }

    if (strlen($url) > 2048 || strlen($uri) > 255 || strlen($title) > 255) {
      $this->flash('error', 'One or more fields are too long.');
      $this->redirect('/video/edit?id=' . $id);
    }

    $user_id = auth_user_id();

    $stmt = $this->db->prepare(
      'UPDATE '
      . $this->table('playlist')
      . ' SET url = ?, uri = ?, title = ?, hidden = ?'
      . ' WHERE id = ? AND user_id = ?'
    );

    $stmt->bind_param(
      'sssiii',
      $url,
      $uri,
      $title,
      $hidden,
      $id,
      $user_id
    );

    $stmt->execute();
    $stmt->close();

    $this->flash('success', 'Video updated.');

    $hidden_mode = isset($_SESSION['_playlist_hidden_unlocked'])
      && $_SESSION['_playlist_hidden_unlocked'] === true;

    if (($hidden_mode && $hidden === 1) || (! $hidden_mode && $hidden === 0)) {
      $this->redirect('/?video=' . $id);
    }

    $this->redirect('/');
  }

  public function deletePost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($id <= 0) {
      $this->flash('error', 'Invalid video.');
      $this->redirect('/');
    }

    $user_id = auth_user_id();

    $stmt = $this->db->prepare(
      'DELETE FROM '
      . $this->table('playlist')
      . ' WHERE id = ? AND user_id = ?'
    );

    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    $this->flash(
      $deleted ? 'success' : 'error',
      $deleted ? 'Video deleted.' : 'Video not found.'
    );

    $this->redirect('/');
  }
}
