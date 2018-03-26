<?php

define('_PS_ROOT_DIR_', dirname(__DIR__));

require_once 'cwstatspayments.php';

class ModuleGrid
{
    public function __construct()
    {
    }

    public function l($text)
    {
        return $text;
    }

    public function install()
    {
        return true;
    }
}
