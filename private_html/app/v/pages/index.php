<div class="playlist-page">
  <section class="playlist-player-sticky border-bottom shadow-sm">
    <div class="container-fluid px-0 py-2 py-md-3">
      <div class="d-flex align-items-center gap-2 mb-2 px-2 px-md-3">
        <div class="min-w-0 flex-grow-1">
          <div class="small text-body-secondary">
            <?php echo $hidden_mode ? lang('LANG_HIDDEN_PLAYLIST') : lang('LANG_PLAYLIST'); ?>
          </div>
          <div class="fw-semibold text-truncate">
            <?php if ($current_video !== null) { ?>
              <?php echo htmlspecialchars($current_video['title'], ENT_QUOTES, 'UTF-8'); ?>
            <?php } else { ?>
              <?php echo htmlspecialchars(lang('LANG_NO_VIDEOS'), ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
          </div>
        </div>

        <?php if (PLAYLIST_HIDDEN) { ?>
        <?php if ($hidden_mode) { ?>
          <form method="post" action="<?php echo url('/hidden-lock'); ?>" class="m-0" data-confirm="<?php echo htmlspecialchars(lang('LANG_LOCK_HIDDEN_VIDEOS_AND_RETURN_TO_THE_PUBLIC_PLAYLIST'), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="<?php echo htmlspecialchars(lang('LANG_LOCK_HIDDEN_VIDEOS'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(lang('LANG_LOCK_HIDDEN_VIDEOS'), ENT_QUOTES, 'UTF-8'); ?>">
              <i class="bi bi-unlock-fill" aria-hidden="true"></i>
            </button>
          </form>
        <?php } else { ?>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            id="playlist-hidden-unlock-button"
            title="<?php echo htmlspecialchars(lang('LANG_UNLOCK_HIDDEN_VIDEOS'), ENT_QUOTES, 'UTF-8'); ?>"
            aria-label="<?php echo htmlspecialchars(lang('LANG_UNLOCK_HIDDEN_VIDEOS'), ENT_QUOTES, 'UTF-8'); ?>"
          >
            <i class="bi bi-lock-fill" aria-hidden="true"></i>
          </button>

          <form method="post" action="<?php echo url('/hidden-unlock'); ?>" id="playlist-hidden-unlock-form" class="d-none">
            <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="hidden_password" id="playlist-hidden-password" value="">
          </form>
        <?php } ?>
        <?php } ?>
      </div>

      <?php if ($current_video !== null) { ?>
        <div class="playlist-player-frame bg-black overflow-hidden">
          <?php if ($current_video['filename'] !== null) { ?>
          <video controls playsinline preload="metadata" src="<?php echo url('/video/media') . '?id=' . (int) $current_video['id']; ?>" aria-label="<?php echo htmlspecialchars($current_video['title'], ENT_QUOTES, 'UTF-8'); ?>"></video>
          <?php } else { ?>
          <iframe
            src="<?php echo htmlspecialchars($current_video['url'] . $current_video['uri'], ENT_QUOTES, 'UTF-8'); ?>"
            title="<?php echo htmlspecialchars($current_video['title'], ENT_QUOTES, 'UTF-8'); ?>"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          ></iframe>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="playlist-empty-player border rounded p-4 text-center text-body-secondary">
          <?php echo $hidden_mode ? lang('LANG_NO_HIDDEN_VIDEOS') : lang('LANG_NO_PUBLIC_VIDEOS'); ?>
        </div>
      <?php } ?>
    </div>
  </section>

  <section class="container py-3 py-md-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
      <h1 class="h4 mb-0">
        <?php echo $hidden_mode ? lang('LANG_HIDDEN_VIDEOS') : lang('LANG_VIDEOS'); ?>
      </h1>
      <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-secondary">
          <?php echo count($videos); ?>
        </span>
        <a href="<?php echo url('/video/add') . '?upload=1'; ?>" class="btn btn-primary btn-sm" title="<?php echo htmlspecialchars(lang('LANG_UPLOAD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(lang('LANG_UPLOAD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>">
          <i class="bi bi-upload" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1"><?php echo htmlspecialchars(lang('LANG_UPLOAD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a
          href="<?php echo url('/video/add'); ?>"
          class="btn btn-primary btn-sm"
          title="<?php echo htmlspecialchars(lang('LANG_ADD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>"
          aria-label="<?php echo htmlspecialchars(lang('LANG_ADD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>"
        >
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          <span class="d-none d-sm-inline ms-1"><?php echo htmlspecialchars(lang('LANG_ADD_VIDEO'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </div>
    </div>

    <?php if (! empty($videos)) { ?>
      <div class="list-group playlist-list">
        <?php foreach ($videos as $video) { ?>
          <?php $active = $current_video !== null && $current_video['id'] === $video['id']; ?>
          <div class="list-group-item<?php echo $active ? ' active' : ''; ?>">
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
              <a
                href="<?php echo url('/') . '?video=' . (int) $video['id']; ?>"
                class="playlist-video-link flex-grow-1 min-w-0 text-decoration-none<?php echo $active ? ' text-white' : ' text-body'; ?>"
              >
                <div class="fw-semibold text-break">
                  <?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php if ($video['uri'] !== '') { ?>
                  <div class="small<?php echo $active ? ' text-white-50' : ' text-body-secondary'; ?> text-break">
                    <?php echo htmlspecialchars($video['uri'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php } ?>
              </a>

              <div class="d-flex gap-2 flex-shrink-0">
                <?php if ($video['filename'] !== null) { ?>
                  <span class="align-self-center" title="<?php echo htmlspecialchars(lang('LANG_UPLOADED_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(lang('LANG_UPLOADED_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-play" aria-hidden="true"></i></span>
                <?php } ?>
                <?php if ($video['has_cover']) { ?>
                  <span class="playlist-cover-icon align-self-center" tabindex="0" aria-label="<?php echo htmlspecialchars(lang('LANG_COVER_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-image" aria-hidden="true"></i>
                    <img class="playlist-cover-preview" loading="lazy" src="<?php echo url('/video/media') . '?id=' . (int) $video['id'] . '&amp;image=cover'; ?>" alt="<?php echo htmlspecialchars(lang('LANG_COVER_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>">
                  </span>
                <?php } ?>
                <?php if ($video['image_count'] > 0) { ?>
                  <span class="align-self-center" tabindex="0" title="<?php echo $video['image_count']; ?> <?php echo lang('LANG_IMAGES'); ?>" aria-label="<?php echo $video['image_count']; ?> <?php echo lang('LANG_IMAGES'); ?>"><i class="bi bi-images" aria-hidden="true"></i></span>
                <?php } ?>
                <a
                  href="<?php echo url('/video/edit') . '?id=' . (int) $video['id']; ?>"
                  class="btn btn-sm <?php echo $active ? 'btn-light' : 'btn-outline-secondary'; ?>"
                >
                  <i class="bi bi-pencil" aria-hidden="true"></i>
                  <span class="visually-hidden"><?php echo htmlspecialchars(lang('LANG_EDIT'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>

                <form
                  method="post"
                  action="<?php echo url('/video/delete'); ?>"
                  class="m-0"
                  data-confirm="<?php echo htmlspecialchars(lang('LANG_DELETE_THIS_VIDEO'), ENT_QUOTES, 'UTF-8'); ?>"
                >
                  <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="id" value="<?php echo (int) $video['id']; ?>">
                  <button type="submit" class="btn btn-sm <?php echo $active ? 'btn-light' : 'btn-outline-danger'; ?>">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span class="visually-hidden"><?php echo htmlspecialchars(lang('LANG_DELETE'), ENT_QUOTES, 'UTF-8'); ?></span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="alert alert-secondary mb-0">
        <?php echo $hidden_mode ? lang('LANG_THERE_ARE_NO_HIDDEN_VIDEOS') : lang('LANG_THERE_ARE_NO_PUBLIC_VIDEOS'); ?>
      </div>
    <?php } ?>
  </section>
</div>

