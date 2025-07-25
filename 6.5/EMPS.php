<?php
/**
 * EMPS MULTI-WEBSITE ENGINE
 *
 * Version 6.5 / SQL-based
 */

require_once EMPS_COMMON_PATH_PREFIX . "/EMPS.php";

/**
 * EMPS Class - Version 6.5 / SQL-based
 */
class EMPS extends EMPS_Common
{
    public $db;
    public $cas;

    public $settings_cache = [], $settings_cache_common = [], $content_cache = [], $new_settings = [];

    public $period_size = 60 * 60 * 24 * 7;

    public function __destruct()
    {
        unset($this->db);
        unset($this->p);
        ob_end_flush();
    }

    public function early_init()
    {
        $this->db = new EMPS_DB();
        $this->p = new EMPS_Properties();
        if (!$this->fast) {
            $this->auth = new EMPS_Auth();
            $this->blocks = new EMPS_Blocks();
        }

        $this->p->db = $this->db;
        $this->db->query("SET SESSION sql_mode=''");
    }

    public function section_menu_ex($code, $parent, $default_parent)
    {
        // Load the menu "grp=$code" and return it as a nested array (if subenus are present)
        $menu = array();

        $use_context = $this->website_ctx;

        $query = 'select * from ' . TP . "e_menu where parent=$parent and context_id=" . $use_context . " and grp='$code' order by ord asc";
        $r = $this->db->query($query);

        $mlst = array();
        while ($ra = $this->db->fetch_named($r)) {
            $mlst[] = $ra;
        }

        if ($parent == 0 || $default_parent) {
            $use_parent = $parent;
            if ($default_parent) {
                $use_parent = $default_parent;
            }
            $q = 'select * from ' . TP . "e_menu where parent=$use_parent and context_id=" . $this->default_ctx . " and grp='$code' order by ord asc";

            $r = $this->db->query($q);
            $dlst = array();
            while ($ra = $this->db->fetch_named($r)) {
                $ra['default_id'] = $ra['id'];
                $dlst[] = $ra;
            }
            $ndlst = array();
            foreach($dlst as $v) {
                $add = true;
                foreach($mlst as $nn => $vv){
                    if ($vv['uri'] == $v['uri'] && $vv['grp'] == $v['grp']) {
                        $mlst[$nn]['default_id'] = $v['id'];
                        $add = false;
                    }
                }
                if ($add) {
                    $ndlst[] = $v;
                }
            }
            if ($ndlst) {
                foreach($ndlst as $vv){
                    $mlst[] = $vv;
                }

                uasort($mlst, array($this, 'sort_menu'));
            }
        }
        foreach($mlst as $ra) {
            if(!$ra['enabled']){
                continue;
            }
            $md = $this->get_menu_data($ra);

            $ra['link'] = $ra['uri'];

            $ra['splink'] = @$md['splink'];
            if (@!$ra['splink']) {
                $ra['splink'] = $ra['link'];
            }

            if (@!$md['name']) {
                $use_name = $ra['uri'];
            } else {
                if (@$md['name$' . $this->lang]) {
                    $use_name = $md['name$' . $this->lang];
                } else {
                    $use_name = $md['name'];
                }
            }

            $ra['dname'] = $use_name;

            if (@$md['width']) {
                $ra['width'] = $md['width'];
            }

            if (@!$md['regex']) {
                if ($ra['uri'] == $this->menu_URI) {
                    $ra['sel'] = 1;
                } else {
                    if ($ra['uri']) {
                        $x = explode($ra['uri'], $this->menu_URI);
                        if ($x[0] == '' && $x[1] != '') {
                            $ra['sel'] = 1;
                        }
                    }
                }
            }

            if (@$md['regex']) {
                if (preg_match('/' . $md['regex'] . '/', $this->menu_URI)) {
                    $ra['sel'] = 1;
                }
            }
            if (@$ra['link'] == $this->menu_URI) {
                $ra['exact_sel'] = true;
            }

            if (@$md['grant']) {
                if (!$this->auth->credentials($md['grant'])) continue;
            }

            if (@$md['hide']) {
                if ($this->auth->credentials($md['hide'])) continue;
            }

            if (@$md['nouser']) {
                if ($this->auth->USER_ID) continue;
            }

            $smenu = $this->section_menu_ex($code, $ra['id'] ?? 0, $ra['default_id'] ?? 0);

            $ra['sub'] = $smenu;
            $ra['md'] = $md;
            $menu[] = $ra;
        }
        return $menu;

    }

