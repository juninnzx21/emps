<?php

trait EMPS_Common_Routing
{
    public $URI = '';
    public $PURI = '';
    public $PLURI = '';
    public $lang = 'nn';
    public $lang_map = [];
    public $virtual_path = 0;
    public $VA = [];
    public $emps_vars = [];

    /**
     * Check if the current module URL should be regarded as 'fast'
     *
     * 'fast' modules are not using authentication and some other modules to boost performance. The list of module names
     * is defined in the EMPS_FAST constant.
     */
    public function check_fast()
    {
        if(!defined("EMPS_FAST")){
            return;
        }
        $x = explode("/", $_SERVER["REQUEST_URI"]);
        $pp = $x[1];

        $x = explode(',', EMPS_FAST);
        $skip = false;
        foreach($x as $v){
            if ($v == $pp) {
                $skip = true;
            }
        }
        if ($skip) {
            $this->fast = true;
        }
    }

    /**
     * Replace the value of a stored URL variable
     *
     * The next loadvars() will set the variable to this new stored value.
     */
    public function changevar($n, $v)
    {
        $this->VA[$n] = $v;
        $GLOBALS[$n] = $v;
    }

    /**
     * Reset stored URL variables
     *
     * Clears the global variables whose names are defined in the EMPS_VARS constant. Omits the $lang variable.
     */
    public function clearvars()
    {

        foreach ($this->emps_vars as $value) {
            if ($value == 'lang') {
                continue;
            }
            $GLOBALS[$value] = "";
        }
    }

    /**
     * Load stored URL variables
     *
     * Loads the values of the variables whose names are defined in the EMPS_VARS constant from the $this->VA array property to their respective
     * global variables.
     */
    public function loadvars()
    {
        foreach ($this->emps_vars as $value) {
            $GLOBALS[$value] = $this->VA[$value];
        }
    }

    /**
     * Save URL variables to storage
     *
     * Puts the values of URL variables whose names are defined in the EMPS_VARS constant into the $this->VA array property.
     */
    public function savevars()
    {
        foreach ($this->emps_vars as $value) {
            if (isset($GLOBALS[$value])) {
                $this->VA[$value] = $GLOBALS[$value];
            }
        }
    }

    /**
     * Redirect handler for parse_path()
     *
     * Called from within parse_path() to check if the current URL has to be redirected (e.g. /admin-shadows/)
     */
    public function handle_redirect($uri)
    {
    }

    /**
     * Main URL parser
     *
     * Parses the current URL to determine if it should be routed to a module or a virtual page.
     */
    private function parse_path()
    {

        $uri = $_SERVER["REQUEST_URI"];

        $this->handle_redirect($uri);

        if (function_exists("emps_uri_filter")) {
            $uri = emps_uri_filter($uri);
        }

        $s = explode("?", $uri, 2);

        $uri = $s[0];
        $uri = str_replace(EMPS_SCRIPT_URL_FOLDER, '', $uri);    // remove initial path from the URI

        $this->PLURI = $uri;
        $this->menu_URI = $uri;

        if (substr($uri, 0, 1) == '/') {
            $uri = substr($uri, 1);
        }
        $ouri = $uri;
        $this->PURI = $ouri;

        $this->savevars();
        $uri = $this->PURI;
        if (substr($uri, strlen($uri) - 1, 1) == '/') {
            $uri = substr($uri, 0, strlen($uri) - 1);
        }

        $this->URI = $uri;

        $sp = $this->get_setting("startpage");

        if (!$this->URI) {
            if (!$_SERVER['QUERY_STRING']) {
                $this->URI = $sp;
            }
            $GLOBALS['pp'] = $sp;
            $this->page_property('front', 1);
        }
        if ($vp = $this->page_exists_external($this->PLURI)) {
            // virtual object (CMS database item)
            $this->virtual_path = $vp;
        } elseif ($vp = $this->page_exists($this->PLURI . '/')) {
            http_response_code(301);
            header("Location: " . $this->PLURI . '/');
            exit;
        } else {
            // parse parts of the $ouri as variables from the $RVLIST, make them global
            $xx = explode(",", EMPS_URL_VARS);
            $x = explode("/", $ouri);
            foreach ($x as $n => $v) {
                if ($v == "") {
                    continue;
                }
                if ($v != '-') {
                    $GLOBALS[$xx[$n]] = urldecode($v);
                }
            }
        }

        $this->post_parse();
    }

    /**
     * Import URL variables from $GET/$POST
     *
     * Checks if any of the variables whose names are defined in EMPS_VARS exist in $GET or $POST arrays and loads them
     * to the appropriate global variables, if found. Effectively this is a filtered track-vars.
     */
    private function import_vars()
    {
        foreach ($this->emps_vars as $v) {
            if (!isset($GLOBALS[$v])) {
                $GLOBALS[$v] = '';
            }
            if (isset($_GET[$v])) {
                $GLOBALS[$v] = $_GET[$v];
            }
            if (isset($_POST[$v])) {
                $GLOBALS[$v] = $_POST[$v];
            }
        }
    }

    public function getvar($varname) {
        return $GLOBALS[$varname];
    }

    public function not_found()
    {
        global $smarty;
        http_response_code(404);
        $smarty->assign("main_body", "db:page/notfound");
        $this->pre_display();
        $this->page_property("plain", $this->get_setting("plain_404"));
        $smarty->assign('page', $this->page_properties);
        $smarty->display("db:main");
    }

