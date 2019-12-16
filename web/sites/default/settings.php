<?php

/**
 * @file settings.php
 * Include settings files for running Drupal in various GovCMS environments.
 *
 * @see https://govcms.gov.au/wiki-drupal-settings
 */

// Set by Drupal core.
/* @var $app_root string */

// Path to standardized platform-wide settings includes.
/* @var $govcms_settings string */

$govcms_settings = getenv('GOVCMS_DRUPAL_SETTINGS') ?: $app_root . '/../vendor/govcms/scaffold-tooling/drupal/settings';
$govcms_is_prod = !getenv('DEV_MODE') && (getenv('LAGOON_ENVIRONMENT_TYPE') && getenv('LAGOON_ENVIRONMENT_TYPE') == 'production');

// All environment defaults.
include $govcms_settings . '/all.settings.php';

// Only Lagoon containers (including local).
if (getenv('LAGOON')) {
  include $govcms_settings . '/lagoon.settings.php';
}

if ($govcms_is_prod) {
  // Only production.
  include $govcms_settings . '/production.settings.php';
}
else {
  // Only non-production.
  include $govcms_settings . '/development.settings.php';
}

// Only this project.
if (file_exists($app_root . '/sites/default/project.settings.php')) {
  include $app_root . '/sites/default/project.settings.php';
}

// Final overrides (not committed in Git).
if (file_exists($app_root . '/sites/default/settings.local.php')) {
  include $app_root . '/sites/default/settings.local.php';
}
