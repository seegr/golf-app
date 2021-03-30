<?php

declare(strict_types=1);

namespace App\CoreModule\AdminModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;

use Contributte\MenuControl\UI\IMenuComponentFactory;
use Contributte\MenuControl\UI\MenuComponent;


class AdminPresenter extends App\CoreModule\Presenters\BasePresenter
{

	public function startup(): void
    {
        parent::startup();
    }

    public function beforeRender(): void
    {
        parent::beforeRender();
        $this->setLayout($this->_adminLayout);
    }

}