<?php

use Joomla\CMS\Form\FormField;

if (!class_exists('acym_JFormField')) {
    if ('WordPress' === 'Joomla') {
        class acym_JFormField extends FormField
        {
        }
    } else {
        class acym_JFormField
        {
        }
    }
}
