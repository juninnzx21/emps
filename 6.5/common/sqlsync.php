<?php

header("Content-Type: text/plain; charset=utf-8");

$emps->no_smarty = true;

$emps->no_time_limit();

function collect_indices($r)
{
    global $emps;

    $lst = array();
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[] = $ra;
    }

    return $lst;
}

function collect_columns($r)
{
    global $emps;

    $lst = array();
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[$ra['Field']] = $ra;
    }

    return $lst;
}

function sync_indices($dest_table, $src_table, $didx, $sidx)
{
    global $emps;

    foreach ($sidx as $sc) {
        reset($didx);
        $exists = false;
        foreach ($didx as $dc) {
            if ($dc['Table'] == $dest_table && $sc['Table'] == $src_table) {
                if ($dc['Column_name'] == $sc['Column_name']) {
                    if (($dc['Key_name'] == $sc['Key_name']) && ($dc['Non_unique'] == $sc['Non_unique'])) {
                        $exists = true;
                    }
                }
            }
        }
        if (!$exists) {
            $column = $sc['Column_name'];
            $index = $sc['Key_name'];

            echo $column . ": create index\r\n";
            if ($sc['Key_name'] == 'PRIMARY') {
                $emps->db->query("alter table `$dest_table` add primary key (`$column`)");
            } elseif ($sc['Index_type'] == 'FULLTEXT') {
                $emps->db->query("alter table `$dest_table` add fulltext key `$index` (`$column`)");
            } else {
                $emps->db->query("alter table `$dest_table` add key `$index` (`$column`)");
            }

        }
    }
}

function get_auto($column)
{
    if ($column['Extra'] == 'auto_increment') {
        return "auto_increment";
    }
    return '';
}

function get_not_null($column)
{
    if ($column['Null'] == 'YES') {
        return "null";
    }
    return "not null";
}

function get_default($sc, $di) {
    global $emps;

    if ($sc['Default'] != $di['Default']) {
        if (!$sc['Default'] && !$di['Default']) {
            return "";
        }
        if ($sc['Default'] == NULL && $di['Default'] == "0") {
            return "";
        }
        if ((intval($sc['Default']) == $sc['Default']) && (intval($sc['Default']) != 0)) {
            return "default " . $sc['Default'];
        } else {
            return "default '" . $emps->db->sql_escape($sc['Default']) . "'";
        }

    }
    return "";
}

function sync_structure($dest_table, $src_table, $dest, $src)
{
    global $emps;

    $need_conversion = false;
    foreach ($src as $sc) {
        $field = $sc['Field'];
        if ($dest[$field]) {
            // field exists

            $di = $dest[$field];

            if ($di['Type'] != $sc['Type']) {

                echo $field . ": type change: " . $di['Type'] . " => " . $sc['Type'] . "\r\n";
                $null = get_not_null($sc);
                $auto = get_auto($sc);
                $default = get_default($sc, $di);
                $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $null . " " .$default . " ". $auto;
                $emps->db->query($query);
                echo $query."\r\n";
                $di['Default'] = $sc['Default'];
            } else {
                if ($di['Null'] != $sc['Null']) {
                    echo $field . ": null change: " . $di['Null'] . " => " . $sc['Null'] . "\r\n";
                    $null = get_not_null($sc);
                    $auto = get_auto($sc);
                    $default = get_default($sc, $di);
                    $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $null . " " .$default ." " . $auto;
                    $emps->db->query($query);
                    echo $query."\r\n";
                    $di['Default'] = $sc['Default'];
                } else {
                    if (($di['Extra'] != $sc['Extra']) && ($sc['Extra'] != "NULL")) {
                        echo $field . ": extra change: " . $di['Extra'] . " => " . $sc['Extra'] . "\r\n";
                        $auto = get_auto($sc);
                        $default = get_default($sc, $di);
                        if ($auto) {
                            $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $default . " " . $auto;
                            $emps->db->query($query);
                            echo $query."\r\n";
                            $di['Default'] = $sc['Default'];
                        }
                    } else {
                        if (($di['Collation'] != $sc['Collation']) && isset($sc['Collation'])) {
                            echo $field . ": charset and collation: " . $di['Collation'] . " => " . $sc['Collation'] . "\r\n";
                            $collation = $sc['Collation'];
                            $x = explode("_", $collation, 2);
                            $charset = $x[0];

                            $need_conversion = true;
                            $conversion_query = "alter table `$dest_table` convert to character set {$charset} collate {$collation}";
                            $di['Collation'] = $sc['Collation'];

                        }
                    }
                }
            }

            if ($di['Default'] != $sc['Default'] && !($sc['Default'] == NULL && $di['Default'] == "0")) {
                echo $field . ": default change: '" . $di['Default'] . "' => '" . $sc['Default'] . "'\r\n";
                if ($sc['Default'] == 'NULL') {
                    $query = "alter table `$dest_table` alter column `$field` drop default ";
                } else {
                    $query = "alter table `$dest_table` alter column `$field` set default '" . $emps->db->sql_escape($sc['Default']) . "'";
                }
                $emps->db->query($query);
                echo $query."\r\n";
            }
        } else {
            // add field
            echo $field . ": add field\r\n";
            $null = get_not_null($sc);
            $auto = get_auto($sc);
            $default = get_default($sc, []);
            $query = "alter table `$dest_table` add column `$field` " . $sc['Type'] . " " . $null . " " . $default . " " . $auto;
            $emps->db->query($query);
            echo $query."\r\n";
        }
    }

    if ($need_conversion) {
        $emps->db->query($conversion_query);
        echo $conversion_query."\r\n";
    }

}

