<?php

namespace App\CoreModule\Model;

use Nette\Mail\SmtpMailer;
use Wavevision\DIServiceAnnotation\DIService;
use Nette\SmartObject;


class Mailer extends SmtpMailer
{
    use SmartObject;

    private array $config;

    public function __construct(array $config)
    {
        bdump($config);
        $this->$config = $config;
    }

}