<?php

trait EMPS_Common_Utils
{
    /**
     * Unslash a string prepared by magic_quotes
     *
     * @param $a string
     *
     * @return string
     */
    public function unslash_prepare($a)
    {
        foreach ($a as $n => $v) {
            if (is_array($v)) {
                $a[$n] = $this->unslash_prepare($v);
            } else {
                $a[$n] = stripslashes($v);
            }
        }
        return $a;
    }

    /**
     * Set the HTML Content-Types
     *
     * This function will ensure that the Content-Type header is set to text/html.
     */
    public function text_headers()
    {
        header("Content-Type: text/html; charset=utf-8");
    }

    /**
     * Truncate a string
     *
     * If the $s string is longer that $t characters, it will be truncated at the nearest space character and ended with a ' ...'
     *
     * @param $s string The input string
     * @param $t int The maximum length
     *
     * @return string
     */
    public function cut_text($s, $t)
    {
        if (mb_strlen($s) <= $t) {
            return $s;
        }
        for ($i = $t; $i > 0; $i--) {
            $c = mb_substr($s, $i, 1);
            if ($c == ' ') {
                return mb_substr($s, 0, $i) . " ...";
            }
        }
        return "";
    }

    /**
     * Parse an enum descriptor string into an enum array
     *
     * @param $name Enum name (code)
     * @param $list Values list string (e.g. '10=Yes;20=No')
     */
    public function make_enum($name, $list)
    {
        $lst = [];
        $x = explode(";", $list);

        foreach ($x as $v) {
            $xx = explode("=", $v, 3);
            $e = [];
            $e['code'] = trim($xx[0]);
            if(strval(intval($e['code'])) == $e['code']){
                $e['code'] = intval($e['code']);
            }
            $e['value'] = $xx[1];
            if (isset($xx[2])) {
                $dx = explode(",", $xx[2]);
                foreach($dx as $vv){
                    if ($vv) {
                        $e[$vv] = 1;
                    }
                }
            }

            if (isset($e['str']) && $e['str']) {
                $e['code'] = strval($e['code']);
            }
            $lst[] = $e;
        }
        $this->enum[$name] = $lst;
    }

    public function pad_menu($template){
        global $smarty;
        $smarty->assign("lang", $this->lang);
        $json = $smarty->fetch($template);
        $menu = json_decode($json, true);
        return $menu;
    }

    public function add_to_menu(&$menu, $variable, $code, $name)
    {
        // Add a code/name pair to a $menu, the selection of a menu item is tracked by $variable
        $current_value = $GLOBALS[$variable];
        $e = [];
        $e['code'] = $code;
        $GLOBALS[$variable] = $code;
        $e['link'] = $this->elink();
        $this->loadvars();
        if ($current_value == $code) {
            $e['sel'] = 1;
        }
        $e['name'] = $name;
        $menu[] = $e;
    }

    public function prepare_pad_menu($pads, $variable)
    {
        $menu = [];
        if (!is_array($pads)) {
            return false;
        }

        foreach ($pads as $n => $v) {
            $this->add_to_menu($menu, $variable, $n, $v);
        }
        return $menu;
    }

    public function default_perpage($default) {
        $perpage = $default;
        if (isset($_SESSION['default_perpage'])) {
            $v = intval($_SESSION['default_perpage']);
            if ($v > 0) {
                $perpage = $v;
            }
        }
        return $perpage;
    }

