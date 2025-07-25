<?php

// core includes
require_once EMPS_COMMON_PATH_PREFIX . "/core/proc.php";
if (defined('EMPS_POSTGRES') && EMPS_POSTGRES) {
    require_once EMPS_PATH_PREFIX . "/core/postgresdb.class.php";
} else {
    require_once EMPS_PATH_PREFIX . "/core/db.class.php";
}
require_once EMPS_PATH_PREFIX . "/core/properties.class.php";

require_once EMPS_PATH_PREFIX . "/core/session_handler_sql.class.php";

$emps_session_handler = new EMPS_SessionHandler();
session_set_save_handler($emps_session_handler, true);

if (!$emps->fast) {
    require_once EMPS_PATH_PREFIX . "/core/auth.class.php";
    require_once EMPS_COMMON_PATH_PREFIX . "/core/smarty.php";
    require_once EMPS_PATH_PREFIX . "/core/blocks.class.php";
    require_once EMPS_PATH_PREFIX . "/core/content.class.php";
}


