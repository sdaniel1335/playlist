<div class="container py-4" style="max-width: 720px;">
  <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <h1 class="h3 mb-0"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <a href="<?php echo url('/') . '?video=' . (int) $video['id']; ?>" class="btn btn-outline-secondary btn-sm">
      Back
    </a>
  </div>

  <form
    method="post"
    action="<?php echo url('/video/edit'); ?>"
    data-confirm="Save these changes?"
  >
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="id" value="<?php echo (int) $video['id']; ?>">

    <div class="mb-3">
      <label for="title" class="form-label">Title</label>
      <input
        type="text"
        class="form-control"
        id="title"
        name="title"
        maxlength="255"
        value="<?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>"
        required
        autofocus
      >
    </div>

    <div class="mb-3">
      <label for="url" class="form-label">Iframe URL</label>
      <input
        type="text"
        class="form-control"
        id="url"
        name="url"
        maxlength="2048"
        value="<?php echo htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8'); ?>"
        required
      >
    </div>

    <div class="mb-3">
      <label for="uri" class="form-label">URI</label>
      <input
        type="text"
        class="form-control"
        id="uri"
        name="uri"
        maxlength="255"
        value="<?php echo htmlspecialchars($video['uri'], ENT_QUOTES, 'UTF-8'); ?>"
      >
    </div>

    <div class="form-check mb-4">
      <input
        class="form-check-input"
        type="checkbox"
        value="1"
        id="hidden"
        name="hidden"
        <?php echo $video['hidden'] ? 'checked' : ''; ?>
      >
      <label class="form-check-label" for="hidden">
        Hidden video
      </label>
    </div>

    <button type="submit" class="btn btn-primary">Save changes</button>
  </form>
</div>
