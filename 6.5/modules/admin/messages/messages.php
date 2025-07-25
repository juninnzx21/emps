<?php
if ($emps->auth->credentials("admin")):

    $perpage = 25;
    $start = intval($start);

    $r = $emps->db->query("select * from ".TP."e_messages where 
        (context_id = 0 or context_id = " . $emps->website_ctx . ")
        order by id desc limit {$start}, {$perpage} 
        ");

    $smarty->assign("pages", $emps->count_pages($emps->db->found_rows()));
    $lst = [];
    while ($ra = $emps->db->fetch_named($r)) {
        $ra['time'] = $emps->form_time($ra['dt']);
        $lst[] = $ra;
    }

    $smarty->assign("lst", $lst);
else:
    $emps->deny_access("AdminNeeded");
endif;