if ($emps->auth->credentials("admin") || $emps->is_empty_database() || true) {
    if ($key) {
        $name = "_" . $key . "/sql,module.sql";
        $file_name = $emps->page_file_name($name, 'inc');
        $sql = file_get_contents($file_name);
    } else {
        if ($_GET['sql']) {
            $sql = file_get_contents($emps->page_file_name($_GET['sql'], 'inc'));
        } else {
            $sql = file_get_contents($emps->common_module('config/sql/emps.sql'));
            $fn = $emps->common_module('config/sql/project.sql');
            if ($fn) {
                $sql .= file_get_contents($fn);
            }
            $fn = $emps->common_module('config/sqlsync.txt');
            if ($fn) {
                $list = file_get_contents($fn);
                $x = explode(",", $list);
                foreach ($x as $v) {
                    $v = trim($v);
                    $name = "_" . $v . "/sql,module.sql";
                    $file_name = $emps->page_file_name($name, 'inc');
                    if (file_exists($file_name)) {
                        $sub_sql = file_get_contents($file_name);
                        $sql .= $sub_sql;
                    }
                }
            }
        }
    }

    $x = explode("-- table", $sql);
    foreach ($x as $code) {
        if (!stristr($code, "create temporary table")) {
            continue;
        }
        $fx = explode("`", $code, 2);
        $tn = explode("`", $fx[1], 2);
        $table_name = trim($tn[0]);
        $fx = explode("temp_", $table_name, 2);

        $rt_name = TP . $fx[1];
        echo "Table `$table_name` (`$rt_name`):\r\n";

        $r = $emps->db->query("show tables like '$rt_name'");
        $ra = $emps->db->fetch_named($r);
        if ($ra) {
            // table exists, synchronize
            $emps->db->query($code);
            echo $emps->db->sql_error();

            $query = "show full columns from `$table_name`";
            $r = $emps->db->query($query);
            $lst_t = collect_columns($r);

            $query = "show full columns from `$rt_name`";
            $r = $emps->db->query($query);
            $lst_r = collect_columns($r);

            $query = "show index from `$table_name`";
            $r = $emps->db->query($query);
            $idx_t = collect_indices($r);

            $query = "show index from `$rt_name`";
            $r = $emps->db->query($query);
            $idx_r = collect_indices($r);

            sync_structure($rt_name, $table_name, $lst_r, $lst_t);
            sync_indices($rt_name, $table_name, $idx_r, $idx_t);

        } else {
            // table does not exist
            // simply cook the $code and execute

            $code = str_replace($table_name, $rt_name, $code);
            $code = str_ireplace("temporary", "", $code);
            $emps->db->query($code);
//			echo $code;
            $emps->db->sql_error();
            echo "CREATED the table.\r\n";
        }
    }
}

