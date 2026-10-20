<nav class="app-navigation container d-flex flex-wrap align-items-center gap-3">
  <a class="app-brand" href="<?php echo url('/'); ?>"><span class="app-brand-icon" aria-hidden="true"><i class="bi bi-play-btn"></i></span><?php echo htmlspecialchars(APP_TITLE); ?></a>

  <?php if (auth_logged_in()) { ?>
    <div class="app-account">
    <span class="app-user">
      <i class="bi bi-person-circle" aria-hidden="true"></i>
      <?php echo htmlspecialchars(auth_username(), ENT_QUOTES, 'UTF-8'); ?>
    </span>

    <a href="<?php echo url('/hidden-password'); ?>"><?php echo htmlspecialchars(lang('LANG_HIDDEN_PASSWORD'), ENT_QUOTES, 'UTF-8'); ?></a>
    <a href="<?php echo url('/password'); ?>"><?php echo htmlspecialchars(lang('LANG_CHANGE_PASSWORD'), ENT_QUOTES, 'UTF-8'); ?></a>

    <form method="post" action="<?php echo url('/logout'); ?>" class="m-0">
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <button type="submit" class="btn btn-outline-secondary app-logout"><?php echo htmlspecialchars(lang('LANG_LOGOUT'), ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
    </div>
  <?php } ?>
</nav>
