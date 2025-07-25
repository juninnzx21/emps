<?php

if (isset($emps_no_common_menus)):
    trait EMPS_Common_Menus {};
else:

trait EMPS_Common_Menus
{
    /**
     * Adds a new menu item to $this->spath
     *
     * This can be a real menu item or an array prepared by a module script.
     *
     * @param $v array Menu item array
     */
    public function add_to_spath($v)
    {
        if (substr($v['uri'], 0, 1) == '#') {
            return false;
        }
        foreach ($this->spath as $cv) {
            if (($cv['id'] == $v['id'])) {
                return false;
            }
        }
        $this->spath[] = $v;
        return true;
    }

    /**
     * Scan a menu for selected items
     *
     * Iterate through a menu, including sub-menus, and mark menu items that match the current URLs.
     *
     * @param $menu array Menu array
     */
    public function scan_selected(&$menu)
    {
        $mr = 0;

        foreach ($menu as $n => $v) {
            $obtained_spath = [];
            if ($v['sub']) {
                $reserve_spath = $this->spath;
                $this->spath = [];
                $res = $this->scan_selected($v['sub']);
                $obtained_spath = $this->spath;
                $this->spath = $reserve_spath;
                $menu[$n]['sub'] = $v['sub'];
                if ($res > 0) {
                    $menu[$n]['ssel'] = $res;
                    $menu[$n]['sel'] = $v['sel'] = 1;
                }
                if ($res > 0) $mr = 1;
            }
            if (@$v['sel'] > 0) {
                if(!($this->no_spath[$v['grp']] ?? false)){
                    $this->add_to_spath($v);
                    foreach ($obtained_spath as $spv) {
                        $this->add_to_spath($spv);
                    }
                }
                $mr = 1;
            }
        }

        return $mr;
    }

    /**
     * Sorting function for menu items
     *
     * @param $a array One menu item
     * @param $b array Other menu item
     */
    function sort_menu($a, $b)
    {
        if ($a['ord'] == $b['ord']) {
            return 0;
        }
        if ($a['ord'] < $b['ord']) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Load a menu or submenu from the database
     *
     * @param $code Menu code
     * @param $parent Parent ID
     */
    public function section_menu($code, $parent)
    {
        return $this->section_menu_ex($code, $parent, 0);
    }

    /**
     * Create menu levels
     *
     * Top menu (0), then selected submenu (1), then selected sub-submenu (2), etc. Used to make the popup-menu for the current page.
     *
     * @param $menu Menu array
     * @param $mlv Menu levels array
     */
    public function menu_levels($menu, $mlv)
    {
        $mlv[] = $menu;
        foreach ($menu as $v) {
            if ($v['sel'] > 0 && $v['sub']) {
                $mlv = $this->menu_levels($v['sub'], $mlv);
                break;
            }
        }
        return $mlv;
    }

    /**
     * Prepare all website menus
     *
     * Read the 'handle_menus' setting and load the appropriate menus, including the 'admin' menu.
     */
    public function prepare_menus()
    {
        global $smarty;

        if ($this->auth->credentials("admin,author,editor,oper,seo,copywriter,buh,manager,staff,owner")) {
            $menu = $this->section_menu("admin", 0);
            $this->scan_selected($menu);
            $this->menus['admin'] = $menu;
        }

        $r = $this->get_setting('handle_menus');
        if (!$r) {
            return false;
        }
        $nsr = $this->get_setting('no_spath_menus');
        $x = explode(',', $nsr);
        $no_spath = [];
        foreach($x as $ns_code){
            $no_spath[$ns_code] = true;
        }
        $this->no_spath = $no_spath;

        $x = explode(',', $r);
        foreach($x as $v){
            unset($menu);
            $xx = explode('/', $v);
            $code = $xx[0];
            $t = "";
            if (isset($xx[1])) {
                $t = $xx[1];
            }

            $menu = $this->section_menu($code, 0);
            $this->scan_selected($menu);
            if ($t == 'mlv') {
                $mlv = [];
                $mlv = $this->menu_levels($menu, $mlv);
                $this->mlv[$code] = $mlv;
            }
            $this->menus[$code] = $menu;
        }

        $smarty->assign("menus", $this->menus);
        $smarty->assign("mlv", $this->mlv);
        return true;
    }

}

endif;