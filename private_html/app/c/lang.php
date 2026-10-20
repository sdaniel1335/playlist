<?php
/** Select scalar translation constants using APP_LANG (PHP 5.3 compatible). */
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang_en.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang_hu.php';

function lang($key)
{
  $language = defined('APP_LANG') ? strtoupper(APP_LANG) : 'EN';
  $suffix = substr($key, 5);
  $localized = 'LANG_' . $language . '_' . $suffix;
  $fallback = 'LANG_EN_' . $suffix;
  return defined($localized) ? constant($localized)
    : (defined($fallback) ? constant($fallback) : $key);
}

function lang_javascript()
{
  $result = array();
  foreach (get_defined_constants() as $name => $value) {
    if (strpos($name, 'LANG_EN_') === 0 || strpos($name, 'LANG_HU_') === 0) {
      $key = 'LANG_' . substr($name, 8);
      $result[$key] = lang($key);
    }
  }
  return json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
