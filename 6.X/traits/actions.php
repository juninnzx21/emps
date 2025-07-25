<?php

trait EMPS_Common_Actions
{
    private $installed_actions = [];

    public function add_action($name, $callback) {
        if (!isset($this->installed_actions[$name])) {
            $this->installed_actions[$name] = [];
        }
        $this->installed_actions[$name][] = $callback;
    }
    public function do_action($name, $payload) {
        if (isset($this->installed_actions[$name])) {
            foreach ($this->installed_actions[$name] as $callback) {
                $payload = call_user_func($callback, $payload);
            }
        }
        return $payload;
    }

}