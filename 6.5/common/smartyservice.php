<?php

$emps->no_smarty = true;

$html = file_get_contents(EMPS_SCRIPT_WEB);
if ($html == "") {
    $smarty->clearCompiledTemplate();
    echo "Empty website: fixed!";
    exit();
}

$html = file_get_contents(EMPS_SCRIPT_WEB."/admin/");
if ($html == "") {
    $smarty->clearCompiledTemplate();
    echo "Empty website (/admin/): fixed!";
    exit();
}

$hours = $emps->get_setting("smarty_clear_hours");

if (!$hours) {
    $hours = 12;
}

$last_dt = intval($emps->get_setting("last_smarty_clear"));
if ($last_dt < (time() - ($hours * 60 * 60))) {
    $emps->save_setting("last_smarty_clear", time());
    $smarty->clearCompiledTemplate();
    echo "Cleared!";
}



