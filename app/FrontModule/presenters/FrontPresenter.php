<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use Nette;


class FrontPresenter extends \App\CoreModule\FrontModule\Presenters\FrontPresenter
{

	public function beforeRender(): void
	{
		parent::beforeRender();
	}

}
