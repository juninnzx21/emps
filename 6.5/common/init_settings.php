<?php

header("Content-Type: text/plain");

$emps->no_smarty = true;

$setting = $emps->get_setting("handle_menus");
if (!$setting) {
    $emps->save_setting("handle_menus", "main");
    echo "Set handle_menus\r\n";
}

$setting = $emps->get_setting("page/mainhead");
if (!$setting) {
    $emps->save_setting("page/mainhead", "A New EMPS Website");
    echo "Set page/mainhead\r\n";
}

$setting = $emps->get_setting("startpage");
if (!$setting) {
    $emps->save_setting("startpage", "front");
    echo "Set page/startpage\r\n";
}

$setting = $emps->get_setting("order_mailto");
if (!$setting) {
    $emps->save_setting("order_mailto", "user@domain.tld, another-user@domain.tld");
    echo "Set order_mailto\r\n";
}

$setting = $emps->get_setting("css_fw");
if (!$setting) {
    $emps->save_setting("css_fw", "bulma");
    echo "Set css_fw\r\n";
}

$setting = $emps->get_setting("admin_tools");
if (!$setting) {
    $emps->save_setting("admin_tools", "vv");
    echo "Set admin_tools\r\n";
}

