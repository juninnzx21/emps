<?php
global $emps, $smarty;
include_once(__DIR__ . '/../modules/hello/index.php');
$smarty->display("hello/templates/hello/default.tpl");
