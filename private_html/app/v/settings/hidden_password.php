<div class="container py-4" style="max-width: 480px;">
  <h1 class="mb-4"><?php echo htmlspecialchars($title); ?></h1>

  <form method="post" action="<?php echo url('/hidden-password'); ?>">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

    <?php if ($has_hidden_password) { ?>
      <div class="mb-3">
        <label for="current_password" class="form-label">Current hidden password</label>
        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required autofocus>
      </div>
    <?php } ?>

    <div class="mb-3">
      <label for="new_password" class="form-label"><?php echo $has_hidden_password ? 'New hidden password' : 'Hidden password'; ?></label>
      <input type="password" class="form-control" id="new_password" name="new_password" minlength="4" autocomplete="new-password" required<?php echo ! $has_hidden_password ? ' autofocus' : ''; ?>>
    </div>

    <div class="mb-3">
      <label for="new_password_confirm" class="form-label">Confirm new hidden password</label>
      <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" minlength="4" autocomplete="new-password" required>
    </div>

    <button type="submit" class="btn btn-primary">
      <?php echo $has_hidden_password ? 'Change hidden password' : 'Set hidden password'; ?>
    </button>
  </form>
</div>
