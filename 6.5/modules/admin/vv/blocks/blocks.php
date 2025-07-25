<?php

if ($emps->auth->credentials("admin")) {
    $emps->page_property("vuejs", 1);
    $emps->page_property("fluid", 1);
    $emps->page_property("vue_debug", 1);

    require_once $emps->common_module('vted/vted.class.php');
    require_once $emps->common_module('videos/videos.class.php');

    class EMPS_ContentBlocks extends EMPS_VueTableEditor
    {
        public $ref_type = DT_CONTENT_BLOCK;
        public $ref_sub = 1;

        public $track_props = P_CONTENT_BLOCK;

        public $table_name = "e_blocks";

        public $credentials = "admin";

        public $order = " order by id desc ";

        public $v;

        public $multilevel = false;

        public $pads = ['info', 'values', 'html', 'photos', 'files'];
        public $pad_names = "db:_admin/vv/blocks,pad_names";

        public $debug = false;

        public $props_by_ref = false;

        public function __construct()
        {
            parent::__construct();
        }

        public function explain_row($row){
            global $emps;

            $row = parent::explain_row($row);

            $row['own_context_id'] = $emps->p->get_context($this->ref_type, $this->ref_sub, $row['id']);

            return $row;
        }

        public function pre_save($nr) {
            global $emps;

            return $nr;
        }
    }

    $vted = new EMPS_ContentBlocks();

    require_once $emps->page_file_name("_admin/vv/blocks,common", "controller");

    $perpage = 25;

    $vted->handle_request();

} else {
    $emps->deny_access("AdminNeeded");
}
