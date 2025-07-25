<?php

echo "EMPS 6.X\r\n";

$command = $argv[1];

if ($command == "install") {
    copy("6.X/pre_bower.json", "6.X/bower.json");
    copy("6.X/pre_package.json", "6.X/package.json");
    copy("pre_composer.json", "composer.json");
    chdir("6.X");
    system("bower update --allow-root");
    system("npm install");
}