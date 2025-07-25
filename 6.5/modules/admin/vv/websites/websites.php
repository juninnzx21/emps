<?php

if ($emps->auth->credentials("root")) {
    $emps->page_property("vuejs", 1);

    require_once $emps->common_module('vted/vted.class.php');
    require_once $emps->common_module('videos/videos.class.php');

    class EMPS_WebsitesEditor extends EMPS_VueTableEditor
    {
        public $ref_type = DT_WEBSITE;
        public $ref_sub = 1;

        public $track_props = P_WEBSITE;

        public $table_name = "e_websites";

        public $credentials = "root";

        public $order = " order by id desc ";

        public $form_name = "db:_admin/vv/websites,form";

        public $v;

        public $multilevel = false;

        public $pads = ['info', 'photos'];

        public $debug = false;

        public $props_by_ref = false;

        public function __construct()
        {
            parent::__construct();
        }

        public function explain_row($row){
            global $emps;

            $row = parent::explain_row($row);

            return $row;
        }

        public function pre_save($nr) {
            global $emps;

            return $nr;
        }

        public function post_save($nr) {
            global $emps;

        }
    }

    $vted = new EMPS_WebsitesEditor;

    require_once $emps->page_file_name("_admin/vv/websites,common", "controller");

    $perpage = 25;

    $vted->handle_request();

} else {
    $emps->deny_access("AdminNeeded");
}
