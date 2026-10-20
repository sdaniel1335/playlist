<?php
$editing = isset($video);
$is_upload = $editing ? $video['filename'] !== null : $upload;
$escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
?>
<div class="container py-4" style="max-width: 720px;">
  <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <h1 class="h3 mb-0"><?php echo $escape($title); ?></h1>
    <a href="<?php echo url('/'); ?>" class="btn btn-outline-secondary btn-sm"><?php echo htmlspecialchars(lang('LANG_BACK'), ENT_QUOTES, 'UTF-8'); ?></a>
  </div>
  <form method="post" enctype="multipart/form-data" action="<?php echo url($editing ? '/video/edit' : '/video/add'); ?>" <?php if (!$editing && $is_upload) { ?>data-video-upload="<?php echo url('/video/upload-chunk'); ?>"<?php } ?> data-confirm="<?php echo $editing ? lang('LANG_SAVE_THESE_CHANGES') : lang('LANG_ADD_THIS_VIDEO'); ?>">
    <input type="hidden" name="_csrf" value="<?php echo $escape(auth_csrf_token()); ?>">
    <?php if ($editing) { ?>
      <input type="hidden" name="id" value="<?php echo (int) $video['id']; ?>">
    <?php } else { ?>
      <input type="hidden" name="upload" value="<?php echo $is_upload ? '1' : '0'; ?>">
    <?php } ?>
    <div class="mb-3">
      <label for="title" class="form-label"><?php echo htmlspecialchars(lang('LANG_TITLE'), ENT_QUOTES, 'UTF-8'); ?></label>
      <input type="text" class="form-control" id="title" name="title" maxlength="255" value="<?php echo $editing ? $escape($video['title']) : ''; ?>" required autofocus>
    </div>
    <?php if ($is_upload) { ?>
      <div class="mb-3">
        <?php if ($editing) { ?>
          <label for="filename" class="form-label"><?php echo htmlspecialchars(lang('LANG_FILENAME'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" class="form-control" id="filename" name="filename" maxlength="255" value="<?php echo $escape($video['filename']); ?>" required>
          <div class="form-text"><?php echo htmlspecialchars(lang('LANG_THE_UPLOADED_VIDEO_CONTENT_CANNOT_BE_REPLACED'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } else { ?>
          <label for="video_file" class="form-label"><?php echo htmlspecialchars(lang('LANG_VIDEO_FILE'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="file" class="form-control" id="video_file" name="video_file" accept=".mp4,.webm,.ogv" required>
          <div class="form-text"><?php echo htmlspecialchars(lang('LANG_MP4_WEBM_OR_OGG_UP_TO_700_MIB_PLAYBACK_DEPENDS_ON_BROWSER_CODEC_SUPPORT'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="mb-3">
        <label for="url" class="form-label"><?php echo htmlspecialchars(lang('LANG_IFRAME_URL'), ENT_QUOTES, 'UTF-8'); ?></label>
        <input type="text" class="form-control" id="url" name="url" maxlength="2048" value="<?php echo $editing ? $escape($video['url']) : 'https://www.youtube.com/embed/'; ?>" required>
      </div>
      <div class="mb-3">
        <label for="uri" class="form-label"><?php echo htmlspecialchars(lang('LANG_URI'), ENT_QUOTES, 'UTF-8'); ?></label>
        <input type="text" class="form-control" id="uri" name="uri" maxlength="255" value="<?php echo $editing ? $escape($video['uri']) : ''; ?>">
      </div>
    <?php } ?>
    <div class="mb-3">
      <label for="note" class="form-label"><?php echo htmlspecialchars(lang('LANG_NOTE'), ENT_QUOTES, 'UTF-8'); ?></label>
      <textarea class="form-control" id="note" name="note" rows="4" maxlength="65535"><?php echo $editing ? $escape($video['note']) : ''; ?></textarea>
    </div>
    <?php for ($i = 0; $i <= 10; $i++) { $column = $i === 0 ? 'cover' : 'image' . $i; ?>
      <div class="mb-3">
        <label for="<?php echo $column; ?>" class="form-label"><?php echo $i === 0 ? lang('LANG_COVER') : lang('LANG_IMAGE') . $i; ?></label>
        <?php if ($editing && $video['has_' . $column]) { ?>
          <div class="mb-2"><a href="<?php echo url('/video/media') . '?id=' . (int) $video['id'] . '&amp;image=' . $column; ?>" target="_blank" rel="noopener"><img src="<?php echo url('/video/media') . '?id=' . (int) $video['id'] . '&amp;image=' . $column; ?>" class="playlist-edit-image" alt="<?php echo $column; ?>"></a></div>
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="remove_<?php echo $column; ?>" name="remove_<?php echo $column; ?>" value="1">
            <label class="form-check-label" for="remove_<?php echo $column; ?>"><?php echo htmlspecialchars(lang('LANG_REMOVE_EXISTING_IMAGE'), ENT_QUOTES, 'UTF-8'); ?></label>
          </div>
        <?php } ?>
        <input type="file" class="form-control" id="<?php echo $column; ?>" name="<?php echo $column; ?>" accept="image/jpeg,image/png,image/gif,image/webp">
      </div>
    <?php } ?>
    <div class="form-text mb-3"><?php echo htmlspecialchars(lang('LANG_JPEG_PNG_GIF_OR_WEBP_UP_TO_10_MIB_EACH_A_NEW_IMAGE_REPLACES_THE_EXISTING_ONE'), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" value="1" id="hidden" name="hidden" <?php echo ($editing ? $video['hidden'] : $hidden_default) ? 'checked' : ''; ?>>
      <label class="form-check-label" for="hidden"><?php echo htmlspecialchars(lang('LANG_HIDDEN_VIDEO'), ENT_QUOTES, 'UTF-8'); ?></label>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $editing ? lang('LANG_SAVE_CHANGES') : ($is_upload ? lang('LANG_UPLOAD_VIDEO') : lang('LANG_ADD_VIDEO')); ?></button>
    <?php if (!$editing && $is_upload) { ?>
      <input type="hidden" name="upload_token" value="">
      <div class="mt-3" role="status" aria-live="polite" data-upload-status></div>
      <progress class="w-100 d-none" data-upload-progress max="100" value="0"></progress>
      <noscript><?php echo htmlspecialchars(lang('LANG_VIDEO_UPLOADS_REQUIRE_JAVASCRIPT_FOR_CHUNKED_TRANSFER'), ENT_QUOTES, 'UTF-8'); ?></noscript>
    <?php } ?>
  </form>
</div>