    public function save_setting($code, $value)
    {
        $x = explode(':', $code);
        $name = $x[0];
        if (!isset($x[1])) {
            $code = $name . ":t";
        }
        $a = array($name => $value);
        $this->p->save_properties($a, $this->website_ctx, $code);
        $this->new_settings[$name] = $value;
    }

    public function save_setting_common($code, $value)
    {
        $x = explode(':', $code);
        $name = $x[0];
        if (!isset($x[1])) {
            $code = $name . ":t";
        }
        $a = array($name => $value);
        $this->p->save_properties($a, $this->default_ctx, $code);
    }

    public function get_setting($code)
    {
        // Get a fine-tuning setting
        if (count($this->settings_cache) == 0) {
            $this->p->no_full = false;
            $default_settings = $this->p->read_properties(array(), $this->default_ctx);
            if (!$default_settings) {
                $default_settings = array();
            }
            $website_settings = $this->p->read_properties(array(), $this->website_ctx);
            if (!$website_settings) {
                $website_settings = array();
            }
            if (!$default_settings['_full']) {
                $default_settings['_full'] = array();
            }
            if (!$website_settings['_full']) {
                $website_settings['_full'] = array();
            }
            $website_settings['_full'] = array_merge($default_settings['_full'], $website_settings['_full']);
            $this->settings_cache = array_merge($default_settings, $website_settings);
//			dump($this->settings_cache);
        }

        if (isset($this->new_settings[$code])) {
            return $this->new_settings[$code];
        }

        if(isset($this->settings_cache[$code])){
            if(intval($this->settings_cache['_full'][$code]['id']) > 0){
                return $this->settings_cache[$code];
            }
        }
        return false;
    }

    public function get_setting_plain($code) {
        // Get a fine-tuning setting
        $this->p->no_full = false;

        $value = $this->p->read_property($code, $this->website_ctx);
        if ($value === null) {
            $value = $this->p->read_property($code, $this->default_ctx);
        }

        $rv = $this->new_settings[$code];
        if (isset($rv)) {
            return $rv;
        }
        return $value;
    }

    public function get_setting_common($code)
    {
        // Get a fine-tuning setting
        if (count($this->settings_cache_common) == 0) {
            $this->p->no_full = false;
            $website_settings = $this->p->read_properties(array(), $this->default_ctx);
            if (!$website_settings) {
                $website_settings = array();
            }
            if (!$website_settings['_full']) {
                $website_settings['_full'] = array();
            }
            $this->settings_cache_common = $website_settings;
//			dump($this->settings_cache);
        }
        $rv = $this->settings_cache_common[$code];
        if(isset($rv)){
            if(intval($this->settings_cache_common['_full'][$code]['id']) > 0){
                return $rv;
            }
        }
        return false;
    }

    public function website_by_host($hostname)
    {
        $website = $this->db->get_row("e_websites", "hostname = '" . $this->db->sql_escape($hostname) . "'");
        if (!$website) {
            $website = $this->db->get_row("e_websites", "'" . $this->db->sql_escape($hostname) . "' regexp hostname_filter");
        }
        if ($website) {
//			dump($website);
            $this->current_website = $website;
            if ($website['lang']) {
                $this->lang = $website['lang'];
            }
            return $website['id'];
        }
        return 0;
    }

    public function select_website()
    {
        // URL parser to decide which website is active
        $hostname = $_SERVER['SERVER_NAME'];
        $this->default_ctx = $this->p->get_context(1, 1, 0);
        $website_id = $this->website_by_host($hostname);
        $this->website_id = 0;
        if ($website_id) {
            $this->website_id = $website_id;
            if ($this->current_website['status'] == 100) {
                $this->website_ctx = $this->default_ctx;
            } else {
                $this->website_ctx = $this->p->get_context(DT_WEBSITE, 1, $website_id);
            }
        } else {
            $this->website_ctx = $this->default_ctx;
        }
//		echo "ctx: ".$this->website_ctx;
    }

