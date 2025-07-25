<?php

class EMPS_Blocks {
    public $editor_mode = false;

    public $debug = false;

    public function get_block($name) {
        global $emps;
        
        $id = intval($name);
        if ($id == $name) {
            $row = $emps->db->get_row("e_blocks", "id = {$id}");
        }
        if (!$row) {
            $e_name = $emps->db->sql_escape($name);
            $row = $emps->db->get_row("e_blocks", "code = '{$e_name}'");
        }
        if (!$row) {
            return false;
        }
        return $row;
    }

    public function get_block_params($dom) {
        $plst = [];

        foreach ($dom->params->param as $param) {
            $pa = [];
            foreach ($param->attributes() as $n => $v) {
                $pa[strval($n)] = strval($v);
            }
            if (isset($param->default)) {
                $pa['default'] = $param->default->children()->asXML();
            }

            if ($pa['name']) {
                $spa = $pa;
                unset($spa['name']);
                $plst[$pa['name']] = $spa;
            }
        }
        return $plst;
    }

    public function convert_xml_for_static(&$el, $idx = 1) {
        if (is_null($el)) {
            return "";
        }
        $text = "";
        $name = strtolower($el->getName());

        $plst = [];
        foreach ($el->attributes() as $n => $v) {
            $plst[strval($n)] = strval($v);
        }

        if ($name == 'html') {
            return '<article ' . "id=\"{{"."$"."element_id}}\"" . '>{{eval var=$' . $plst['param'] . '}}</article>';
        } elseif ($name == 'value') {
            return '{{$' . $plst['param'] . '}}';
        } elseif ($name == 't') {
            return '<article ' . "id=\"{{"."$"."element_id}}\"". '>' . $el->__toString() . '</article>' . PHP_EOL;
        } elseif ($name == 'array') {
            $param = $plst['param'];
            $rv = '';
//            $rv .= '{{$'.$param.'|var_dump}}'.PHP_EOL;
            $rv .= '{{foreach from=$' . $param . ' item="row" key="i" name="a"}}'.PHP_EOL;
            $rv .= '{{if $row|@is_array}}{{if $row.type == "ref"}}'.PHP_EOL;
            //$rv .= '{{"sblk:`$row.value`"}}'.PHP_EOL;
            $rv .= '{{$block_id = $row.value}}'.PHP_EOL;
            $rv .= '{{if !$avoid_block.$block_id}}'.PHP_EOL;
            $rv .= '{{$avoid_block.$block_id = true}}';
            $rv .= '{{include file="sblk:`$row.value`" vars=[]}}'.PHP_EOL;
            $rv .= '{{else}}Recursive block {{$block_id}}!'.PHP_EOL;
            $rv .= '{{/if}}'.PHP_EOL;
            $rv .= '{{/if}}'.PHP_EOL;
            $rv .= '{{if $row.type == "raw"}}'.PHP_EOL;
//            $rv .= "SBLT!".PHP_EOL;
//            $rv .= '{{$row|var_dump}}'.PHP_EOL;
            $rv .= '{{if !$row.template}}{{$row.template = "blocks/plain"}}{{/if}}'.PHP_EOL;
            $rv .= '{{include element_id="`$element_id`_`$i+1`" file="sblt:`$row.template`" vars=$row.value}}'.PHP_EOL;
            $rv .= '{{/if}}{{/if}}'.PHP_EOL;
            $rv .= '{{/foreach}}';
            return $rv;
        } else {
            $sidx = 1;
            foreach ($el->children() as $n => $v) {
                $text .= $this->convert_xml_for_static($v, $sidx);
                $sidx++;
            }

            $addtag = "";
            $params = [];
            foreach ($plst as $pn => $pv) {
                if ($pn == "add-class") {
                    $plst["class"] .= " {{\$" . $pv . "}}";
                }
                if ($pn == "add-tag") {
                    $addtag = "{{\$" . $pv . "}}";
                }
            }
            foreach ($plst as $pn => $pv) {
                if ($pn == "add-class") {
                    continue;
                }
                if ($pn == "add-tag") {
                    continue;
                }
                $pv = str_replace("[", "{{"."$", str_replace("]", "}}", $pv));
                $params[] = $pn . "=\"" . $pv ."\"";
            }
            $params[] = "id=\"{{"."$"."element_id}}\"";
            $params = implode(" ", $params);

            $raw = trim($el->__toString());
            if ($raw) {
                $raw .= PHP_EOL;
            }

            if ($name != 'block') {
                $text = '<'.$name.$addtag.' '.$params.'>'.PHP_EOL.$text.PHP_EOL.$raw.'</'.$name.$addtag.'>'.PHP_EOL;
            }
        }
        return $text;
    }

    public function render_block_template_static($template) {
        global $smarty;

        $temp = $smarty->fetch("db:" . $template);
        if (!$temp) {
            return ['html' => ''];
        }

        $dom = new SimpleXMLElement($temp);

        $params = $this->get_block_params($dom);

        foreach ($params as $param) {
            $smarty->assign($param['name'], $param['default']);
        }

        $text = $this->convert_xml_for_static($dom->block);

        $debug = "";
        if ($this->debug) {
            $debug = '{{$var.name}}: {{$var.value|json_encode}}';
        }

//        echo $text; exit;
        $text = '
{{foreach from=$vars item="var"}}
{{if $var.vtype[0] == "a"}}
{{if $var.value != "" && $var.value}}
{{assign var=$var.name value=$var.value|json_decode:true}}
{{else}}
{{assign var=$var.name value=[]}}
{{/if}}
{{elseif $var.type == "h" || $var.type == "t"}}
{{assign var="evaldata" value=$var.value|emps:smartybr}}
{{eval assign=$var.name var=$evaldata}}
{{elseif $var.type == "photo"}}
{{assign var=$var.name value=$var.value|load_pic}}
{{else}}
{{assign var=$var.name value=$var.value}}
{{/if}}
'.$debug.
'
{{/foreach}}
'.$text;

        return ['html' => $text];
    }

