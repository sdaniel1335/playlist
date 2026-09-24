<?php

class Settings extends App
{
  public function hiddenPassword()
  {
    $this->requireLogin();

    $user_id = auth_user_id();

    $stmt = $this->db->prepare(
      'SELECT password_hash FROM '
      . $this->table('playlist_hidden')
      . ' WHERE user_id = ? LIMIT 1'
    );

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);

    $has_hidden_password = $stmt->fetch();
    $stmt->close();

    $this->set(
      array(
        'title' => $has_hidden_password
          ? 'Change hidden password'
          : 'Set hidden password',
        'has_hidden_password' => (bool) $has_hidden_password
      )
    );

    $this->render('settings/hidden_password');
  }

  public function hiddenPasswordPost()
  {
    $this->requireLogin();
    $this->requireCsrf();

    $current_password = isset($_POST['current_password'])
      ? $_POST['current_password']
      : '';

    $new_password = isset($_POST['new_password'])
      ? $_POST['new_password']
      : '';

    $new_password_confirm = isset($_POST['new_password_confirm'])
      ? $_POST['new_password_confirm']
      : '';

    if ($new_password === '' || $new_password_confirm === '') {
      $this->flash('error', 'The new password fields are required.');
      $this->redirect('/hidden-password');
    }

    if (strlen($new_password) < 4) {
      $this->flash('error', 'The new password must be at least 4 characters.');
      $this->redirect('/hidden-password');
    }

    if ($new_password !== $new_password_confirm) {
      $this->flash('error', 'The new passwords do not match.');
      $this->redirect('/hidden-password');
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

    $has_hidden_password = $stmt->fetch();
    $stmt->close();

    if ($has_hidden_password) {
      if (
        $current_password === ''
        || !auth_password_check($current_password, $password_hash)
      ) {
        $this->flash('error', 'Current hidden password is incorrect.');
        $this->redirect('/hidden-password');
      }
    }

    $new_hash = auth_password_make($new_password);

    if ($has_hidden_password) {
      $stmt = $this->db->prepare(
        'UPDATE '
        . $this->table('playlist_hidden')
        . ' SET password_hash = ? WHERE user_id = ?'
      );

      $stmt->bind_param('si', $new_hash, $user_id);
    } else {
      $stmt = $this->db->prepare(
        'INSERT INTO '
        . $this->table('playlist_hidden')
        . ' (user_id, password_hash) VALUES (?, ?)'
      );

      $stmt->bind_param('is', $user_id, $new_hash);
    }

    $stmt->execute();
    $stmt->close();

    unset($_SESSION['_playlist_hidden_unlocked']);

    $this->flash(
      'success',
      $has_hidden_password
        ? 'Hidden password changed.'
        : 'Hidden password set.'
    );

    $this->redirect('/hidden-password');
  }
}

