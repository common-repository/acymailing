<?php

include_once __DIR__.DIRECTORY_SEPARATOR.'field.php';

class JFormFieldHelp extends acym_JFormField
{
    var $type = 'help';

    public function getInput()
    {

        $config = acym_config();
        $level = $config->get('level');
        $link = ACYM_DOCUMENTATION;

        return '<a class="btn" target="_blank" href="'.$link.'">'.acym_translation('ACYM_HELP').'</a>';
    }
}
