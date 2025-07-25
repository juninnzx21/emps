<?php
$admin_tools = $emps->get_setting("admin_tools");

if ($admin_tools == "bsv") {
    // Future support of bootstrap-based vue admin tools
} else {
    require_once $emps->page_file_name("_admin/vv/menu", "controller");
}
