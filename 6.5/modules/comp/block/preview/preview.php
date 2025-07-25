<?php

$emps->no_smarty = true;

header("Content-Type: text/html; charset=utf-8");

if ($emps->auth->credentials("admin")) {

    $emps->page_property("vuejs", true);

    $emps->blocks->editor_mode = true;
    $id = intval($key);
    $row = $emps->blocks->get_block($id);
    if (!$row) {
        echo "Block not found!"; exit;
    }

    $emps->page_property("blocks", 1);
    $emps->pre_display();
    $smarty->assign("no_html_scroll", true);

    $tpl = $smarty->createTemplate("sblk:".$row['id']);

//    echo $tpl->source->getContent();
    $html = $tpl->fetch();

    if ($_REQUEST['inner'] ?? false) {
        echo $html;
    } else {
        $smarty->display("db:page/headtags");
        echo "<body><div id='preview-app' class='blocks container'>";
        echo "<dynamic :template='html' :key='dckey'></dynamic>";
        $smarty->display("db:_comp/block/preview,controls");
        echo "</div>";
        $smarty->display("db:page/commonfoot");
        $smarty->display("db:page/footscripts");
        echo "<script>var html = ".json_encode($html).";</script>";
        $smarty->display("db:_comp/block/preview,footscripts");
        echo "</body>";
    }

} else {
    echo "Admin access needed!";
}