    public function base_url_by_ctx($website_ctx)
    {
        $ctx = $this->db->get_row("e_contexts", "id = " . $website_ctx);
        if ($ctx) {
            if ($ctx['ref_type'] == DT_WEBSITE) {
                $website = $this->db->get_row("e_websites", "id=" . $ctx['ref_id']);
                if ($website) {
                    return "http://" . $website['hostname'];
                }
            }
        }
        return EMPS_SCRIPT_WEB;
    }

    public function display_log()
    {
        global $smarty;
        $smarty->assign("ShowTiming", EMPS_SHOW_TIMING);
        $smarty->assign("ShowErrors", EMPS_SHOW_SQL_ERRORS);
        $end_time = emps_microtime_float(microtime(true));

        $span = $end_time - $this->start_time;

        $smarty->assign("timespan", sprintf("%02d", $span * 1000));
        $smarty->assign("errors", $this->db->sql_errors);
        if ($_GET['sql_profile'] ?? false) {
            $smarty->assign("SqlProfile", 1);
            $smarty->assign("timing", $this->db->sql_timing);
        }

        return $smarty->fetch("db:page/foottimer");
    }

    function get_full_id($id, $table, $pf, $vf)
    {
        global $emps;
        $row = $emps->db->get_row($table, "id = {$id}");
        if (!$row) {
            return "";
        }

        if ($row[$pf]) {
            $full_id = $this->get_full_id($row[$pf], $table, $pf, $vf);
        } else {
            $full_id = "";
        }

        $value = "";
        $vle = $row[$vf];
        $id = -$vle + 0;
        for ($i = 0; $i < 4; $i++) {
            $cur = ($id >> ((3 - $i) * 8)) & 255;
            $value .= chr($cur);
        }
        return $full_id . $value;
    }

    public function load_website($id) {
        $row = $this->db->get_row("e_websites", "id = {$id}");
        if ($row) {
            return $row;
        }
        return false;
    }

    public function not_default_website()
    {
        global $smarty;
        if ($this->current_website['status'] == 100) {
            if ($this->website_ctx == $this->default_ctx) {
                $this->deny_access('WebsiteNeeded');

                $r = $this->db->query("select * from " . TP . "e_websites where status = 50 and pub = 10 and parent = " . $this->current_website['id'] . " order by hostname asc");
                $lst = array();
                while ($ra = $this->db->fetch_named($r)) {
                    $lst[] = $ra;
                }

                $smarty->assign("wlst", $lst);
                $smarty->assign("current_url", $_SERVER['REQUEST_URI']);

                return false;
            }
        }
        return true;
    }

    public function handle_redirect($uri)
    {
        $ouri = $this->db->sql_escape(urldecode($uri));
        $row = $this->db->get_row("e_redirect", "'$ouri' regexp olduri");
        if ($row) {
            // redirect if there is an entry in the e_redirect table
            http_response_code(301);
            $this->redirect_page($row['newuri']);
            exit;
        }
    }

    public function get_db_content_item($uri)
    {
        // Return the e_content item by URI, cache the response

        if (isset($this->content_cache[$uri])) {
            return $this->content_cache[$uri];
        }

        $euri = $this->db->sql_escape($uri);

        $q = "select * from " . TP . "e_content where uri = '{$euri}' and context_id = " . $this->website_ctx;
        $r = $this->db->query($q);
        $ra = $this->db->fetch_named($r);
        if (!$ra) {
            $q = "select * from " . TP . "e_content where uri = '{$euri}' and context_id = " . $this->default_ctx;
            $r = $this->db->query($q);
            $ra = $this->db->fetch_named($r);
        }
        $this->content_cache[$uri] = $ra;
        return $ra;
    }

    public function get_db_cache($code) {
        $result = $this->p->read_cache($this->website_ctx, $code);
        if ($result) {
            return $result['data'];
        }
        return "";
    }

    public function get_content_data($page)
    {
        // Read the properties of a content item (effectively page_properties)
        $context_id = $this->p->get_context(DT_CONTENT, 1, $page['id']);
        $ra = $this->p->read_properties(array(), $context_id);
        $ra['context_id'] = $context_id;
        $ra['page_context_id'] = $context_id;
        return $ra;
    }

