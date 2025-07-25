<?php
global $emps;

function smarty_common_photoreport($params)
{
    global $smarty, $emps, $sp_photos;

    require_once $emps->common_module('photos/photos.class.php');

    if (!isset($sp_photos)) {
        $sp_photos = new EMPS_Photos();
    }

    $context_id = $params['context'];
    $ps = [];

    if (!$context_id) {
        $list = $params['list'];
        $x = explode(",", $list);
        $cl = "";
        foreach ($x as $v) {
            $pic = $emps->db->get_row("e_uploads", "id = " . intval($v));
            $cl .= "." . $v;
            if ($pic) {
                $pic = $sp_photos->explain_pic($pic);
                $ps[] = $pic;
            }
        }
        $smarty->assign("rel", "rel" . md5($cl));
    } else {
        $ps = $sp_photos->list_pics($context_id, 1000);
        $smarty->assign("rel", "rel" . md5($context_id));
    }

    $smarty->assign("ctx", $context_id);

    $smarty->assign("vert", $params['vert']);
    $smarty->assign("fullpic", $params['fullpic']);
    $smarty->assign("size", $params['size']);
    $smarty->assign("pretitle", $params['pretitle']);

    $smarty->assign("pset", $ps);

}

function smarty_plugin_montage($params)
{
    global $smarty;

    smarty_common_photoreport($params);

    return $smarty->fetch("db:photos/montage");
}

function smarty_plugin_photoreport($params)
{
    global $smarty;

    smarty_common_photoreport($params);

    return $smarty->fetch("db:photos/photoreport");
}

function smarty_plugin_downloads($params)
{
    global $smarty, $emps, $up;

    require_once($emps->common_module('uploads/uploads.class.php'));

    if (!isset($sp_up)) {
        $sp_up = new EMPS_Uploads;
    }

    $context_id = $params['context'];
    if ($context_id) {
        $lst = $sp_up->list_files($context_id, 1000);
        $smarty->assign("filelist", $lst);
        return $smarty->fetch("db:page/filelist");
    }

    $list = $params['list'];
    if ($list) {
        $lst = array();
        $xx = explode(",", $list);
        foreach ($xx as $v) {
            $id = $v + 0;
            $ra = $emps->db->get_row("e_files", "id = " . $id);
            $ra['fsize'] = format_size($ra['size']);
            $lst[] = $ra;

        }

        $smarty->assign("filelist", $lst);
        return $smarty->fetch("db:page/filelist");
    }
}

function smarty_plugin_video($params)
{
    global $smarty, $emps;

    $id = $params['id'];
    $mctx = $emps->p->get_context(DT_VIDEO, 1, $id);

    $video = $emps->db->get_row("e_videos", "id=$id");
    if ($video) {
        $video = $emps->p->read_properties($video, $mctx);

        $smarty->assign("video", $video);
        return $smarty->fetch("db:videos/videocon");
    }
}

function smarty_modifier_load_pic($v)
{
    global $emps;

    $id = intval($v);
    $pic = $emps->db->get_row("e_uploads", "id = {$id}");
    if ($pic) {
        return $pic;
    }
    $pic = [];
    $pic['md5'] = '';
    $pic['filename'] = 'empty.jpg';
    return $pic;
}

function smarty_modifier_blockparam($v, $type)
{
    global $emps;

    $id = intval($v);
    $row = $emps->db->get_row("e_block_param_values ", "id = {$id}");
    if (!$row) {
        return [];
    }
    if ($type == 'v_json') {
        $a = json_decode($row['v_json'], true);
        return $a;
    }
    return [];
}

$fn = $emps->common_module('config/smarty/plugins.php');
if (file_exists($fn)) {
    require_once $fn;
}