    public function database_down()
    {
        global $smarty;
        http_response_code(500);
        $this->pre_display();
        $smarty->display("db:page/databasedown");
    }

    public function deny_access($reason)
    {
        global $smarty;

        $this->retry_for_session();
        $smarty->assign($reason, 1);
    }

    public function retry_for_session()
    {
        if ($this->should_prevent_session()) {
            $retry = intval($_GET['retry']);
            if ($retry < 3) {
                $retry++;
                $this->redirect_page("./?retry=" . $retry);
                exit();
            }
        }
    }

    public function clink($a)
    {
        // Make up a link with the current variables plus another query part component (e.g. "x=1")
        $l = $this->elink();
        if ($a) {
            if (strstr($l, "?")) {
                $l .= "&" . $a;
            } else {
                $l .= "?" . $a;
            }
        }
        return $l;
    }

    public function elink()
    {
        // Make up an internal link with the variables

        $t = "./";

        if (!($this->no_url_vars ?? false)) {
            $x = explode(",", EMPS_URL_VARS);
            $rlist = [];
            foreach ($x as $v) {
                $rlist[$v] = $GLOBALS[$v];
            }

            $t = "";
            $tc = "";

            foreach ($x as $v) {
                $v = $this->xrawurlencode($GLOBALS[$v]);
                if (!$v) {
                    $tc .= "/-";
                } else {
                    $t .= $tc;
                    $t .= "/$v";
                    $tc = "";
                }
            }
            $t .= "/";
        }

        $vars = EMPS_VARS;
        if ($this->custom_vars ?? false) {
            $vars = $this->custom_vars;
        }
        $s = false;
        $xx = explode(",", $vars);
        foreach ($xx as $value) {
            if ($GLOBALS[$value] == "") {
                continue;
            }
            if ($rlist[$value] != "") {
                continue;
            }
            if ($s) {
                $t .= "&";
            } else {
                $t .= "?";
            }
            $s = true;
            $t .= $value . "=" . rawurlencode(strval($GLOBALS[$value]));
        }
        return $t;
    }

    public function slink($value, $var) {
        $GLOBALS[$var] = $value;
        return $this->elink();
    }

    public function redirect_page($page)
    {
        header("Location: " . $page);
    }

    public function redirect_elink()
    {
        $this->redirect_page($this->elink());
    }

    public function ensure_protocol($protocol)
    {
        $addr = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if ($protocol == 'https') {
            if ($_SERVER['HTTPS'] != 'on') {
                http_response_code(301);
                header("Location: https://" . $addr);
                exit;
            }
        } elseif ($protocol == 'http') {
            if ($_SERVER['HTTPS'] == 'on') {
                http_response_code(301);
                header("Location: http://" . $addr);
                exit;
            }
        }
    }

    public function is_https()
    {
        if ($_SERVER['HTTPS'] == 'on') {
            return true;
        }
        return false;
    }

    public function should_prevent_session()
    {
        global $emps_bots, $emps_just_set_cookie;

        if ($this->is_localhost_request()) {
            return false;
        }

        if (!$_SERVER['HTTP_USER_AGENT']) {
            return true;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'];
        foreach ($emps_bots as $bot) {
            if (strpos($ua, $bot) != false) {
                return true;
            }
        }
        if ($ua == "-") {
            return true;
        }
        if (strpos($ua, "curl/") != false) {
            return true;
        }
        if (strpos($ua, "python") != false) {
            return true;
        }

        if (!$emps_just_set_cookie) {
            if (!isset($_COOKIE['EMPS'])) {
                return true;
            }
        } else {
            if (count($_GET) > 0) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function normalize_url()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $x = explode("?", $uri, 2);
        $uri = $x[0];
        $elink = $this->elink();
        if ($uri != $elink) {
            $this->redirect_elink();
            exit;
        }
    }

    public function is_localhost_request()
    {
        if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
            return true;
        }
        return false;
    }

    public function json_response($response)
    {
        global $emps;

        $emps->no_smarty = true;
        header("Content-Type: application/json; charset=utf-8");

        echo json_encode($response, $this->json_options);
    }

    public function json_error($message, $id = "") {
        $response = [];
        $response['code'] = "Error";
        $response['message'] = $message;
        if ($id != "") {
            $response['id'] = $id;
        }
        $this->json_response($response);
    }

    public function json_ok($data = []) {
        $response = [];
        $response['code'] = "OK";
        $response = array_merge($response, $data);
        $this->json_response($response);
    }

    public function plaintext_response()
    {
        global $emps;

        $emps->no_smarty = true;
        header("Content-Type: text/plain; charset=utf-8");
    }

    public function referer_vars()
    {
        $referer = $_SERVER['HTTP_REFERER'];

        $x = explode(EMPS_SCRIPT_WEB, $referer);
        if ($x[0] == "" && isset($x[1])) {
            $xx = explode(",", EMPS_URL_VARS);
            $uri = mb_substr($x[1], 1);
            $x = explode("/", $uri);
            foreach ($x as $n => $v) {
                if ($v == "") {
                    continue;
                }
                if ($v != '-') {
                    $GLOBALS[$xx[$n]] = urldecode($v);
                }
            }
        }
    }


}