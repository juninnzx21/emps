<?php

// The session handler based on $emps->db (SQL abstraction interface)

class EMPS_SessionHandler implements SessionHandlerInterface
{
    public $last_id;

    public $last_data;

    public $last_result;

    public function open($savePath, $sessionName)
    {
        // just open... we're a database-driven session handler

        return true;
    }

    public function close()
    {
        // just close

        return true;
    }

    public function read($id)
    {
        global $emps;

        if (!trim($id)) {
            return false;
        }

        $this->last_id = $id;

        $rv = "";

        $r = $emps->db->query("select * from " . TP . "e_php_sessions where sess_id = '" . $emps->db->sql_escape($id) . "'");
        $ra = $emps->db->fetch_named($r);
        if (!$ra) {
            $nr = [];
            $nr['sess_id'] = $id;
            $nr['ip'] = $_SERVER['REMOTE_ADDR'];
            $nr['browser_id'] = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
            $emps->db->sql_insert_row("e_php_sessions", ['SET' => $nr]);
        } else {
            if ($ra['dt'] < (time() - 10 * 60)) {
                $browser_id = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
                $browser = "";
                if ($browser_id != $ra['browser_id']) {
                    $browser = ", browser_id = " . $browser_id . " ";
                }
                $emps->db->query("update " . TP . "e_php_sessions set dt = " . time() . $browser . " where id = " . $ra['id']);
            }
            $this->last_result = $ra;
            $rv = $ra['data'];
        }

        $this->last_data = $rv;

        return $rv;
    }

    public function write($id, $data)
    {
        global $emps;

        if (!trim($id)) {
            return false;
        }

        $update = true;
        if ($data == $this->last_data) {
            $update = false;
        }

        if ($update) {
            $nr = [];
            $nr['data'] = $data;
            $nr['browser_id'] = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
            $nr['ip'] = $_SERVER['REMOTE_ADDR'];

            $row = $emps->db->get_row("e_php_sessions", "sess_id = '" . $emps->db->sql_escape($id) . "'");
            if ($row) {
                $emps->db->sql_update_row("e_php_sessions", ['SET' => $nr], "id = " . $row['id']);
            } else {
                $nr['sess_id'] = $id;
                $emps->db->sql_insert_row("e_php_sessions", ['SET' => $nr]);
            }
        }

        return true;
    }

    public function destroy($id)
    {
        global $emps;

        $emps->db->query("delete from " . TP . "e_php_sessions where sess_id = '" . $emps->db->sql_escape($id) . "'");

        return true;
    }

    public function gc($maxlifetime)
    {
        // Garbage collection will be done by a service script,
        // so that clients never have to wait for it.

        return true;
    }
}

