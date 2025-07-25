<?php
/**
 * Initialization Script
 *
 * This procedure is common for all EMPS versions.
 */

define('EMPS_COMMON_PATH_PREFIX', 'EMPS6/6.X');
// EMPS_PATH_PREFIX is set in the current version's emps_bootstrap.php file

date_default_timezone_set(EMPS_TZ);

if (isset($emps_force_hostname) && $emps_force_hostname) {
    if ($_SERVER['HTTP_HOST'] != EMPS_HOST_NAME) {
        http_response_code(301);
        header("Location: " . EMPS_SCRIPT_WEB . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/*
 * Composer Autoloader
 *
 * Since EMPS 6, there is no common 'vendor' folder for all EMPS websites on the server. Each website has to
 * install its own dependencies by combining the EMPS pre_composer.json file with the project's composer.json file
 *
 * To install all dependencies of the project (not only Composer, but also bower, npm) run:
 *
 * ./emps install
 *
 * in the project's folder
 */
if (!isset($emps_no_common_autoload) || !$emps_no_common_autoload) {
    require_once EMPS_COMMON_PATH_PREFIX."/../vendor/autoload.php";
}

$emps_include_path = ini_get('include_path');

$glue = PATH_SEPARATOR;

$emps_paths = array($emps_include_path, EMPS_SCRIPT_PATH);
$emps_extra_paths = explode(':', EMPS_INCLUDE_PATH); // that's why ":" here even on Windows
$emps_paths = array_merge($emps_paths, $emps_extra_paths);

$path = implode($glue, $emps_paths);
ini_set('include_path', $path);

// Send the file if the user wants a file
require_once EMPS_COMMON_PATH_PREFIX . "/emps_sendfile.php";
// No further execution of the main script will be needed if this script does the job

// A cookie test - this will let us know if the browser supports cookies
$emps_just_set_cookie = false;
if (!isset($_COOKIE['EMPS'])) {
    $emps_just_set_cookie = true;
    setcookie("EMPS", time(), time() + 60 * 60 * 24 * 30, '/');
}

// The main script
require_once EMPS_PATH_PREFIX . "/EMPS.php";                        // EMPS Class

$emps = new EMPS();
$emps->check_fast();

if (isset($emps_force_protocol) && $emps_force_protocol && !($_GET['nohttps'] ?? false)) {
    $emps->ensure_protocol($emps_force_protocol);
}

$fn = $emps->common_module('config/precore.php');
if ($fn) {
    require_once $fn;
}

// Core classes (some not included if $emps->fast is set)
require_once EMPS_PATH_PREFIX . "/core/core.php";

mb_internal_encoding('utf-8');
date_default_timezone_set(EMPS_TZ);

ini_set("session.cookie_lifetime", EMPS_SESSION_COOKIE_LIFETIME);
ini_set("session.cookie_path", "/");
ini_set("session.use_cookies", "1");
ini_set("session.use_only_cookies", "1");
ini_set("magic_quotes_runtime", "0");

$emps_bots = array(
    'YandexBot',
    'SputnikBot',
    'YandexMetrika',
    'Yahoo! Slurp',
    'bingbot',
    'StackRambler',
    'Googlebot',
);

$emps->pre_init();

$emps->initialize();    // initialization and automatic configuration

if (!$emps->db->operational) {
    // could not connect to a DB
    $emps->database_down();
    exit;
}

$emps->start_time = emps_microtime_float($emps_start_time);

ob_start();

$actions_file_name = $emps->common_module('config/project/actions.php');
if (file_exists($actions_file_name)) {
    require_once $actions_file_name;
}

if (!$emps->fast) {
    $emps->auth->handle_logon();

    $fn = $emps->page_file_name('_' . $pp . ',_postinit', 'controller');
    if (file_exists($fn)) {
        require_once $fn;
    }

    if (function_exists("emps_nosmarty_pp")) {
        if (emps_nosmarty_pp($pp)) {
            $emps->no_smarty = true;
        }
    }
    $emps->post_init();
}


$sua = $emps->get_setting("service_unavailable");
if ($sua == 'yes') {
    $go = true;
    if (
        substr($_SERVER['REQUEST_URI'], 0, 6) == "/admin" ||
        substr($_SERVER['REQUEST_URI'], 0, 4) == "/mjs"
    ) {
        $go = false;
        if ($emps->auth->USER_ID > 0) {
            if ($emps->auth->USER_ID != 1) {
                $go = true;
            }
        }
    }

    if ($go) {
        $page = $emps->get_setting("unavailable_page");
        if ($page) {
            $smarty->assign("show_page", $page);
        }
        http_response_code(503);
        header("Retry-After: 3600");
        $smarty->display("db:site/unavailable");
        exit;
    }
}

if ($emps->virtual_path && !$emps->fast) {
// if the item exists in the CMS database
    $data = $emps->get_content_data($emps->virtual_path);

    $emps->last_modified = $emps->virtual_path['dt'];
    $emps->handle_modified();

    $emps->page_property("canprint", 1);
    $emps->copy_properties($emps->virtual_path['uri']);
    $emps->pre_display();

    $out = ob_get_clean();
    $smarty->assign("ob_out", $out);
    $smarty->assign("virtual_path", $emps->virtual_path);

    if (!$data['html']) {
        $emps->not_found();
    } else {
        $smarty->assign("main_body", "page:" . $emps->virtual_path['uri']);
        $smarty->display("db:main");
    }
} else {
// if the item is a controller or a static page
    $fn = $emps->common_module('config/webinit.php');
    if ($fn) {
        require_once $fn;
    }

    $emps->pre_controller();

    $tn = $emps->page_file_name('_' . $pp, 'view');
    $fn = $emps->page_file_name('_' . $pp, 'controller');

    if (file_exists($fn)) {
        require_once $fn;
    } else {
        if (!file_exists($tn)) {
            $fn = $emps->common_module($pp . '.php');
            if ($fn) {
                $fn = $emps->resolve_include_path($fn);
                if ($fn !== false) {
                    require_once $fn;
                }
            }
        }
    }

    // HTML view
    if (!$emps->no_smarty) {
        $emps->pre_display();
        $out = ob_get_clean();
        $smarty->assign("ob_out", $out);

        if (file_exists($tn)) {
            $x = explode("-", $pp);
            if (in_array("comp", $x)) {
                // Prevent component HTML from being displayed
                $emps->not_found();
            } else {
                $smarty->assign("main_body", $tn);

                $smarty->display("db:main");
            }
        } else {
            $emps->not_found();
        }
    } else {

    }
}

exit;            // Invoke EMPS class destructor for clean shutdown
