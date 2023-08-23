<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;

use Contributte\MenuControl\UI\IMenuComponentFactory;
use Contributte\MenuControl\UI\MenuComponent;


class FrontPresenter extends App\CoreModule\Presenters\BasePresenter
{

	public function startup(): void
	{
		parent::startup();
	}

	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$template = $this->template;

		$frontLayoutPath = __DIR__ . "/../../../FrontModule/templates/@layout.latte";
		// bdump($frontLayoutPath, "frontlayout");
		if (file_exists($frontLayoutPath)) {
			// bdump("front layout jojo", $frontLayoutPath);
			$this->setLayout($frontLayoutPath);

			// bdump($this->getLayout(), "current layout");
		}

		[, $presenter] = Nette\Application\Helpers::splitName($this->getName());
		$templateFile = self::FRONT_ROOT . "templates/$presenter/$this->view.latte";
		// bdump($templateFile, "lookin for templateFile");
		if (file_exists($templateFile)) {
			$template->setFile($templateFile);
		}

		$this->setHtmlMeta();
	}

}