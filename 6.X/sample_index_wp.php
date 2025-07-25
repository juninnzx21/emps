<?php

/**
 * THIS IS A SAMPLE index.php FILE FOR A WORDPRESS+EMPS WEBSITE
 *
 * Rename the original WordPress index.php to wp-index.php
 *
 * Rename this file to index.php and put to the website's document root
 */

$emps_start_time = microtime(true);

// Just a suggestion. Could be turned off on a production server.
error_reporting(E_ERROR);
if ($_GET['debug']) {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    // Debug mode. Could be turned off in a production environment.
    ini_set('display_errors', 1);
}

require_once "local/local.php";                        // local settings for configuration

$emps_not_found = false;
$emps_wordpress_mode = true;

require_once "EMPS6/".EMPS_VERSION."/emps_wp_bootstrap.php";            // The main logic of the index.php file

if ($emps_not_found) {
    require_once "wp-index.php";
}