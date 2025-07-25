<?php

trait EMPS_Common_Files
{
    public function try_page_file_name($page_name, $first_name, $include_name, $type, $path, $lang)
    {
        $fn = $path . '/modules/' . $page_name;
        switch ($type) {
            case 'view':
                if (mb_substr($first_name, 0, 1) == '!') {
                    $first_name = mb_substr($first_name, 1);
                    $fn .= '/' . $first_name;
                } else {
                    $fn .= '/' . $first_name . '.' . $lang . '.htm';
                }
                break;
            case 'controller':
                $fn .= '/' . $first_name . '.php';
                break;
            case 'inc':
                $fn .= '/' . $include_name;
                break;
        }
        //echo "file name: {$fn}\r\n";
        if (isset($this->require_cache['try_page_file_name'][$fn])) {
            return $this->require_cache['try_page_file_name'][$fn];
        }

        $fn = $this->resolve_include_path($fn);

        //echo "resolved: {$fn}\r\n";

        $this->require_cache['try_page_file_name'][$fn] = $fn;
        return $fn;
    }

    public function resolve_include_path($fn) {
        $ofn = $fn;
        $fn = stream_resolve_include_path($fn);
        if ($fn === false) {
            if (file_exists($ofn)) {
                $fn = $ofn;
            } else {
                //echo "not found: {$ofn} / {$fn}\r\n";
                //echo shell_exec("cat {$ofn} 2>&1");
            }
        }
        return $fn;
    }

    public function try_template_name($path, $page_name, $lang)
    {
        $fn = $path . '/templates/' . $page_name . '.' . $lang . '.htm';
        if (isset($this->require_cache['try_template_name'][$fn])) {
            return $this->require_cache['try_template_name'][$fn];
        }
        $fn = $this->resolve_include_path($fn);

        $this->require_cache['try_template_name'][$fn] = $fn;
        return $fn;
    }

    public function module_exists($name) {
        $fn = $this->page_file_name("_{$name}", "controller");
        if (file_exists($fn)) {
            return true;
        }
        return false;
    }

    public function hyphens_to_slashes($file_name) {
        $x = explode(",", $file_name);
        if (count($x) > 1) {
            $last = array_pop($x);
            $name = implode(",", $x);
            $name = str_replace('-', '/', $name);
            $file_name = $name . "," . $last;
        } else {
            $file_name = str_replace('-', '/', $file_name);
        }
        return $file_name;
    }

    public function min_file_name($page_name) {
        if (isset($this->require_cache['min_file'][$page_name])) {
            return $this->require_cache['min_file'][$page_name];
        }

        $page_name = substr($page_name, 1);
        $page_name = $this->hyphens_to_slashes($page_name);

        $x = explode(',', $page_name, 2);
        $page_name = $x[0];
        $include_name = $x[1];

        $prefix = EMPS_SCRIPT_PATH."/local/minified/";

        if (!is_dir($prefix)) {
            mkdir($prefix);
            chmod($prefix, 0777);
        }

        $path = $prefix;
        $x = explode("/", $page_name);
        foreach ($x as $dir) {
            $path .= $dir."/";
            if (!file_exists($path)) {
                mkdir($path);
                chmod($path, 0777);
            }
        }

        $fn = $path.$include_name;

        $this->require_cache['min_file'][$page_name] = $fn;
        return $fn;
    }

