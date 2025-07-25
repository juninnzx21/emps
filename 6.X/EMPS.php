<?php

require_once "traits/routing.php";
require_once "traits/utils.php";
require_once "traits/properties.php";
require_once "traits/menus.php";
require_once "traits/files.php";
require_once "traits/actions.php";

/**
 * The version-independent base class for EMPS
 *
 * This class contains common functions that are not supposed to differ from one version of EMPS to another.
 * Such functions do not depend on the database engine used.
 *
 */
class EMPS_Common
{
    use EMPS_Common_Routing;
    use EMPS_Common_Utils;
    use EMPS_Common_Properties;
    use EMPS_Common_Menus;
    use EMPS_Common_Files;
    use EMPS_Common_Actions;

    /**
     * @var $p The Properties object
     */
    public $p;

    /**
     * @var $auth The Authentication object
     */
    public $auth;

    public $start_time = 0;

    public $log_file_path = EMPS_SCRIPT_PATH."/local/log.txt";
    public $log_enabled = false;

    public $page_properties = [];

    /**
     * @var $settings_cache Cache array to store website settings
     */
    public $settings_cache = false;

    /**
     * @var $content_cache Cache array to store content pages
     */
    public $content_cache = [];

    /**
     * @var $require_cache Cache array to store the resolved paths of files looked up with page_file_name()
     */
    public $require_cache = [];

    public $no_smarty = false;

    public $enum = [];

    public $spath = [];

    public $menus = [];
    public $mlv = [];

    public $page_var = 'start';
    public $page_clink = '';
    public $no_autopage = false;

    public $website_ctx, $default_ctx;

    public $current_website;

    public $enums_loaded;

    public $fast = false;

    public $last_modified = 0;

    public $cli_mode = false;

    public $tl_array = [];

    public $json_options = 0;

    public $prand_seed = 17131;

    public function __construct()
    {
        $this->lang = $GLOBALS['emps_lang'];
        $this->lang_map = $GLOBALS['emps_lang_map'];
        $this->emps_vars = explode(",", EMPS_VARS);
    }

    public function __destruct()
    {
        ob_end_flush();
    }

    /**
     * Early initialization procedure
     *
     * Overloaded by EMPS version classes
     */
    public function early_init()
    {
    }

    /**
     * Main initialization procedure
     *
     * This will detect the current website, parse the URL for variables, initialize Smarty plugins.
     */
    public function initialize()
    {
        $this->early_init();
        $this->select_website();

        if (!$this->cli_mode) {
            $this->parse_path();
            $this->import_vars();
            $this->savevars();

            if (isset($GET['plain']) && $_GET['plain']) {
                $this->page_property('plain', true);
            }
        }

        $plugins = $this->common_module('smarty.plugins.php');

        if (file_exists($plugins)) {
            require_once $plugins;
        }
    }

    /**
     * Called after parsing the URL
     *
     * This function, among other things, starts the PHP session.
     */

    public function post_parse()
    {
        global $pp;

        $fn = $this->common_module('config/postparse.php');
        if ($fn) {
            require_once $fn;
        }

        // this website's default content-type is utf-8 HTML
        $this->text_headers();

        $skip = false;

        if (defined("EMPS_NO_SESSION")){
            // these pages should not set the session cookie, they don't need it
            $x = explode(',', EMPS_NO_SESSION);
            foreach($x as $v){
                if ($v == $pp) {
                    $skip = true;
                }
            }
        }

        if (!$skip) {
            $skip = $this->should_prevent_session();
        }
        if (!$skip) {
            if (!$this->is_localhost_request() || ($GLOBALS['emps_localhost_mode'] ?? false)) {
                session_start();
                if ($_SESSION['lsu'] < (EMPS_SESSION_COOKIE_LIFETIME / 30)) {
                    $_sess_name = session_name();
                    $_sess_id = session_id();
                    setcookie($_sess_name, $_sess_id, time() + EMPS_SESSION_COOKIE_LIFETIME, "/");
                    $_SESSION['lsu'] = time();
                }
                if (count($_GET) > 0) {
                    foreach ($_GET as $n => $v) {
                        $n = strtolower($n);
                        if (substr($n, 0, 4) == "utm_") {
                            $_SESSION['utm'][$n] = $v;
                        }
                    }
                    $this->copy_values($_SESSION['utm'], $_GET,
                        "gclid,gclsrc,dclid,fbclid,yclid,ymclid,zanpid");
                }
            }
        }
    }