    public function list_template_params($template) {
        global $smarty, $emps;

        $temp = $smarty->fetch("db:" . $template);
        if (!$temp) {
            return false;
        }

        $emps->save_setting("last_temp", $temp);
        $dom = new SimpleXMLElement($temp);

        $params = $this->get_block_params($dom);
        if (isset($dom->title)) {
            //var_dump((string)$dom->title);
            $params['template_title'] = (string)$dom->title;
        }
        //var_dump($params); exit;

        return $params;
    }

    public function list_block_param_values($block_id) {
        global $emps;

        $lang = $emps->lang;
        $r = $emps->db->query("select * from ".TP."e_block_param_values where block_id = {$block_id}
            and lang = '{$lang}' 
            order by ord asc");

        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
            $lst[] = $ra;
        }
        return $lst;
    }

    public function render_block_static($row) {
        $lst = $this->list_block_param_values($row['id']);

        $params = $this->list_template_params($row['template']);

        unset($params['template_title']);
        $names = [];
        foreach ($lst as $v) {
            $names[] = $v['name'];
        }

        $dlst = [];

        foreach ($params as $name => $value) {
            if (!in_array($name, $names)) {
                $dlst[] = ['name' => $name, 'v_char' => $value['default'], 'vtype' => $value['type']];
            } else {
                foreach ($lst as $v) {
                    if ($v['name'] == $name) {
                        $dlst[] = $v;
                    }
                }
            }
        }

        //var_dump($dlst); exit;
        $text = "";
        foreach ($dlst as $v) {
            if ($v['vtype'] == 'i') {
                $text .= '{{$' . $v['name'] . '=' . $v['v_int'] . '}}' . PHP_EOL;
            } elseif ($v['vtype'] == 'f') {
                $text .= '{{$' . $v['name'] . '=' . $v['v_float'] . '}}' . PHP_EOL;
            } elseif ($v['vtype'] == 'photo') {
                $text .= '{{$' . $v['name'] . '=' . $v['v_int'] . '|load_pic}}{{$'.$v['name']."|json_encode}}" . PHP_EOL;
            } elseif (substr($v['vtype'], 0, 1) == 'a') {
/*                $text .= '{{$' . $v['name'] . '="' . str_replace("\$", "\\\$", addslashes($v['v_json'])) . '"|json_decode:true}}' . PHP_EOL;
                if ($this->debug) {
                    echo json_encode(json_decode($v['v_json'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }*/
                $text .= '{{$' . $v['name'] . '=' . $v['id'] . '|blockparam:v_json}}' . PHP_EOL;
            } else {
                $text .= '{{capture assign="' . $v['name'] . '"}}' . PHP_EOL;
                if ($v['vtype'] == 't' || $v['vtype'] == 'h') {
                    $text .= $v['v_text'];
                } else {
                    $text .= $v['v_char'];
                }
                $text .= '{{/capture}}' . PHP_EOL;
                echo $text; exit;
            }
        }

        $text .= '{{include element_id="el" vars=[] file="sblt:' . $row['template'] . '"}}' . PHP_EOL;

        return ['html' => $text];
    }

    public function save_param_value($block_id, $param, $lang, $idx, $ord) {
        global $emps;

        /*
         * `id` bigint NOT NULL AUTO_INCREMENT,
  `block_id` bigint NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `lang` varchar(2) NOT NULL DEFAULT 'nn',
  `vtype` char(2)  NOT NULL DEFAULT 'c',
  `idx` int NOT NULL DEFAULT '0',
  `v_char` varchar(255) NOT NULL DEFAULT '',
  `v_text` mediumtext NOT NULL DEFAULT '',
  `v_int` bigint DEFAULT NULL,
  `v_float` float DEFAULT NULL,
  `cdt` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
         */
        $nr = [];
        $nr['block_id'] = $block_id;
        $nr['name'] = $param['name'];
        $nr['lang'] = $lang;
        $qr = $nr;
        $nr['vtype'] = $param['type'];
        $nr['idx'] = $idx;
        $nr['ord'] = $ord;
        if ($param['type'] == 'c') {
            $nr['v_char'] = $param['value'];
        }
        if ($param['type'] == 'photo') {
            $nr['v_int'] = $param['value'];
        }
        if ($param['type'] == 't' || $param['type'] == 'h') {
            $nr['v_text'] = $param['value'];
        } elseif (substr($param['type'], 0, 1) == 'a') {
            $nr['v_json'] = json_encode($param['value'], JSON_UNESCAPED_UNICODE);
        }
        $row = $emps->db->sql_ensure_row("e_block_param_values", $qr);
        if ($row) {
            $emps->db->sql_update_row("e_block_param_values", ['SET' => $nr], "id = {$row['id']}");
        }

    }

    public function get_template_title($template) {
        return $template;
    }

    public function check_expanded(&$array) {
        foreach ($array as &$v) {
            if (!isset($v['expanded']) && is_array($v['value'])) {
                $v['expanded'] = false;
            }
            if ($v['template']) {
                $params = $this->list_template_params($v['template']);
                $v['template_title'] = $params['template_title'];
            }
            if (is_array($v['value'])) {
                $this->check_expanded($v['value']);
            } else {
                unset($v['expanded']);
            }
        }
    }
}