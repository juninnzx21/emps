<?php

if ($_POST['post_get_params']) {
    $payload = $_POST['payload'];

    $template = str_replace("{slash}", '/', $key);

    $title = $key;

    $nlst = $emps->blocks->list_template_params($template);
    if (isset($nlst['template_title'])) {
        $title = $nlst['template_title'];
        unset($nlst['template_title']);
    }

    $lst = [];
    foreach ($nlst as $n => $v) {
        $v['name'] = $n;
        if (!isset($v['value'])) {
            $v['value'] = $v['default'];
        }

        $lst[] = $v;
    }

    $emps->json_ok(['lst' => $lst, 'title' => $title]);

}