    public function page_file_name($page_name, $type)
    {
        // This function controls the naming of files used by the application
        if (isset($this->require_cache['page_file'][$type][$page_name])) {
            return $this->require_cache['page_file'][$type][$page_name];
        }
        $first_name = "";
        if (substr($page_name, 0, 1) == '_') {
            $page_name = substr($page_name, 1);
            $page_name = $this->hyphens_to_slashes($page_name);
            if ($type == 'inc') {
                $x = explode(',', $page_name, 2);
                $page_name = $x[0];
                $include_name = $x[1];
            } else {
                $x = explode(',', $page_name);
                if (isset($x[1])) {
                    $page_name = $x[0];
                    $first_name = $x[1];
                } else {
                    $x = explode('/', $page_name);
                    $first_name = array_pop($x);
                }
            }

            if (!isset($include_name)) {
                $include_name = $page_name;
            }

            $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, $this->lang);
            if (!$fn) {
                $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, 'nn');
                if (!$fn) {
                    $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, $this->lang);
                    if (!$fn) {
                        $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, 'nn');
                        if (!$fn) {
                            $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, $this->lang);
                            if (!$fn) {
                                $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, 'nn');
                                if (!$fn) {
                                    $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, $this->lang);
                                    if (!$fn) {
                                        $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, 'nn');
                                    }
                                }
                            }
                        }
                    }
                }
            }


        } else {
            $fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, $this->lang);
            if (!$fn) {
                $fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, 'nn');
                if (!$fn) {
                    $fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, $this->lang);
                    if (!$fn) {
                        $fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, 'nn');
                        if (!$fn) {
                            $fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, $this->lang);
                            if (!$fn) {
                                $fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, 'nn');
                                if (!$fn) {
                                    $fn = $this->try_template_name(EMPS_COMMON_PATH_PREFIX, $page_name, $this->lang);
                                    if (!$fn) {
                                        $fn = $this->try_template_name(EMPS_COMMON_PATH_PREFIX, $page_name, 'nn');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->require_cache['page_file'][$type][$page_name] = $fn;
        return $fn;
    }

    public function try_common_module_html($path, $file_name, $lang)
    {

        $x = explode(".", $file_name);
        $len = mb_strlen($x[count($x) - 1], "utf-8");
        if ($len <= 3) {
            $fn = $path . '/modules/_common/' . $file_name;
        } else {
            $fn = $path . '/modules/_common/' . $file_name . '.' . $lang . '.htm';
        }

        if (isset($this->require_cache['common_module_html_try'][$fn])) {
            return $this->require_cache['common_module_html_try'][$fn];
        }

        if (!file_exists($fn)) {
            $fn = false;
        } else {
//            error_log("Common file: {$fn}\r\n");
        }

        $this->require_cache['common_module_html_try'][$fn] = $fn;
        return $fn;
    }

    public function common_module_html($file_name)
    {
        // This function controls the naming of files used by common modules
        if (isset($this->require_cache['common_module_html'][$file_name])) {
            return $this->require_cache['common_module_html'][$file_name];
        }
        $fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH, $file_name, $this->lang);
        if (!$fn) {
            $fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH, $file_name, 'nn');
            if (!$fn) {
                $fn = $this->try_common_module_html(EMPS_SCRIPT_PATH, $file_name, $this->lang);
                if (!$fn) {
                    $fn = $this->try_common_module_html(EMPS_SCRIPT_PATH, $file_name, 'nn');
                    if (!$fn) {
                        $x = explode(".", $file_name);
                        $len = mb_strlen($x[count($x) - 1], "utf-8");
                        if ($len <= 3) {
                            $fn = EMPS_PATH_PREFIX . '/common/' . $file_name;
                            $fn = $this->resolve_include_path($fn);
                            if (!$fn) {
                                $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name;
                                $fn = $this->resolve_include_path($fn);
                            }
                        } else {
                            $fn = EMPS_PATH_PREFIX . '/common/' . $file_name . '.' . $this->lang . '.htm';
                            $fn = $this->resolve_include_path($fn);
                            if (!$fn) {
                                $fn = EMPS_PATH_PREFIX . '/common/' . $file_name . '.nn.htm';
                                $fn = $this->resolve_include_path($fn);
                                if (!$fn) {
                                    $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name . '.' . $this->lang . '.htm';
                                    $fn = $this->resolve_include_path($fn);
                                    if (!$fn) {
                                        $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name . '.nn.htm';
                                        $fn = $this->resolve_include_path($fn);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->require_cache['common_module_html'][$file_name] = $fn;
        return $fn;
    }

    public function try_common_module($path, $file_name)
    {
        if (isset($this->require_cache['common_module_try'][$path][$file_name])) {
            return $this->require_cache['common_module_try'][$path][$file_name];
        }
        $fn = $path . '/modules/_common/' . $file_name;
        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['common_module_try'][$path][$file_name] = $fn;
        return $fn;
    }

    public function common_module_ex($file_name, $level)
    {
        // This function controls the naming of files used by common modules
        if (isset($this->require_cache['common_module'][$level][$file_name])) {
            return $this->require_cache['common_module'][$level][$file_name];
        }

        $fn = $this->try_common_module(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
        if (!$fn || ($level > 0)) {
            $fn = $this->try_common_module(EMPS_SCRIPT_PATH, $file_name);
            if (!$fn || ($level > 1)) {
                $fn = EMPS_PATH_PREFIX . '/common/' . $file_name;
                $fn = $this->resolve_include_path($fn);
                if (!$fn) {
                    $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name;
                    $fn = $this->resolve_include_path($fn);
                }
            }
        }

        if ($fn != false) {
            $this->require_cache['common_module'][$level][$file_name] = $fn;
        }

        return $fn;
    }

    public function common_module($file_name)
    {
        return $this->common_module_ex($file_name, 0);
    }


    public function try_core_script($path, $file_name)
    {
        if (isset($this->require_cache['common_script_try'][$path][$file_name])) {
            return $this->require_cache['common_script_try'][$path][$file_name];
        }
        $fn = $this->resolve_include_path($path . "/core/" . $file_name . ".php");

        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['common_script_try'][$path][$file_name] = $fn;
        return $fn;
    }


    public function core_module($file_name)
    {

        $fn = $this->try_core_script(EMPS_PATH_PREFIX, $file_name);
        if (!$fn) {
            $fn = $this->try_core_script(EMPS_COMMON_PATH_PREFIX, $file_name);
        }
        return $fn;
    }

    public function try_plain_file($path, $file_name)
    {
        if (isset($this->require_cache['plain_file_try'][$path][$file_name])) {
            return $this->require_cache['plain_file_try'][$path][$file_name];
        }
        $fn = $path . $file_name;
        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['plain_file_try'][$path][$file_name] = $fn;
        return $fn;
    }

    public function plain_file($file_name)
    {
        // This function finds a file in the websites' folders
        // (first the primary website, then the base website) and then in the main EMPS folder
        if (isset($this->require_cache['plain_file'][$file_name])) {
            return $this->require_cache['plain_file'][$file_name];
        }
        $fn = $this->try_plain_file(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
        if (!$fn) {
            $fn = $this->try_plain_file(EMPS_SCRIPT_PATH, $file_name);
            if (!$fn) {
                $fn = EMPS_PATH_PREFIX . $file_name;
                $fn = $this->resolve_include_path($fn);
                if (!$fn) {
                    $fn = EMPS_COMMON_PATH_PREFIX . $file_name;
                    $fn = $this->resolve_include_path($fn);
                }
            }
        }

        if ($fn != false) {
            $this->require_cache['plain_file'][$file_name] = $fn;
        }

        return $fn;
    }

    public function load_enums_from_file_ex($file)
    {
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $x = explode("\n", $data);
            foreach ($x as $v) {
                $v = trim($v);
                $m = explode(':', $v, 2);
                $name = trim($m[0]);
                $value = null;
                if (isset($m[1])) {
                    $value = trim($m[1]);
                }

                if ($name && $value) {
                    $this->make_enum($name, $value);
                }
            }
            $this->enums_loaded = true;
        }
    }

    public function load_enums_from_file()
    {
        $file_list = [];
        for ($i = 2; $i >= 0; $i--) {
            $file = $this->common_module_ex("config/enum.nn.txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
            $file = $this->common_module_ex("config/enum." . $this->lang . ".txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }

            $file = $this->common_module_ex("config/project/enum.nn.txt", $i);
            if (!isset($file_list[$file])) {
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
            $file = $this->common_module_ex("config/project/enum." . $this->lang . ".txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
        }
    }

    public function static_svg($url) {
        if (mb_substr($url, 0, 9) == "/modules/") {
            return "";
        }
        if (mb_substr($url, 0, 7) == "/local/") {
            return "";
        }
        if (strstr($url, "..")) {
            return "";
        }

        $file_name = EMPS_WEBSITE_SCRIPT_PATH.$url;

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_SCRIPT_PATH.$url;

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_PATH_PREFIX.$url;
        $file_name = $this->resolve_include_path($file_name);

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_COMMON_PATH_PREFIX.$url;
        $file_name = $this->resolve_include_path($file_name);
        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        return "";
    }

}