    public function count_pages($total)
    {
        // New pagination function
        global $perpage;

        if ($total < $GLOBALS[$this->page_var] && !$this->no_autopage && $total > 0) {
            $GLOBALS[$this->page_var] = 0;
            $this->redirect_page($this->elink() . "?" . $_SERVER['QUERY_STRING']);
        }

        if (!$perpage) {
            $perpage = 10;
        }
        $a = [];

        if (is_numeric($GLOBALS[$this->page_var])) {
            if ($GLOBALS[$this->page_var] >= $total) {
                $GLOBALS[$this->page_var] = $total - $perpage;
                if ($GLOBALS[$this->page_var] < 0) {
                    $GLOBALS[$this->page_var] = 0;
                }
                $this->savevars();
            }
        }

        $cs = $GLOBALS[$this->page_var];
        $f = ceil($total / $perpage);

        $cf = $f;
        $scl = floor(intval($GLOBALS[$this->page_var]) / $perpage) - 3;
        if ($scl < 0) $scl = 0;

        if ($f > 7) $f = 7;

        if ($f + $scl > $cf) $scl = $cf - $f;

        if ($scl < 0) $scl = 0;

        $GLOBALS[$this->page_var] = 0;
        $a['first']['start'] = $GLOBALS[$this->page_var];
        $a['first']['page'] = 1;
        $a['first']['link'] = $this->clink($this->page_clink);
        $pl = [];

        $selitem = -1;

        for ($i = 0; $i < $f; $i++) {
            $GLOBALS[$this->page_var] = ($i + $scl) * $perpage;

            $pl[$i] = [];

            $sel = false;
            if ($GLOBALS[$this->page_var] == $cs) {
                $pl[$i]['sel'] = true;
                $sel = true;
                $selitem = $i;
            }

            $pl[$i]['start'] = $GLOBALS[$this->page_var];
            $pl[$i]['link'] = $this->clink($this->page_clink);
            $pl[$i]['page'] = ($i + $scl + 1);

            $GLOBALS[$this->page_var]++;

            $pl[$i]['fi'] = $GLOBALS[$this->page_var] + 0;

            $res = ($GLOBALS[$this->page_var] + $perpage - 1);

            if ($res > $total) $res = $total;

            $pl[$i]['li'] = $res + 0;
            $pl[$i]['count'] = $res - $GLOBALS[$this->page_var] + 1;

            if ($pl[$i]['sel'] ?? false) {
                $a['cur'] = $pl[$i];
            }

        }

        $GLOBALS[$this->page_var] = ($cf - 1) * $perpage;

        if (is_array($pl[$i - 1]) && $pl[$i - 1]['start'] == $GLOBALS[$this->page_var]) {
            $a['last'] = $pl[$i - 1];
        } else {
            $a['last'] = [];
            $a['last']['start'] = $GLOBALS[$this->page_var];
            $a['last']['link'] = $this->clink($this->page_clink);
            $a['last']['page'] = $cf;
        }

        $npl = [];
        for ($i = 0; $i < $f; $i++) {
            $npl[$i] = array_slice($pl[$i], 0);
            if ($i > 0) {
                $npl[$i]['prev'] = array_slice($pl[$i - 1], 0);
            } else {
                $npl[$i]['prev'] = $a['last'];
            }
            if ($i < $f - 1) {
                $npl[$i]['next'] = array_slice($pl[$i + 1], 0);
            } else {
                $npl[$i]['next'] = $a['first'];
            }
        }

        if ($selitem != -1) {
            $a['prev'] = $npl[$selitem]['prev'];
            $a['next'] = $npl[$selitem]['next'];
        }

        $GLOBALS[$this->page_var] = "all";
        $a['all'] = ['start' => 'all', 'link' => $this->clink($this->page_clink)];

        $GLOBALS[$this->page_var] = $cs;

        $a['pl'] = $npl;
        $a['count'] = count($npl);
        $a['total'] = $total;
        $a['perpage'] = $perpage;

        return $a;
    }

    public function xrawurlencode($vle)
    {
        // rawurlencode that doesn't encode hyphens
        $v = rawurlencode($vle);
        $v = str_replace("%2F", "-", $v);
        $v = str_replace("%2C", ",", $v);
        $v = str_replace("%3D", "=", $v);
        $v = str_replace("%3B", ";", $v);

        return $v;
    }

    public function print_pages($found)
    {
        global $smarty;

        $pages = $this->count_pages($found);
        $smarty->assign("pages", $pages);
        return $smarty->fetch("db:page/paginator");
    }

