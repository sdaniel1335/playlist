<?php
$module_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$current_module = realpath(ROOT);
$modules = array();
if ($module_root !== false && is_dir($module_root) && is_readable($module_root)) {
  $entries = scandir($module_root);
  if ($entries !== false) {
    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') { continue; }
      $directory = $module_root . DIRECTORY_SEPARATOR . $entry;
      if (is_dir($directory) && is_file($directory . DIRECTORY_SEPARATOR . 'MODULE')) {
        $modules[$entry] = realpath($directory) === $current_module;
      }
    }
  }
}
?>
<?php if (!empty($modules)) { ?>
  <nav style="background-color: #f2f2f2; border-bottom: 1px solid #666;" aria-label="Modules">
    <div class="container py-2 d-flex flex-wrap align-items-center gap-3">
      <?php foreach ($modules as $name => $active) { ?>
        <?php if ($active) { ?>
          <strong aria-current="page"><?php echo htmlspecialchars(ucfirst($name), ENT_QUOTES, 'UTF-8'); ?></strong>
        <?php } else { ?>
          <a href="/<?php echo rawurlencode($name); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(ucfirst($name), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php } ?>
      <?php } ?>
    </div>
  </nav>
<?php } ?>