    public function get_menu_data($item)
    {
        // Read the properties of a menu item
        $ra = $this->p->read_properties(array(), $this->p->get_context(DT_MENU, 1, $item['id']));
        return $ra;
    }

    public function get_setting_time($code)
    {
        // Get the timestamp of a fine-tuning setting
        $ra = $this->get_setting($code);
        if ($ra) {
//			echo "has setting $code: ".$this->settings_cache['_full'][$code]['dt'].", ";
            return $this->settings_cache['_full'][$code]['dt'] + 0;
        } else {
            return false;
        }
    }

    public function print_pages_found()
    {
        $found = $this->db->found_rows();
        return $this->print_pages($found);
    }

    public function redirect_elink()
    {
        if (count($this->db->sql_errors) > 0) {
//			dump($this->db->sql_errors);
            return false;
        }
        $this->redirect_page($this->elink());
    }

    public function is_empty_database()
    {
        $r = $this->db->query("show tables");
        $lst = array();
        while ($ra = $this->db->fetch_row($r)) {
            $lst[] = $ra;
        }
        if (count($lst) == 0) {
            return true;
        }
        return false;
    }

    public function shadow_properties_link($link)
    {
        $link = $this->db->sql_escape($link);

        $shadow = $this->db->get_row("e_shadows", "url='" . $link . "' and website_ctx = " . $this->website_ctx);
        if (!$shadow) {
            $shadow = $this->db->get_row("e_shadows", "url='" . $link . "' and website_ctx = " . $this->default_ctx);
            if (!$shadow) {
                return false;
            }
        }
        $context_id = $this->p->get_context(DT_SHADOW, 1, $shadow['id']);
        $props = $this->p->read_properties(array(), $context_id);
        $this->page_properties = array_merge($this->page_properties, $props);
    }

    public function shadow_properties($vars)
    {
        $link = $this->raw_elink($vars);

        return $this->shadow_properties_link($link);
    }

    public function ensure_browser($name)
    {
        if (isset($this->db)) {
            $row = $this->db->get_row("e_browsers", "name = '" . $this->db->sql_escape($name) . "'");
            if ($row) {
                return $row['id'];
            } else {
                $nr = [];
                $nr['name'] = $name;
                $this->db->sql_insert_row("e_browsers", ['SET' => $nr]);
                $id = $this->db->last_insert();
                return $id;
            }
        } else {
            return -1;
        }
    }

    /**
     * Add the current remote IP address to the black list (or update the timestamps if it already exists)
     *
     *
     */
    public function add_to_blacklist()
    {
        $term = 180 * 24 * 60 * 60;
        $ip = $_SERVER['REMOTE_ADDR'];
        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");

        $ur = array();
        $ur['edt'] = time() + $term;
        $ur['adt'] = time();

        if ($row) {
            $update = ['SET' => $ur];
            $this->db->sql_update_row("e_blacklist", $update, "id = " . $row['id']);
        } else {
            $ur['ip'] = $ip;
            $update = ['SET' => $ur];
            $this->db->sql_insert_row("e_blacklist", $update);
        }

        $this->service_blacklist();
    }

    /**
     * Add the current remote IP address to the black list (or update the timestamps if it already exists)
     *
     *
     */
    public function add_to_blacklist_term($term)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");

        $ur = array();
        $ur['edt'] = time() + $term;
        $ur['adt'] = time();

        if ($row) {
            $update = ['SET' => $ur];
            $this->db->sql_update_row("e_blacklist", $update, "id = " . $row['id']);
        } else {
            $ur['ip'] = $ip;
            $update = ['SET' => $ur];
            $this->db->sql_insert_row("e_blacklist", $update);
        }