    public function form_time($dt)
    {
        return date("d.m.Y H:i", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function form_time_full($dt)
    {
        return date("d.m.Y H:i:s", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function get_log_time()
    {
        $mt = microtime();
        $x = explode(' ', $mt, 2);
        return date("d.m.Y H:i:s", $x[1] + EMPS_TZ_CORRECT * 60 * 60) . sprintf(':%d', $x[0] * 1000);
    }

    public function form_date($dt)
    {
        return date("d.m.Y", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function form_date_full($dt)
    {
        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября',
            'ноября', 'декабря'];

        $month = intval(date("m", $dt)) - 1;
        $month_name = $months[$month];
        return sprintf("%s %s %s", date("d", $dt), $month_name, date("Y", $dt));
    }

    public function form_date_us($dt)
    {
        return date("m/d/Y", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function parse_time($v)
    {
        $format = EMPS_DT_FORMAT;
        $x = explode(" ", $format);
        $date = $x[0];
        $split = ".";
        if (strstr($date, "/")) {
            $xx = explode("/", $date);
            $split = "/";
        } else {
            $xx = explode(".", $date);
        }
        $p = explode(" ", $v);
        $d = explode($split, $p[0]);
        foreach ($d as $n => $v) {
            if ($xx[$n] == "%d") {
                $day = intval($v);
            }
            if ($xx[$n] == "%m") {
                $mon = intval($v);
            }
            if ($xx[$n] == "%Y") {
                $year = intval($v);
            }
        }
        if (!$p[1]) {
            $p[1] = '12:00:00';
        }

        $t = explode(":", $p[1]);
        $hour = intval($t[0]);
        $min = intval($t[1]);
        $sec = intval($t[2]);
        try {
            $dt = mktime($hour, $min, $sec, $mon, $day, $year) - EMPS_TZ_CORRECT * 60 * 60;
        } catch (\Exception $e) {
            $dt = 0;
        }

        return $dt;
    }

    public function check_required($arr, $list)
    {
        // Check if $arr contains values named with comma-separated values in the $list. If an item from $list
        // does not exist in the $arr, it is added to the $err array so that Smarty could know which fields
        // are missing: style="field {{if $err.some_value}}error{{/if}}"
        $x = explode(",", $list);
        $err = [];
        foreach($x as $v){
            if (!$arr[$v]) {
                $err[] = $v;
            } else {
                if (is_array($arr[$v])) {
                    if (!$arr[$v][0] && count($arr[$v]) == 1) {
                        $err[] = $v;
                    }
                }
            }
        }
        return $err;
    }

    public function partial_array($arr, $list)
    {
        $x = explode(",", $list);
        $parr = [];
        foreach($x as $v){
            if ($arr[$v]) {
                $parr[$v] = $arr[$v];
            }
        }
        return $parr;
    }

    public function utf8_urldecode($str)
    {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($str));
        return html_entity_decode($str, null, 'UTF-8');;
    }

    public function enum_val($enum, $code)
    {
        $lst = $this->enum[$enum];
        foreach ($lst as $n => $v) {
            if ($v['code'] == $code) {
                return $v['value'];
            }
        }
        return false;
    }

    public function enumval($code, $enum){
        return $this->enum_val($enum, $code);
    }

    public function inflection($value)
    {
        $h = floor(($value % 100) / 10);
        $d = $value % 10;

        if ($d == 1) {
            if ($h == 1) {
                return 5;
            } else {
                return 1;
            }
        }
        if ($d >= 2 && $d <= 4) {
            if ($h == 1) {
                return 5;
            } else {
                return 2;
            }
        }

        return 5;
    }

    public function traceback(Exception $e)
    {
        $o = "";

        $trace = $e->getTrace();

        $i = count($trace);
        foreach ($trace as $v) {
            $o .= "#" . $i . ": at line " . $v['line'] . " of " . $v['file'] . ", " . $v['class'] . $v['type'] . $v['function'] . "\r\n";
            $i--;
        }

        return $o;
    }

    public function expire_guess()
    {
        $dt = time();
        if ($this->last_modified > 0) {
            $past = time() - $this->last_modified;
            $mins = floor($past / 60);
            $hours = floor($mins / (60));
            $days = floor($hours / 24);
            if ($days > 7) {
                return time() + 7 * 24 * 60 * 60;
            }
            if ($days > 1) {
                return time() + 2 * 24 * 60 * 60;
            }
            if ($hours > 12) {
                return time() + 12 * 60 * 60;
            }
            if ($hours > 6) {
                return time() + 6 * 60 * 60;
            }
            if ($hours > 2) {
                return time() + 2 * 60 * 60;
            }
            if ($hours > 1) {
                return time() + 60 * 60;
            }
            if ($mins > 30) {
                return time() + 30 * 60;
            }
            if ($mins > 15) {
                return time() + 15 * 60;
            }
            return time() + 60;
        }
        return $dt;
    }

    public function in_list($val, $list)
    {
        $x = explode(",", $list);
        foreach ($x as $v) {
            if ($v == $val) {
                return true;
            }
        }
        return false;
    }

    public function values_match($row, $copy, $list) {
        $x = explode(",", $list);
        foreach ($x as $v) {
            if ($row[$v] != $copy[$v]) {
                return false;
            }
        }
        return true;
    }

    public function copy_values(&$target, $source, $list)
    {
        $x = explode(",", $list);
        foreach ($x as $v) {
            $v = trim($v);
            $xx = explode(":", $v);
            $v = trim($xx[0]);
            if (isset($source[$v])) {
                $target[$v] = $source[$v];
            }
        }
    }

    /*
     * Source: https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/formatting.php
     */
    public function remove_accents($string) {
        if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
            return $string;
        }

        $chars = array(
            // Decompositions for Latin-1 Supplement
            'ª' => 'a',
            'º' => 'o',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Ø' => 'O',
            // Decompositions for Latin Extended-A
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
            'ſ' => 's',
            // Decompositions for Latin Extended-B
            'Ș' => 'S',
            'ș' => 's',
            'Ț' => 'T',
            'ț' => 't',
            // Euro Sign
            '€' => 'E',
            // GBP (Pound) Sign
            '£' => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            'Ơ' => 'O',
            'ơ' => 'o',
            'Ư' => 'U',
            'ư' => 'u',
            // grave accent
            'Ầ' => 'A',
            'ầ' => 'a',
            'Ằ' => 'A',
            'ằ' => 'a',
            'Ề' => 'E',
            'ề' => 'e',
            'Ồ' => 'O',
            'ồ' => 'o',
            'Ờ' => 'O',
            'ờ' => 'o',
            'Ừ' => 'U',
            'ừ' => 'u',
            'Ỳ' => 'Y',
            'ỳ' => 'y',
            // hook
            'Ả' => 'A',
            'ả' => 'a',
            'Ẩ' => 'A',
            'ẩ' => 'a',
            'Ẳ' => 'A',
            'ẳ' => 'a',
            'Ẻ' => 'E',
            'ẻ' => 'e',
            'Ể' => 'E',
            'ể' => 'e',
            'Ỉ' => 'I',
            'ỉ' => 'i',
            'Ỏ' => 'O',
            'ỏ' => 'o',
            'Ổ' => 'O',
            'ổ' => 'o',
            'Ở' => 'O',
            'ở' => 'o',
            'Ủ' => 'U',
            'ủ' => 'u',
            'Ử' => 'U',
            'ử' => 'u',
            'Ỷ' => 'Y',
            'ỷ' => 'y',
            // tilde
            'Ẫ' => 'A',
            'ẫ' => 'a',
            'Ẵ' => 'A',
            'ẵ' => 'a',
            'Ẽ' => 'E',
            'ẽ' => 'e',
            'Ễ' => 'E',
            'ễ' => 'e',
            'Ỗ' => 'O',
            'ỗ' => 'o',
            'Ỡ' => 'O',
            'ỡ' => 'o',
            'Ữ' => 'U',
            'ữ' => 'u',
            'Ỹ' => 'Y',
            'ỹ' => 'y',
            // acute accent
            'Ấ' => 'A',
            'ấ' => 'a',
            'Ắ' => 'A',
            'ắ' => 'a',
            'Ế' => 'E',
            'ế' => 'e',
            'Ố' => 'O',
            'ố' => 'o',
            'Ớ' => 'O',
            'ớ' => 'o',
            'Ứ' => 'U',
            'ứ' => 'u',
            // dot below
            'Ạ' => 'A',
            'ạ' => 'a',
            'Ậ' => 'A',
            'ậ' => 'a',
            'Ặ' => 'A',
            'ặ' => 'a',
            'Ẹ' => 'E',
            'ẹ' => 'e',
            'Ệ' => 'E',
            'ệ' => 'e',
            'Ị' => 'I',
            'ị' => 'i',
            'Ọ' => 'O',
            'ọ' => 'o',
            'Ộ' => 'O',
            'ộ' => 'o',
            'Ợ' => 'O',
            'ợ' => 'o',
            'Ụ' => 'U',
            'ụ' => 'u',
            'Ự' => 'U',
            'ự' => 'u',
            'Ỵ' => 'Y',
            'ỵ' => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            'ɑ' => 'a',
            // macron
            'Ǖ' => 'U',
            'ǖ' => 'u',
            // acute accent
            'Ǘ' => 'U',
            'ǘ' => 'u',
            // caron
            'Ǎ' => 'A',
            'ǎ' => 'a',
            'Ǐ' => 'I',
            'ǐ' => 'i',
            'Ǒ' => 'O',
            'ǒ' => 'o',
            'Ǔ' => 'U',
            'ǔ' => 'u',
            'Ǚ' => 'U',
            'ǚ' => 'u',
            // grave accent
            'Ǜ' => 'U',
            'ǜ' => 'u',
        );

        $string = strtr( $string, $chars );

        return $string;
    }

    public function transliterate($c)
    {
        if ($c == '0') {
            return $c;
        }
        $src = "A.B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z." .
            "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "1.2.3.4.5.6.7.8.9.0.А.Б.В.Г.Д.Е.Ё.Ж.З.И.Й.К.Л.М.Н.О.П.Р.С.Т.У.Ф.Х.Ц.Ч.Ш.Щ.Ъ.Ы.Ь.Э.Ю.Я." .
            "а.б.в.г.д.е.ё.ж.з.и.й.к.л.м.н.о.п.р.с.т.у.ф.х.ц.ч.ш.щ.ъ.ы.ь.э.ю.я.é";
        $dest = "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "1.2.3.4.5.6.7.8.9.0.a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya." .
            "a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya.e";
        if (!$this->tl_array) {
            $x = explode(".", $src);
            $y = explode(".", $dest);
            $l = count($x);
            $this->tl_array = [];
            for ($i = 0; $i < $l; $i++) {
                $this->tl_array['_' . $x[$i]] = $y[$i];
            }
        }
        if ($this->tl_array['_' . $c]) {
            return $this->tl_array['_' . $c];
        }

        if ($c == ' ' || $c == '-' || $c == '_' || $c == ':' || $c == '*') {
            return '-';
        }

        if ($c == '\'' || $c == '"') {
            return "";
        }

        if ($c == ',' || $c == ';') {
            return ',';
        }

        return '.';
    }

    public function transliterate_url($source)
    {
        $s = $this->remove_accents($source);
        $t = "";
        $l = mb_strlen($s);
        $c = '';
        $pc = '';
        for ($i = 0; $i < $l; $i++) {
            $c = mb_substr($s, $i, 1, "UTF-8");
            $tc = $this->transliterate($c);
            if (($pc == '-' || $pc == '.' || $pc == ',') && ($tc == '-' || $tc == '.' || $tc == ',')) {
                continue;
            }
            $pc = $tc;
            $t .= $tc;
        }
        $l = mb_strlen($t);
        $lc = mb_substr($t, $l - 1, 1);
        if ($lc == '.' || $lc == ',' || $lc == '-') {
            $t = mb_substr($t, 0, $l - 1);
        }
        return $t;
    }

    function indexes_list($ar)
    {
        $lst = "";
        foreach ($ar as $n => $v) {
            if ($lst != "") {
                $lst .= ", ";
            }
            $lst .= $n;
        }
        return $lst;
    }

    public function recaptcha_check()
    {
        $response = $_POST['g-recaptcha-response'];
        if (!$response) {
            return false;
        }

        $postdata = http_build_query(
            array(
                'secret' => GOOGLE_KEY_RECAPTCHA,
                'response' => $response
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        $data = json_decode($result, true);
        $this->last_recaptcha_result = $data;
        if ($data['success']) {
            return true;
        }

        return false;
    }

    /**
     * Pseudo-random number generator
     *
     * Used in {{syn v=""}} to select variants pseudo-randomly based on the page URL seed
     */
    public function prand($min, $max){
        $pv = $this->prand_seed;
        $cv = $this->prand_seed * $this->prand_seed + 7;
        //echo $cv." => ";
        $val = $cv / 10;
        $val = $val % 100000;

        if($val == 0){
            $val = 15891;
        }
        if($val == 1){
            $val = 21131;
        }

        if($pv == $val){
            $val += 7;
        }

        $this->prand_seed = $val;
        //echo $val;

        $diff = abs($max - $min);
        $rv = $val / (100000 / $diff);
        $rv += $min;
        return $rv;
    }

    /**
     * Create a 5-digital-digit prand_seed from an md5 string
     *
     * A few digits of the md5 string will be cut out of the middle of the string, converted to integer, and limited at 11111-27999
     *
     * @param $md string The input md5 string
     *
     * @return int
     */

    public function prand_md5_seed($md5){
        $s = substr($md5, 8, 6);
        $int = intval($s, 16);
        $v = $int % 16889;
        $this->prand_seed = $v + 11111;
        return $this->prand_seed;
    }

    /**
     * Pseudo-random shuffling of an array
     *
     * Unlike the original shuffle(), this one will generate the same pseudo-random results depending on the initial prand_seed
     *
     * @param $array &array The array to shuffle
     *
     * @return bool
     */
    public function prand_shuffle(&$array)
    {
        $l = count($array);
        for($i = 0; $i < $l; $i++){
            $v = $array[$i];
            $idx = $this->prand(0, $l - 1);
            $array[$i] = $array[$idx];
            $array[$idx] = $v;
        }
        return true;
    }

    public function json_dump($data){
        echo "<pre>";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }

    public function amount($number){
        $str = number_format($number, 10, ",", "");

        $str = preg_replace('~\,0+$~','', $str);

        return $str;
    }

    public function format_size($bytes, $lang)
    {
        $bytes = intval($bytes);
        if ($bytes <= 0) return $bytes;
        if($lang == 'en'){
            $formats = array("%d bytes", "%.1f KB", "%.1f MB", "%.1f GB", "%.1f TB");
        }else{
            $formats = array("%d байт", "%.1f Кб", "%.1f Мб", "%.1f Гб", "%.1f Тб");
        }

        $logsize = min((int)(log($bytes) / log(1024)), count($formats) - 1);
        return sprintf($formats[$logsize], $bytes / pow(1024, $logsize));
    }

    public function parse_array($text) {
        $x = explode("\n", $text);
        $rv = [];
        foreach($x as $v){
            $v = trim($v);
            $xx = explode("=", $v);
            if (!$xx[0]) {
                continue;
            }
            $rv[$xx[0]] = $xx[1];
        }
        return $rv;
    }

    private $md5_shortener = "yenaEFGHIJKLMNOPQRSTUVWXYZDbcdBfghijklmCopqrstuvwxAz0123456789-_";

    public function safe_short_md5($short_md5) {
        $rv = str_replace("_", "", $short_md5);
        $rv = str_replace("-", "", $rv);
        return $rv;
    }

    public function short_md5($md5) {
        $bin = hex2bin($md5);
        $rv = "";
        $byte_index = 0;
        $src_bit = 0;
        $dst_bit = 0;
        $value = 0;
        $length = 16;

        while (true) {
            $byte = ord($bin[$byte_index]);
            $bit = ($byte >> $src_bit) & 0x1;
            $add = $bit << $dst_bit;
            $value += $add;
            $src_bit++;
            $dst_bit++;
            if ($dst_bit >= 6) {
                $rv .= $this->md5_shortener[$value];
                $value = 0;
                $dst_bit = 0;
            }
            if ($src_bit >= 8) {
                $src_bit = 0;
                $byte_index++;
                if ($byte_index >= $length) {
                    $rv .= $this->md5_shortener[$value];
                    break;
                }
            }
        }
        return $rv;
    }

    public function long_md5($short_md5) {
        $rv = "";
        $byte_index = 0;
        $src_bit = 0;
        $dst_bit = 0;
        $value = 0;
        $length = strlen($short_md5);

        while (true) {
            $char = $short_md5[$byte_index];
            $byte = strpos($this->md5_shortener, $char);
            $bit = ($byte >> $src_bit) & 0x1;
            $add = $bit << $dst_bit;
            $value += $add;
            $src_bit++;
            $dst_bit++;
            if ($dst_bit >= 8) {
                $rv .= chr($value);
                $value = 0;
                $dst_bit = 0;
            }
            if ($src_bit >= 6) {
                $src_bit = 0;
                $byte_index++;
                if ($byte_index >= $length) {
                    break;
                }
            }
        }
        return bin2hex($rv);
    }

    public function join_text($glue, $list) {
        $pts = [];
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            $pts[] = $item;
        }
        return implode($glue, $pts);
    }

    public function first_match($list) {
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            return $item;
        }
        return "";
    }

    function remove_emoji($text) {
        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|'.
            '\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{1F000}-\x{1FEFF}]?|'.
            '[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{1F000}-\x{1F9FF}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{1F000}-\x{1F9FF}][\x{1F000}-\x{1FEFF}]?/u', '', $text);
    }

    public function make_keywords($list) {
        if (!isset($this->hf_words)) {
            $hf_words = "что
тот
быть
весь
это
как
она
они
так
сказать
этот
который
один
еще
такой
только
себя
свое
какой
когда
уже
для
вот
кто
год
мой
или
если
нет
даже
другой
наш
свой
под
где
есть
сам
раз
чтобы
два
там
чем
ничто
потом
очень
при
мог
могли
могут
может
надо
без
теперь
тоже
сейчас
можно
после
место
что
над
три
ваш
несколько
пока
хорошо
более
хотя
всегда
куда
сразу
совсем
об
почти
много
между
про
лишь
однако
чуть
зачем
любой
назад
оно
поэтому
совершенно
точно
среди
иногда
ко
затем
четыре
также
откуда
чтоб
мало
немного
впрочем
разве
против
иной
лучший
вполне
иметь
имеет
имеют
нужно
начать
включает
понятие
нем
нём
нужно
начать
каждое
каждый
каждая
";
            $x = explode("\n", $hf_words);
            $hf = [];
            foreach ($x as $v) {
                $v = trim($v);
                if (!$v) {
                    continue;
                }
                $hf[$v] = true;
            }
            $this->hf_words = $hf;
        }
        $words = [];
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            $item = trim(strip_tags($item));
            if (!$item) {
                continue;
            }
            $item = $this->remove_emoji($item);
            $item = preg_replace("#[[:punct:]](?<!-)#", "", $item);
            $item = preg_replace("#[[:space:]]#", " ", $item);
            $x = explode(" ", $item);
            foreach ($x as $v) {
                $v = trim($v);
                if (!$v) {
                    continue;
                }
                if (mb_strlen($v) < 3) {
                    continue;
                }
                $v = mb_strtolower($v);
                if ($this->has_similar_index($this->hf_words, $v, 80)) {
                    continue;
                }
                $key = $this->has_similar_index($words, $v, 80);
                if (!$key) {
                    $words[$v] = 1;
                } else {
                    $words[$key]++;
                }
            }
        }

        arsort($words);
        $i = 0;
        $pts = [];
        foreach ($words as $k => $v) {
            $pts[] = $k;
            $i++;
            if ($i > 10) {
                break;
            }
        }
        $this->last_keywords = $words;
        return implode(", ", $pts);
    }

    public function usergroup($group) {
        return $this->auth->credentials($group);
    }

    public function has_similar_index($words, $word, $target_percent) {
        foreach ($words as $key => $value) {
            $percent = 0;
            similar_text($key, $word,$percent);
            if ($percent >= $target_percent) {
                return $key;
            }
        }
        return false;
    }

    public function log($v) {
        if (!$this->log_enabled) {
            return;
        }
        if (is_array($v) || is_object($v)) {
            $v = "\r\n".json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $time = microtime(true);
        $dt = floor($time);
        $micro = sprintf("%03d", round(($time - $dt)*1000));
        $output = $this->form_time_full($dt).".".$micro.": ".$v."\r\n";
        error_log($output, 3, $this->log_file_path);
    }

    public function smartybr($v) {
        if (!is_string($v)) {
            return $v;
        }

        if (strstr($v, "[[") !== false) {
            $this->save_setting("smartybr", $v);
        }
        $v = str_replace("[[", "{{", str_replace("]]", "}}", $v));

        return $v;
    }
}