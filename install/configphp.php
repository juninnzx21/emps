<?php

require_once "functions.php";

$version = phpversion();
$installer->say( "PHP version: {$version}");

$x = explode(".", $version);
$ver = $x[0].".".$x[1];

$installer->say("PHP ver: {$ver}");

$etc_path = "/etc/php/{$ver}";

$installer->say("Fixing php.ini (fpm)...");
$phpini_path = $etc_path."/fpm/php.ini";
$installer->modify_ini_file($phpini_path, __DIR__."/templates/phpini.json");

$installer->say("Fixing php.ini (cli)...");
$phpini_path = $etc_path."/cli/php.ini";
$installer->modify_ini_file($phpini_path, __DIR__."/templates/phpini.json");

$installer->say("Fixing www.conf (fpm)...");
$fpmconf_path = $etc_path."/fpm/pool.d/www.conf";
$installer->modify_ini_file($fpmconf_path, __DIR__."/templates/wwwconf.json");

system("service php{$ver}-fpm restart");