        $this->service_blacklist();
    }

    /**
     * Check if the current remote IP address is blacklisted
     *
     *
     */
    public function is_blacklisted()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");
        if ($row) {
            return true;
        }

        return false;
    }

    /**
     * Delete expired items from the black list
     *
     *
     */
    public function service_blacklist()
    {
        $this->db->query("delete from " . TP . "e_blacklist where edt < " . time());
    }

    public function failed_antibot()
    {
        global $emps;

        $ip = $_SERVER['REMOTE_ADDR'];
        error_log("Failed antibot: " . $ip);

        $row = $this->db->get_row("e_watchlist", "ip = '" . $ip . "'");
        if ($row) {
            $cnt = $row['cnt'] + 1;

            $update = array();
            $update['SET'] = array('cnt' => $cnt);
            $emps->db->sql_update_row("e_watchlist", $update, "id = ".$row['id']);

        }else{
            $cnt = 1;
            $ur = array();
            $ur['ip'] = $ip;
            $ur['cnt'] = $cnt;
            $update = array();
            $update['SET'] = $ur;
            $emps->db->sql_insert_row("e_watchlist", $update);
        }

        if($cnt > 5){
            $this->add_to_blacklist_term(30 * 60);
        }
        if($cnt > 10){
            $this->add_to_blacklist_term(6 * 60 * 60);
        }
        if($cnt > 20){
            $this->add_to_blacklist_term(24 * 60 * 60);
        }
    }

    public function passed_antibot()
    {
        global $emps;
        $ip = $_SERVER['REMOTE_ADDR'];

        $row = $this->db->get_row("e_watchlist", "ip = '" . $ip . "'");
        if ($row) {
            $update = array();
            $update['SET'] = array('cnt' => 0);
            $emps->db->sql_update_row("e_watchlist", $update, "id = ".$row['id']);
        }
    }

    public function add_stat($metric, $value) {
        $period = floor(time() / ($this->period_size));

        $context_id = $this->website_ctx;

        $nr = [];
        $nr['code'] = $metric;
        $nr['context_id'] = $context_id;
        $nr['per'] = $period;
        $nr['dt'] = time();
        $nr['value'] = $value;
        $this->db->query("lock tables ".TP."e_counter");
        $row = $this->db->get_row("e_counter", "code = '{$metric}' and context_id = {$context_id} and per = {$period}");
        if ($row) {
            $SET['vle'] = $row['vle'] + $value;
            $this->db->sql_update_row("e_counter", ['SET' => $nr], "id = " . $row['id']);
        } else {
            $this->db->sql_insert_row("e_counter", ['SET' => $nr]);
        }
        $this->db->query("unlock tables");

    }

    public function page_exists_external($uri) {
        $rv = $this->page_exists($uri);
        if ($rv) {
            $data = $this->get_content_data($rv);
            if ($data['internal']) {
                return false;
            }
        }
        return $rv;
    }

    public function ensure_data_type($dt_name, $code) {
        if ($code == 0) {
            $dt_name_e = $this->db->sql_escape($dt_name);
            $r = $this->db->query("select max(value) from ".TP."e_data_types where name = '{$dt_name_e}'");
            $ra = $this->db->fetch_row($r);
            if (!$ra[0]) {
                $code = 100010;
            } else {
                $code = $ra[0] + 10;
            }
        }
        $code = intval($code);
        while (true) {
            $row = $this->db->get_row("e_data_types", "value = {$code}");
            if ($row) {
                $code += 10000;
            } else {
                break;
            }
        }

        $nr = [];
        $nr['name'] = $dt_name;
        $nr['value'] = $code;
        $this->db->sql_ensure_row("e_data_types", $nr);

        $this->load_dt_table();
    }

    public function load_dt_table() {
        $r = $this->db->query("select * from ".TP."e_data_types order by value asc");
        if (!$r) {
            $this->dt_table = -1;
            return;
        }
        $this->dt_table = [];
        while ($ra = $this->db->fetch_named($r)) {
            $this->dt_table[$ra['name']] = $ra['value'];
        }
    }

    public function dt($dt_name, $code = 0) {
        if (!isset($this->dt_table)) {
            $this->load_dt_table();
        }
        if ($this->dt_table == -1) {
            define($dt_name, $code);
            return $code;
        }
        if (!isset($this->dt_table[$dt_name])) {

            $acode = $this->ensure_data_type($dt_name, $code);
            define($dt_name, $acode);
            return $acode;
        }
        $acode = $this->dt_table[$dt_name];
        define($dt_name, $acode);
        return $acode;
    }

    public function php_session_id() {
        global $emps;

        $code = $_COOKIE['PHPSESSID'];
        $row = $emps->db->get_row("e_php_sessions", "sess_id = '{$code}'");
        if ($row) {
            return $row['id'];
        }
        return 0;
    }

}