    /**
     * Post-init handler
     *
     * Called after the initialization of the EMPS object.
     */
    public function post_init()
    {
        if(isset($_SERVER["CONTENT_TYPE"]) && strstr($_SERVER["CONTENT_TYPE"], "application/json") !== false){
            $raw = file_get_contents("php://input");
            $request = json_decode($raw, true);
            if (is_array($request)) {
                $_REQUEST = array_merge($_REQUEST, $request);
                $_POST = array_merge($_POST, $request);
            }
        }
        if (!$this->no_smarty) {
            $this->prepare_menus();
        }
    }

    /**
     * Pre-init handler
     *
     * Called before the initialization of the EMPS object.
     */
    public function pre_init()
    {
        if(isset($_SERVER["CONTENT_TYPE"]) && strstr($_SERVER["CONTENT_TYPE"], "application/json") !== false){
            $raw = file_get_contents("php://input");
            $request = json_decode($raw, true);
            if (is_array($request)) {
                $_REQUEST = array_merge($_REQUEST, $request);
                $_POST = array_merge($_POST, $request);
            }
        }
    }

    /**
     * Pre-controller handler
     *
     * Called immediately before a module controller PHP script is called.
     */
    public function pre_controller()
    {
        global $pp, $smarty;
        if (!$this->fast) {
            $x = explode('-', $pp);
            if ($x[0] == "admin" || $x[0] == "manage") {
                $this->page_property("adminpage", 1);
            }

            $smarty->assign("enum", $this->enum);
        }
    }

    /**
     * Pre-display handler
     *
     * Called immediately before a module view Smarty template is displayed.
     */
    public function pre_display()
    {
        global $smarty;

        header("Referrer-Policy: unsafe-url");

        if (!($this->page_properties['title'] ?? false)) {
            $this->page_properties['title'] = "";
            foreach ($this->spath as $v) {
                if ($this->page_properties['title'] != "") {
                    $this->page_properties['title'] = strip_tags($v['dname']) . " - " . $this->page_properties['title'];
                } else {
                    $this->page_properties['title'] = strip_tags($v['dname']);
                }
            }
        }

        $this->page_property("year", date("Y", time()));

        $smarty->assign("enum", $this->enum);

        $fn = $this->common_module('config/predisplay.php');
        if ($fn) {
            require_once $fn;
        }

        $smarty->assign("spath", $this->spath);

        $smarty->assign('page', $this->page_properties);
        $smarty->assign('lang', $this->lang);

        $html_lang = $this->lang;

        if ($html_lang == 'nn') {
            $html_lang = 'ru';
        }
        $smarty->assign("html_lang", $html_lang);

        $smarty->assign("df_format", EMPS_DT_FORMAT);

        $smarty->assign("current_host", $_SERVER['HTTP_HOST']);
        $smarty->assign("current_uri", $_SERVER['REQUEST_URI']);

    }

    /**
     * Check if a virtual page exists in the database
     *
     * @param $uri string The full relative URI of the page sought
     */
    public function page_exists($uri)
    {
        $ra = $this->get_db_content_item($uri);
        if ($ra) return $ra;
        return false;
    }

    public function page_exists_external($uri)
    {
        return $this->page_exists($uri);
    }

    /**
     * Set the maximum execution time of the script to unlimited / 12 hours.
     */
    public function no_time_limit(){
        ini_set("max_execution_time",60*60*12);
        set_time_limit(0);
        ignore_user_abort(true);
    }

    public function conditional_content_length($resp, $size) {
        $range = $_SERVER['HTTP_RANGE'];
        $x = explode("=", $range);
        if ($x[0] == "bytes") {
            $xx = explode("-", $x[1]);
            $start = intval($xx[0]);
            if ($start == 0) {
                $resp->setHeader("Content-Length", $size);
            }
        }
    }

    public function cached_response($seconds) {
        header("Last-Modified: ", time());
        header("Expires: ", date("r", time() + $seconds));
        header("Cache-Control: max-age=" . $seconds);
    }


}
