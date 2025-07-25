<?php

header("Content-Type: text/css; charset=utf-8");

$emps->no_smarty = true;

header("Last-Modified: ".date("r", time() - 60*60*12));
header("Expires: ".date("r",time()+60*60*24*7));
header("Pragma: ");
header("Cache-Control: max-age=".(60*60*24*7));

echo '/* fonts.css */'."\r\n";
echo file_get_contents($emps->plain_file("/fonts/fonts.css"));
echo '/* bootstrap.min.css */'."\r\n";

$css_fw = $emps->get_setting("css_fw");
if ($css_fw == "bulma") {
    echo file_get_contents($emps->plain_file("/bulma/css/bulma.min.css")).PHP_EOL;
    echo file_get_contents($emps->plain_file("/bulma/ext.css")).PHP_EOL;
} else {
}

echo '/* default.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/default.css"));
echo '/* site-default.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/site-default.css"));
echo '/* editor.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/editor.css"));

echo ".mce-content-body {padding: 0.5rem !important}".PHP_EOL;