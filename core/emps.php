<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Usa o autoload do Composer

use Smarty\Smarty; // <-- IMPORTANTE! Smarty 5 usa namespace

class EMPS {
    public function __construct() {
        global $smarty;

        $smarty = new Smarty();  // Agora a classe correta é usada

        $smarty->setTemplateDir(__DIR__ . '/../modules/');
        $smarty->setCompileDir(__DIR__ . '/../templates_c/');
    }

    public function page_property($key, $value) {
        global $page_props;
        $page_props[$key] = $value;
    }
}
