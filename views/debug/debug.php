<?php
/*
  Plugin Name: Debug
  Description: Debug your blog, wordpress site and multisite with <a href="http://www.soninow.com/" target="_blank">Soninow</a>. Debug is a development/production tool, that's help you to remove bugs from your wordpress website.
  Version: 1.12
  Author: SoniNow Team
  Author URI: http://www.soninow.com
  License: GPL2+
 */
if (!defined('ABSPATH')) {
     exit();
}
define('DEBUG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('DEBUG_PLUGIN_VERSION', '1.12');

// function library file
require_once DEBUG_PLUGIN_DIR.'functions/function.php';
require_once DEBUG_PLUGIN_DIR.'functions/plugin.php';
