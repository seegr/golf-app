<?php

namespace App\model;

use Nette\Utils\ArrayHash;

class GolfConfig
{
    protected ArrayHash $config;

    public function __construct($config)
    {
        $this->config = ArrayHash::from($config);
    }

    public function get($key)
    {
        return !empty($this->config->$key) ? $this->config->$key : null;
    }

}