<?php

class Playlist extends App
{
  const VIDEO_PART_SIZE = 134217728;

  private function videoColumns()
  {
    return array('video_blob', 'video_blob2', 'video_blob3', 'video_blob4', 'video_blob5', 'video_blob6');
  }

  public function uploadChunk()
  {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    try {
      if (!auth_csrf_valid(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
        throw new Exception('Session token expired or request body was rejected. Reload the page.');
      }
      $offset = isset($_POST['offset']) ? (int) $_POST['offset'] : -1;
      $total = isset($_POST['total']) ? (int) $_POST['total'] : 0;
      $token = isset($_POST['token']) ? $_POST['token'] : '';
      if ($total <= 0 || $total > 734003200 || $offset < 0) { throw new Exception('Invalid video size.'); }
      if (!isset($_FILES['chunk'])) { throw new Exception('Chunk missing: check PHP and web-server request limits.'); }
      if ($_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
        $errors = array(1 => 'PHP upload_max_filesize exceeded', 2 => 'form size limit exceeded',
          3 => 'only part of the chunk arrived', 4 => 'chunk missing', 6 => 'temporary directory missing',
          7 => 'cannot write temporary file (disk space or quota)', 8 => 'PHP extension stopped upload');
        $code = (int) $_FILES['chunk']['error'];
        throw new Exception('Chunk upload failed: ' . (isset($errors[$code]) ? $errors[$code] : 'PHP error ' . $code) . '.');
      }
      if (!is_uploaded_file($_FILES['chunk']['tmp_name'])) { throw new Exception('Chunk temporary file is unavailable.'); }
      $chunk = $_FILES['chunk'];
      if ($chunk['size'] <= 0 || $chunk['size'] > 8388608 || $offset + $chunk['size'] > $total) {
        throw new Exception('Invalid chunk size.');
      }
      if ($token === '') {
        if ($offset !== 0) { throw new Exception('Start upload from the beginning.'); }
        $name = isset($_POST['filename']) ? basename(str_replace('\\', '/', $_POST['filename'])) : '';
        if ($name === '' || strlen($name) > 255 || !in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), array('mp4', 'webm', 'ogv'))) {
          throw new Exception('Use an MP4, WebM or Ogg file with a filename up to 255 bytes.');
        }
        // One staged upload per session; restarting also removes an abandoned upload.
        if (isset($_SESSION['_video_upload'])) { @unlink($_SESSION['_video_upload']['path']); }
        $path = tempnam(sys_get_temp_dir(), 'playlist-video-');
        if ($path === false) { throw new Exception('Cannot create upload temporary file.'); }
        @chmod($path, 0600);
        $token = bin2hex(auth_random_bytes(32));
        $_SESSION['_video_upload'] = array('token' => $token, 'path' => $path,
          'filename' => $name, 'size' => $total, 'received' => 0, 'time' => time(), 'user' => auth_user_id());
      }
      $upload = $this->stagedUpload($token);
      $hash = @hash_file('sha256', $chunk['tmp_name']);
      if ($hash === false) { throw new Exception('Cannot read chunk temporary file.'); }
      // A response may be lost after the chunk was saved. A verified replay is safe.
      if ($upload['size'] === $total && $upload['received'] === $offset + (int) $chunk['size']
        && isset($upload['last_offset'], $upload['last_hash']) && $upload['last_offset'] === $offset
        && auth_hash_equals($upload['last_hash'], $hash)) {
        echo json_encode(array('token' => $token, 'received' => $upload['received']));
        exit;
      }
      if ($upload['size'] !== $total || $upload['received'] !== $offset) {
        throw new Exception('Unexpected chunk offset: server has ' . $upload['received'] . ' bytes, request starts at ' . $offset . '. Restart the upload.');
      }
      $input = @fopen($chunk['tmp_name'], 'rb');
      $output = @fopen($upload['path'], 'c+b');
      if (!$input || !$output) {
        if ($input) { fclose($input); }
        if ($output) { fclose($output); }
        throw new Exception('Cannot open upload temporary file.');
      }
      if (!@ftruncate($output, $offset) || @fseek($output, $offset) !== 0) {
        fclose($input); fclose($output);
        throw new Exception('Cannot seek upload temporary file.');
      }
      $written = @stream_copy_to_stream($input, $output);
      fclose($input);
      fclose($output);
      if ($written !== (int) $chunk['size']) { throw new Exception('Cannot write upload: check temporary disk space.'); }
      $_SESSION['_video_upload']['received'] += $written;
      $_SESSION['_video_upload']['last_offset'] = $offset;
      $_SESSION['_video_upload']['last_hash'] = $hash;
      $_SESSION['_video_upload']['time'] = time();
      echo json_encode(array('token' => $token, 'received' => $_SESSION['_video_upload']['received']));
    } catch (Exception $e) {
      header('HTTP/1.1 400 Bad Request');
      echo json_encode(array('error' => $e->getMessage()));
    }
    exit;
  }

  private function stagedUpload($token)
  {
    if (!isset($_SESSION['_video_upload'])) { throw new Exception('Upload expired. Select the file again.'); }
    $upload = $_SESSION['_video_upload'];
    if (!is_string($token) || !auth_hash_equals($upload['token'], $token)
      || $upload['user'] !== auth_user_id() || time() - $upload['time'] > 86400 || !is_file($upload['path'])) {
      throw new Exception('Upload expired. Select the file again.');
    }
    return $upload;
  }

  private function validateVideo($file)
  {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $head = file_get_contents($file['tmp_name'], false, null, 0, 65536);
    if ($head === false) { throw new Exception('Cannot read uploaded video.'); }
    $mime = $this->videoMime($head);
    $allowed = array('mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg');
    if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      throw new Exception('Video format could not be recognized (detected: ' . $finfo->buffer($head)
        . '). Expected MP4, WebM or Ogg.');
    }
  }

  private function videoMime($head)
  {
    // ISO BMFF files are sometimes reported as application/octet-stream or
    // video/quicktime by older fileinfo databases. Inspect bounded box headers.
    $position = 0;
    $available = strlen($head);
    while ($position + 8 <= $available) {
      $size = unpack('Nsize', substr($head, $position, 4));
      $size = $size['size'];
      $type = substr($head, $position + 4, 4);
      if ($type === 'ftyp' && $size >= 16 && $position + $size <= $available && $size % 4 === 0) {
        $brands = substr($head, $position + 8, 4) . substr($head, $position + 16, $size - 16);
        for ($i = 0; $i < strlen($brands); $i += 4) {
          if (in_array(substr($brands, $i, 4), array('isom', 'iso2', 'iso3', 'iso4', 'iso5', 'iso6',
            'iso7', 'iso8', 'iso9', 'mp41', 'mp42', 'avc1', 'dash', 'M4V ', 'MSNV'))) {
            return 'video/mp4';
          }
        }
        break;
      }
      if ($size < 8 || $position + $size > $available) { break; }
      $position += $size;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($head);
    if (in_array($mime, array('video/mp4', 'application/mp4'))) { return 'video/mp4'; }
    if (substr($head, 0, 4) === "\x1a\x45\xdf\xa3"
      && ($mime === 'video/webm' || strpos($head, 'webm') !== false)) { return 'video/webm'; }
    if (substr($head, 0, 4) === 'OggS'
      && (in_array($mime, array('video/ogg', 'application/ogg')) || strpos($head, 'theora') !== false)) {
      return 'video/ogg';
    }
    return null;
  }

  private function imageColumns()
  {
    $columns = array('cover');
    for ($i = 1; $i <= 10; $i++) { $columns[] = 'image' . $i; }
    return $columns;
  }

  private function notFound()
  {
    header('HTTP/1.1 404 Not Found');
    die('Video not found.');
  }

  private function metadata($id)
  {
    $user = auth_user_id();
    $keys = array('id', 'url', 'uri', 'title', 'hidden', 'filename', 'note');
    $select = implode(',', $keys);
    foreach ($this->imageColumns() as $column) {
      $select .= ', (' . $column . ' IS NOT NULL)';
      $keys[] = 'has_' . $column;
    }
    $stmt = $this->db->prepare('SELECT ' . $select . ' FROM ' . $this->table('playlist') . ' WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $user);
    $stmt->execute();
    $row = array();
    $refs = array();
    foreach ($keys as $key) { $row[$key] = null; $refs[] =& $row[$key]; }
    call_user_func_array(array($stmt, 'bind_result'), $refs);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) { $this->notFound(); }
    return $row;
  }

  public function add()
  {
    $upload = isset($_GET['upload']);
    $this->set(array('title' => $upload ? 'Upload video' : 'Add video',
      'upload' => $upload, 'hidden_default' => !empty($_SESSION['_playlist_hidden_unlocked'])));
    $this->render('playlist/add');
  }

  public function edit()
  {
    $video = $this->metadata(isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $this->set(array('title' => 'Edit video', 'video' => $video));
    $this->render('playlist/edit');
  }

  private function uploadedFile($name, $video = false)
  {
    if (!isset($_FILES[$name]) || $_FILES[$name]['error'] === UPLOAD_ERR_NO_FILE) { return null; }
    $file = $_FILES[$name];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
      throw new Exception('Upload failed for ' . $name . '. Check server upload limits.');
    }
    if ($file['size'] <= 0 || $file['size'] > ($video ? 734003200 : 10485760)) {
      throw new Exception($video ? 'Video must be at most 700 MiB.' : 'Each image must be at most 10 MiB.');
    }
    if ($video) {
      $this->validateVideo($file);
    } else {
      $info = @getimagesize($file['tmp_name']);
      if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, 18))) {
        throw new Exception('Images must be JPEG, PNG, GIF or WebP.');
      }
    }
    return $file;
  }

  public function addPost() { $this->save(false); }
  public function editPost() { $this->save(true); }

  private function save($editing)
  {
    $this->requireLogin();
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
      header('HTTP/1.1 413 Payload Too Large');
      die('Upload exceeds the server post_max_size limit.');
    }
    $this->requireCsrf();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $existing = $editing ? $this->metadata($id) : null;
    $upload = $editing ? $existing['filename'] !== null : !empty($_POST['upload']);
    $back = $editing ? '/video/edit?id=' . $id : '/video/add' . ($upload ? '?upload=1' : '');
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $url = $upload ? '' : (isset($_POST['url']) ? trim($_POST['url']) : '');
    $uri = $upload ? '' : (isset($_POST['uri']) ? trim($_POST['uri']) : '');
    $note = isset($_POST['note']) ? $_POST['note'] : '';
    $hidden = isset($_POST['hidden']) ? 1 : 0;
    $user = auth_user_id();
    $transaction = false;
    try {
      if ($title === '' || (!$upload && $url === '')) { throw new Exception('Title and iframe URL (for embedded videos) are required.'); }
      if (strlen($title) > 255 || strlen($url) > 2048 || strlen($uri) > 255 || strlen($note) > 65535) {
        throw new Exception('One or more fields are too long.');
      }
      $staged = null;
      $file = null;
      if (!$editing && $upload && !empty($_POST['upload_token'])) {
        $staged = $this->stagedUpload($_POST['upload_token']);
        if ($staged['received'] !== $staged['size'] || filesize($staged['path']) !== $staged['size']) {
          throw new Exception('Video upload is incomplete.');
        }
        $file = array('name' => $staged['filename'], 'tmp_name' => $staged['path'], 'size' => $staged['size']);
        $this->validateVideo($file);
      } elseif (!$editing && $upload) {
        $file = $this->uploadedFile('video_file', true);
      }
      if (!$editing && $upload && !$file) { throw new Exception('Select a video file.'); }
      $images = array();
      foreach ($this->imageColumns() as $column) { $images[$column] = $this->uploadedFile($column); }
      $this->db->autocommit(false);
      $transaction = true;
      if ($editing) {
        $filename = $upload ? (isset($_POST['filename']) ? trim($_POST['filename']) : $existing['filename']) : null;
        if ($upload && ($filename === '' || strlen($filename) > 255)) { throw new Exception('Filename is required and must be at most 255 bytes.'); }
        $stmt = $this->db->prepare('UPDATE ' . $this->table('playlist')
          . ' SET url=?, uri=?, title=?, hidden=?, note=?, filename=? WHERE id=? AND user_id=?');
        $stmt->bind_param('sssissii', $url, $uri, $title, $hidden, $note, $filename, $id, $user);
      } else {
        $filename = $file ? basename(str_replace('\\', '/', $file['name'])) : null;
        if ($filename !== null && strlen($filename) > 255) { throw new Exception('Filename is too long.'); }
        $stmt = $this->db->prepare('INSERT INTO ' . $this->table('playlist')
          . ' (user_id,url,uri,title,hidden,note,filename) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('isssiss', $user, $url, $uri, $title, $hidden, $note, $filename);
      }
      $stmt->execute();
      if (!$editing) { $id = (int) $stmt->insert_id; }
      $stmt->close();
      if ($file) {
        @set_time_limit(0);
        $part = 0;
        foreach ($this->videoColumns() as $column) {
          $offset = $part++ * self::VIDEO_PART_SIZE;
          if ($offset >= $file['size']) { break; }
          $this->storeFile($id, $column, $file['tmp_name'], $offset, min(self::VIDEO_PART_SIZE, $file['size'] - $offset));
        }
      }
      foreach ($images as $column => $image) {
        if ($image) { $this->storeFile($id, $column, $image['tmp_name']); }
        elseif ($editing && isset($_POST['remove_' . $column])) {
          $stmt = $this->db->prepare('UPDATE ' . $this->table('playlist') . ' SET ' . $column . '=NULL WHERE id=? AND user_id=?');
          $stmt->bind_param('ii', $id, $user);
          $stmt->execute();
          $stmt->close();
        }
      }
      $this->db->commit();
      $this->db->autocommit(true);
      if ($staged) { @unlink($staged['path']); unset($_SESSION['_video_upload']); }
    } catch (Exception $e) {
      if ($transaction) { $this->db->rollback(); $this->db->autocommit(true); }
      error_log($e->getMessage());
      $this->flash('error', $e instanceof mysqli_sql_exception ? 'Database save failed. Check server limits and logs.' : $e->getMessage());
      $this->redirect($back);
    }
    $this->flash('success', $editing ? 'Video updated.' : 'Video added.');
    $mode = !empty($_SESSION['_playlist_hidden_unlocked']);
    $this->redirect($mode === (bool) $hidden ? '/?video=' . $id : '/');
  }

  private function storeFile($id, $column, $path, $offset = 0, $length = null)
  {
    $stmt = $this->db->prepare('UPDATE ' . $this->table('playlist') . ' SET ' . $column . '=? WHERE id=?');
    $blob = null;
    $stmt->bind_param('bi', $blob, $id);
    $handle = fopen($path, 'rb');
    if (!$handle) { throw new Exception('Cannot read uploaded file.'); }
    if (fseek($handle, $offset) !== 0) { fclose($handle); throw new Exception('Cannot seek uploaded file.'); }
    $remaining = $length === null ? filesize($path) : $length;
    while ($remaining > 0) {
      $chunk = fread($handle, min(1048576, $remaining));
      if ($chunk === false) { fclose($handle); throw new Exception('Cannot read uploaded file.'); }
      if ($chunk === '') { fclose($handle); throw new Exception('Unexpected end of uploaded file.'); }
      $stmt->send_long_data(0, $chunk);
      $remaining -= strlen($chunk);
    }
    fclose($handle);
    $stmt->execute();
    $stmt->close();
  }

  public function media()
  {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $row = $this->metadata($id);
    if ($row['hidden'] && empty($_SESSION['_playlist_hidden_unlocked'])) { $this->notFound(); }
    $column = isset($_GET['image']) ? $_GET['image'] : 'video_blob';
    if ($column !== 'video_blob' && !in_array($column, $this->imageColumns(), true)) { $this->notFound(); }
    $user = auth_user_id();
    $columns = $column === 'video_blob' ? $this->videoColumns() : array($column);
    $size_sql = array();
    foreach ($columns as $part_column) { $size_sql[] = 'COALESCE(OCTET_LENGTH(' . $part_column . '),0)'; }
    $stmt = $this->db->prepare('SELECT ' . implode(',', $size_sql) . ', SUBSTRING(' . $column . ',1,65536) FROM '
      . $this->table('playlist') . ' WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $user);
    $stmt->execute();
    $sizes = array_fill(0, count($columns), 0);
    $refs = array();
    foreach ($sizes as &$part_size) { $refs[] =& $part_size; }
    unset($part_size);
    $refs[] =& $head;
    call_user_func_array(array($stmt, 'bind_result'), $refs);
    $stmt->fetch();
    $stmt->close();
    $size = array_sum($sizes);
    if (!$size) { $this->notFound(); }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $column === 'video_blob' ? $this->videoMime($head) : $finfo->buffer($head);
    if ($mime === null) { $mime = 'application/octet-stream'; }
    if ($mime === 'application/ogg') { $mime = 'video/ogg'; }
    if ($mime === 'application/mp4') { $mime = 'video/mp4'; }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Accept-Ranges: bytes');
    $start = 0;
    $end = $size - 1;
    if (isset($_SERVER['HTTP_RANGE'])) {
      if (!preg_match('/^bytes=(\d*)-(\d*)$/D', $_SERVER['HTTP_RANGE'], $matches) || ($matches[1] === '' && $matches[2] === '')) {
        header('HTTP/1.1 416 Range Not Satisfiable'); header('Content-Range: bytes */' . $size); exit;
      }
      $start = $matches[1] === '' ? max(0, $size - (int) $matches[2]) : (int) $matches[1];
      if ($matches[1] !== '' && $matches[2] !== '') { $end = min($end, (int) $matches[2]); }
      if ($start > $end || $start >= $size) {
        header('HTTP/1.1 416 Range Not Satisfiable'); header('Content-Range: bytes */' . $size); exit;
      }
      header('HTTP/1.1 206 Partial Content');
      header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    header('Content-Length: ' . ($end - $start + 1));
    session_write_close();
    @set_time_limit(0);
    while (ob_get_level() > 0) { ob_end_clean(); }
    $part_index = 0;
    $part_start = 0;
    while ($start <= $end && !connection_aborted()) {
      while ($part_index < count($sizes) && $start >= $part_start + $sizes[$part_index]) {
        $part_start += $sizes[$part_index++];
      }
      if ($part_index >= count($sizes)) { break; }
      $offset = $start - $part_start + 1;
      $length = min(1048576, $end - $start + 1, $sizes[$part_index] - ($offset - 1));
      $stmt = $this->db->prepare('SELECT SUBSTRING(' . $columns[$part_index] . ',?,?) FROM ' . $this->table('playlist') . ' WHERE id=? AND user_id=?');
      $stmt->bind_param('iiii', $offset, $length, $id, $user);
      $stmt->execute();
      $stmt->bind_result($chunk);
      $stmt->fetch();
      $stmt->free_result();
      $stmt->close();
      if ($chunk === null || strlen($chunk) === 0) { break; }
      echo $chunk;
      flush();
      $start += strlen($chunk);
    }
    exit;
  }

  public function deletePost()
  {
    $this->requireLogin();
    $this->requireCsrf();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $user = auth_user_id();
    $stmt = $this->db->prepare('DELETE FROM ' . $this->table('playlist') . ' WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $user);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    $this->flash($deleted ? 'success' : 'error', $deleted ? 'Video deleted.' : 'Video not found.');
    $this->redirect('/');
  }
}
