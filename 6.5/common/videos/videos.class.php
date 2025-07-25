<?php
global $emps;

require_once $emps->common_module('xml.class.php');
require_once $emps->common_module('photos/photos.class.php');

class EMPS_Videos
{
    public $p;

    public function __construct()
    {
        global $emps;

        $this->p = new EMPS_Photos;

        $emps->p->register_cleanup(array($this, 'delete_videos_context'));
    }

    function parse_video_url($url)
    {
        $a = array();

        $x = explode('youtube.com/watch?', $url, 2);
        if ($x[1]) {
            $y = explode('&', $x[1]);
            foreach ($y as $v) {
                $z = explode('=', $v);
                if ($z[0] == 'v') {
                    $a['youtube_id'] = $z[1];
                }
            }
        } else {
            $x = explode("://vimeo.com/", $url, 2);
            if ($x[1]) {
                $a['vimeo_id'] = $x[1];
            } else {
                $x = explode("://rutube.ru/video/", $url, 2);
                if ($x[1]) {
                    $xx = explode("/", $x[1], 2);
                    $a['rutube_id'] = $xx[0];
                } else {
                    $x = explode("//www.screencast.com/", $url, 2);
                    if ($x[1]) {
                        $xx = explode(" src=\"", $url, 2);
                        $xxx = explode("\"", $xx[1], 2);
                        $a['screencast_url'] = $xxx[0];
                        $a['name'] = $xxx[0];
                    }
                }
            }
        }

        return $a;
    }

    function covtime($youtube_time)
    {
        $start = new DateTime('@0'); // Unix epoch
        $start->add(new DateInterval($youtube_time));
        return $start->getTimestamp();
    }

    function process_video($video_id)
    {
        global $emps;
        $ctx = $emps->p->get_context(DT_VIDEO, 1, $video_id);

        $video = $emps->db->get_row("e_videos", "id=$video_id");
        if ($video['youtube_id'] && defined('GOOGLE_KEY_YOUTUBE')) {

            $url = "https://www.googleapis.com/youtube/v3/videos?key=" . GOOGLE_KEY_YOUTUBE . "&part=snippet,contentDetails&id=" . $video['youtube_id'];
            //echo $url;
            $data = file_get_contents($url);
            //echo $data;
            $json = json_decode($data, true);

            $snip = $json['items'][0]['snippet'];
            $data = $json['items'][0]['contentDetails'];
			//dump($json['items'][0]);
			//dump($data);
            $SET['name'] = $snip['title'];
            $SET['description'] = $snip['description'];
            $SET['duration'] = $this->covtime($data['duration']);

            $emps->db->sql_update_row("e_videos", ['SET' => $SET], "id=$video_id");
            $emps->p->save_properties($SET, $ctx, P_VIDEO);


            $img = $snip['thumbnails']['maxres'];
            if (!$img) {
                $img = $snip['thumbnails']['standard'];
                if (!$img) {
                    $img = $snip['thumbnails']['high'];
                    if (!$img) {
                        $img = $snip['thumbnails']['medium'];
                        if (!$img) {
                            $img = $snip['thumbnails']['default'];
                        }
                    }
                }
            }

            if ($img) {
                $this->p->delete_photos_context($ctx);

                $ord = 10;

                $data = file_get_contents($img['url']);

                if ($data) {
                    $nr = [];
                    $nr['md5'] = md5(uniqid(time() + 1231111));
                    $nr['filename'] = "video.jpg";
                    $nr['type'] = 'image/jpeg';
                    $nr['size'] = strlen($data);
                    $nr['thumb'] = $img['width'] . 'x' . $img['height'] . "|120x90|auto,max";
                    $nr['context_id'] = $ctx;
                    $nr['ord'] = $ord + 10;
                    $emps->db->sql_insert_row("e_uploads", ['SET' => $nr]);
                    $file_id = $emps->db->last_insert();
                    $oname = $this->p->up->upload_filename($file_id, DT_IMAGE);

                    file_put_contents($oname, $data);

                    $row = $emps->db->get_row("e_uploads", "id=$file_id");
                    if ($row) {
                        $fname = $this->p->thumb_filename($file_id);
                        $this->p->treat_upload($oname, $fname, $row);
                    }
                }

            }

        }

        if ($video['vimeo_id']) {
            $raw = file_get_contents("http://vimeo.com/api/v2/video/" . $video['vimeo_id'] . ".json");
            $data = json_decode($raw, true);

            $data = $data[0];

            $SET = array();
            $SET['name'] = $data['title'];
            $SET['description'] = $data['description'];
            $SET['duration'] = $data['duration'];
            $SET['width'] = $data['width'];
            $SET['height'] = $data['height'];

            $emps->db->sql_update_row("e_videos", ['SET' => $SET], "id=$video_id");
            $emps->p->save_properties($SET, $ctx, P_VIDEO);

            $image = file_get_contents($data['thumbnail_large']);

            $x = explode("/", $data['thumbnail_large']);
            $name = $x[count($x) - 1];

            $nr = [];
            $nr['md5'] = md5(uniqid(time() + 1231111));
            $nr['filename'] = $name;
            $nr['type'] = 'image/jpeg';
            $nr['size'] = strlen($image);
            $nr['thumb'] = "1920x1080|120x90|inner";
            $nr['context_id'] = $ctx;
            $nr['ord'] = 10;
            $emps->db->sql_insert_row("e_uploads", ['SET' => $nr]);
            $file_id = $emps->db->last_insert();
            $oname = $this->p->up->upload_filename($file_id, DT_IMAGE);

            file_put_contents($oname, $image);

            $row = $emps->db->get_row("e_uploads", "id=$file_id");
            if ($row) {
                $fname = $this->p->thumb_filename($file_id);
                $this->p->treat_upload($oname, $fname, $row);
            }


//			dump($data);exit();
        }

        if ($video['rutube_id']) {
            $raw = file_get_contents("http://rutube.ru/api/video/" . $video['rutube_id'] . "/?format=json");
            $data = json_decode($raw, true);

//			dump($data);exit();

            $SET = array();
            $SET['name'] = $data['title'];
            $SET['description'] = $data['description'];
            $SET['duration'] = $data['duration'];
            $SET['embed_url'] = $data['embed_url'];

            $emps->db->sql_update_row("e_videos", ['SET' => $SET], "id=$video_id");
            $emps->p->save_properties($SET, $ctx, P_VIDEO);

            $image = file_get_contents($data['thumbnail_url']);

            $x = explode("/", $data['thumbnail_url']);
            $name = $x[count($x) - 1];

            $nr = array();
            $nr['md5'] = md5(uniqid(time() + 1231111));
            $nr['filename'] = $name;
            $nr['type'] = 'image/jpeg';
            $nr['size'] = strlen($image);
            $nr['thumb'] = "1920x1080|120x90|inner";
            $nr['context_id'] = $ctx;
            $nr['ord'] = 10;
            $emps->db->sql_insert_row("e_uploads", ['SET' => $nr]);
            $file_id = $emps->db->last_insert();
            $oname = $this->p->up->upload_filename($file_id, DT_IMAGE);

            file_put_contents($oname, $image);

            $row = $emps->db->get_row("e_uploads", "id=$file_id");
            if ($row) {
                $fname = $this->p->thumb_filename($file_id);
                $this->p->treat_upload($oname, $fname, $row);
            }


//			dump($data);exit();
        }

        $SET = $os;
    }

