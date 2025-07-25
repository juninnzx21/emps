<?php
// CALLED ONLY FOR PROGRAM MODULES

global $pp, $key, $smarty;

$ctx = $emps->website_ctx;

if (!$emps->fast) {
    if (isset($emps->auth) && $emps->auth->credentials("admin,oper")) {
        if (isset($smarty)) {
            $smarty->assign("AdminMode", 1);
        }
    }

    $emps->load_enums_from_file();

    $file_name = $emps->common_module('config/project/webinit.php');
    if (file_exists($file_name)) {
        require_once $file_name;
    }

    if (isset($_GET['load_enum'])) {
        $emps->json_ok(['enum' => $emps->enum[$_GET['load_enum']]]);
        exit;
    }
}
