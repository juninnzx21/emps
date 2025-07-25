<?php

trait EMPS_Common_Properties
{
    /**
     * Sets a page property
     *
     * @param $name string Page property name (code)
     * @param $value mixed Page property value
     */
    public function page_property($name, $value)
    {
        $this->page_properties[$name] = $value;
    }

    /**
     * Copy properties from a content item (page)
     *
     * @param $code string Page URI
     */
    public function copy_properties($code)
    {
        // Load properties from get_content_data for the content item $code and save them as $page_properies

        $item = $this->get_db_content_item($code);
        $props = $this->get_content_data($item);
        unset($props['_full']);

        $this->page_properties = array_merge($this->page_properties, $props);
    }

    /**
     * Copy properties from a Markdown files
     *
     * @param $code string Page URI
     */
    public function copy_md_properties($code) {
        $file_name = $this->page_file_name($code, "view");
        if (!$file_name) {
            return;
        }
        $contents = file_get_contents($file_name);
        if (!$contents) {
            return;
        }
        $vars = [];

        $x = explode("\n", $contents);
        foreach ($x as $v) {
            $v = trim($v);
            if (mb_substr($v, 0, 1) == "#") {
                if (mb_substr($v, 1, 1) != "#") {
                    $title = trim(mb_substr($v, 1));
                    $vars['title'] = $title;
                }
            }
        }
        $xx = explode("### META", $contents);
        if (count($xx) < 2) {
            return;
        }
        $meta = trim($xx[1]);
        $x = explode("\n", $meta);
        $var = null;

        foreach ($x as $v) {
            $xx = explode("#### ", trim($v), 2);
            if (count($xx) == 2) {
                $var = $xx[1];
                continue;
            }
            $vars[$var] = $v;
        }
        foreach ($vars as $name => $val) {
            $this->page_properties[$name] = trim($val);
        }
    }

    /**
     * Set properties from a text file (can be obtained from a Smarty template with $lang and {{syn...}} applied)
     *
     * @param $code string Property codes followed by "equals" signs and property values, one property per line
     */
    public function parse_properties($code)
    {
        $x = explode("\n", $code);
        foreach($x as $v){
            $v = trim($v);
            $xx = explode("=", $v);
            $code = trim($xx[0]);
            $value = trim($xx[1]);
            $this->page_properties[$code] = $value;
        }
    }

    public function page_properties_from_settings($list){
        $x = explode(",", $list);
        foreach($x as $v){
            $v = trim($v);
            $value = $this->get_setting($v);
            if(!$value) {
                continue;
            }
            if (!isset($this->page_properties[$v])) {
                $this->page_property($v, $value);
            }
        }
    }

    public function handle_modified()
    {
        if ($this->last_modified > 0) {
            header("Expires: " . date("r", $this->expire_guess()));
            header("Last-Modified: " . date("r", $this->last_modified));

            $if_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            if ($if_modified) {
                $if_dt = strtotime($if_modified);
                if ($this->last_modified <= $if_dt) {
                    http_response_code(304);
                    exit();
                }
            }
        }
    }


}