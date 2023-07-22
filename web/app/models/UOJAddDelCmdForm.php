<?php

class UOJAddDelCmdForm extends UOJForm {

    public function __construct($form_name, $validate, $handle, $final = null) {
        parent::__construct($form_name);

        $this->addTextArea(
            $form_name . '_cmds', '命令', '',
            function($str, &$vdata) use($validate) {
                $cmds = array();
                foreach (explode("\n", $str) as $line_id => $raw_line) {
                    $line = trim($raw_line);
                    if ($line == '') {
                        continue;
                    }
                    if ($line[0] != '+' && $line[0] != '-') {
                        return '第' . ($line_id + 1) . '行：格式错误';
                    }
                    $obj = trim(substr($line, 1));
                    
                    if ($err = $validate($obj, $vdata)) {
                        return '第' . ($line_id + 1) . '行：' . $err;
                    }
                    $cmds[] = array('type' => $line[0], 'obj' => $obj);
                }
                $vdata['cmds'] = $cmds;
                return '';
            },
            null
        );
        if (!isset($final)) {
            $this->handle = function(&$vdata) use($handle) {
                foreach ($vdata['cmds'] as $cmd) {
                    $handle($cmd['type'], $cmd['obj'], $vdata);
                }
            };
        } else {
            $this->handle = function(&$vdata) use($handle, $final) {
                foreach ($vdata['cmds'] as $cmd) {
                    $handle($cmd['type'], $cmd['obj'], $vdata);
                }
                $final();
            };
        }
    }
}