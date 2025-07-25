<?php

$emps->no_smarty = true;

$last_modified = date("r", time() - 60 * 60 * 24 * 7);
$expires = date("r", time() + 60 * 60 * 24 * 7);

header("Last-Modified: " . $last_modified);
header("Expires: " . $expires);
header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
header_remove("Pragma");

$part = str_replace("-", "/", $key);
$file = str_replace("..", "", $start);

$x = explode(".", $file);
$ext = array_pop($x);

if ($ext == "css") {
    header("Content-Type: text/css");
}

if ($ext == "js") {
    header("Content-Type: application/javascript; charset=utf-8");
}

if ($ext == "vue") {
    header("Content-Type: text/html; charset=utf-8");
}

if ($ext == "php") {
    $emps->not_found();
    exit;
}
if ($ext == "htm") {
    $emps->not_found();
    exit;
}

$page = "_{$part},{$file}";

$file_name = $emps->page_file_name($page, "inc");
if(!$file_name){
    $emps->not_found();
    exit;
}

if ($ext == "vue") {
    $emps->pre_display();
    $page = "_{$part},!{$file}";
    $smarty->display("db:{$page}");
} elseif ($ext == "js") {
    $fh = fopen($file_name, "rb");
    if ($fh) {
        $fl = fgets($fh);
        fclose($fh);
        if (substr($fl, 0, 9) == "// minify" && !$emps_no_nodejs) {
            $min_file_name = $emps->min_file_name($page);
            //$min_file_name = $file_name.".min";
            $pass = false;
            if (file_exists($min_file_name)) {
                if (filemtime($file_name) < filemtime($min_file_name)) {
                    $pass = true;
                }
            }
            if ($pass) {
                $fh = fopen($min_file_name, "rb");
                if ($fh) {
                    fpassthru($fh);
                    fclose($fh);
                }
            } else {
                $uglify = EMPS_COMMON_PATH_PREFIX."/node_modules/uglify-js/bin/uglifyjs";
                $uglify = $emps->resolve_include_path($uglify);
                if (file_exists($uglify)) {
                    $rv = shell_exec("node {$uglify} --compress --mangle -- {$file_name}");
                } else {
                    $rv = file_get_contents($file_name);
                }
                file_put_contents($min_file_name, $rv);
                chmod($min_file_name, 0777);
                echo $rv;
            }
        } else {
            $fh = fopen($file_name, "rb");
            if ($fh) {
                fpassthru($fh);
                fclose($fh);
            }
        }
    }
} else {
    $fh = fopen($file_name, "rb");
    if ($fh) {
        fpassthru($fh);
        fclose($fh);
    }
}

