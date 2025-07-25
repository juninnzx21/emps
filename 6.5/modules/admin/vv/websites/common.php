<?php

$vted->ref_id = $key;

$vted->add_pad_template("admin/vv/websites/pads,%s");

$search_text = $_SESSION[$vted->table_name . "_search"];

$vted->where = " where 1=1 ";

if ($search_text) {
    $ps = "%" . $search_text . "%";

    $vted->where .= " and (
    t.name like '{$ps}' 
    or t.hostname like '{$ps}'
    ) ";
}
