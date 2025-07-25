<?php

if ($emps->auth->credentials("admin")) {

    $id = intval($key);
    $row = $emps->blocks->get_block($id);
    if (!$row) {
        $emps->json_error("Block not found!"); exit;
    }

    if ($_POST['post_save_values']) {
        $payload = $_POST['payload'];

        $ord = 10;
        foreach ($payload as $param) {
            $emps->blocks->save_param_value($row['id'], $param, $emps->lang, 0, $ord);
            $ord += 10;
        }

        $emps->db->sql_update_row("e_blocks", ['SET' => ['dt' => time()]], "id = {$row['id']}");
        $emps->json_ok([]); exit;
    }

    $nlst = $emps->blocks->list_template_params($row['template']);

    $title = "";

    if (isset($nlst['template_title'])) {
        $title = $nlst['template_title'];
        unset($nlst['template_title']);
    }

    $vlst = $emps->blocks->list_block_param_values($row['id']);
    foreach ($vlst as $vv) {
        if ($vv['vtype'] == 'c') {
            $value = $vv['v_char'];
        } elseif (substr($vv['vtype'], 0, 1) == 'a') {
            $value = json_decode($vv['v_json'], true);
        } else {
            $value = $vv['v_text'];
        }
        if (isset($nlst[$vv['name']])) {
            $nlst[$vv['name']]['value'] = $value;
        }
    }
    //var_dump($nlst['blocks']); exit;
    if (isset($nlst['blocks'])) {
        $nlst['blocks']['template_title'] = $title;
    }


    $lst = [];
    foreach ($nlst as $n => $v) {
        $v['name'] = $n;
        if (!isset($v['value'])) {
            $v['value'] = $v['default'];
        }
        if (is_array($v['value'])) {
            $emps->blocks->check_expanded($v['value']);
        }

        $lst[] = $v;
    }


    //$emps->json_ok(['lst' => $lst, 'vlst' => $vlst, 'nlst' => $nlst]);
    $emps->json_ok(['lst' => $lst]);
} else {
    $emps->json_error("Admin access needed!");
}