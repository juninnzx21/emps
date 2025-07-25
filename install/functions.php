<?php

class EMPS_Installer {
    public function say($s) {
        echo $s.PHP_EOL;
    }

    public function modify_ini_file($file, $template) {
        $data = file_get_contents($file);
        if (!$data) {
            $this->say("Could not open: {$file}");
            return;
        }
        $x = explode(PHP_EOL, $data);

        $phpini_list = json_decode(file_get_contents($template), true);
        $file_dirty = false;
        foreach ($phpini_list as $line) {
            $dirty = $this->replace_ini_strings($x, $line['from'], $line['to']);
            if ($dirty) {
                $file_dirty = true;
            } else {
                $this->say("Not found: {$line['from']} in {$file}");
            }
        }
        if ($file_dirty) {
            $data = implode(PHP_EOL, $x);
            file_put_contents($file, $data);
            $this->say("New {$file} saved!");
        }
    }

    public function replace_ini_strings(&$x, $find, $replace) {

        $dirty = false;

        foreach ($x as $idx => $line) {
            $line = trim($line);
            if (trim($line) == '') {
                continue;
            }
            if (preg_match($find, $line) === 1) {
                $dirty = true;
                $x[$idx] = $replace;
            }
        }

        return $dirty;
    }

    public function paths_config_file($source_path, $dest_path, $rlst) {
        $data = file_get_contents($source_path);
        if (!$data) {
            $this->say("No data at {$source_path}!");
            return false;
        }
        if (count($rlst) > 0) {
            foreach ($rlst as $find => $replace) {
                $data = str_replace($find, $replace, $data);
            }
        }
        file_put_contents($dest_path, $data);
        $this->say("New {$dest_path} saved!");
    }

    public function nginx_config_file($path, $rlst = [], $altpath = "") {
        $source_path = __DIR__ . "/templates/nginx/{$path}";
        $dest_path = "/etc/nginx/{$path}";
        if (file_exists($dest_path)) {
            if ($altpath != "") {
                $dest_path = $altpath;
            }
            if (file_exists($dest_path)) {
                $this->say("File {$path} already exists!");
                return false;
            }
        }
        $this->paths_config_file($source_path, $dest_path, $rlst);
    }

    public function ensure_user($config) {
        $rc = shell_exec("grep -c '^{$config['main_user']}:' /etc/passwd");
        if(intval($rc) == 1){
            // user exists
            $this->say("Setting new password...");
            exec("echo {$config['main_user']}:{$config['user_password']} | chpasswd");
        }else{
            // user does not exist
            system("useradd -b /home/{$config['main_user']} -f -1 -G www-data,git -m -U {$config['main_user']}");
            sleep(1);

            $rc = shell_exec("grep -c '^{$config['main_user']}:' /etc/passwd");
            if(intval($rc) == 1){
                $this->say("Setting new password...");
                exec("echo {$config['main_user']}:{$config['user_password']} | chpasswd");
            }else{
                $this->say("Failed to create user!");
            }
        }
    }
}

$installer = new EMPS_Installer();