    function delete_video($id)
    {
        global $emps;
        $ictx = $emps->p->get_context(DT_VIDEO, 1, $id);

        $emps->p->delete_context($ictx);

        $emps->db->query("delete from " . TP . "e_videos where id=$id");
    }

    function delete_videos_context($context_id)
    {
        global $emps;

        $r = $emps->db->query("select * from " . TP . "e_videos where context_id = " . $context_id);
        while ($ra = $emps->db->fetch_named($r)) {
            $this->delete_video($ra['id']);
        }
    }

    function convert_duration($duration)
    {
        $h = (integer)floor($duration / 3600);
        $m = (integer)floor(($duration - $h * 3600) / 60);
        $s = $duration - $h * 3600 - $m * 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    function load_videos($context_id, $sel_id)
    {
        global $emps, $sd;
        $r = $emps->db->query("select * from " . TP . "e_videos where context_id = {$context_id} order by ord asc, id asc");
        $lst = array();
        $emps->loadvars();
        while ($ra = $emps->db->fetch_named($r)) {
            $ra = $emps->db->row_types("e_videos", $ra);
            $cctx = $emps->p->get_context(DT_VIDEO, 1, $ra['id']);

            $sd = 'videos.' . $ra['id'];
            $ra['vlink'] = $emps->elink();
            $ra['pic'] = $this->p->first_pic($cctx);

            if ($ra['id'] == $sel_id) {
                $ra['sel'] = true;
            }

            $lst[] = $ra;
        }
        return $lst;
    }

    function count_videos($context_id)
    {
        global $emps;

        $r = $emps->db->query("select count(*) from " . TP . "e_videos where context_id = " . $context_id);
        $ra = $emps->db->fetch_row($r);

        return $ra[0];
    }
}
