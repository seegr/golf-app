<?php

namespace Monty;

use Nette;
use Nette\Utils\Strings;


class BaseControl extends Nette\Application\UI\Control {

	protected $presenter;
	protected $id;
	protected $lookupPath;
	protected $renderToString;


	// public function __construct($presenter = null)
	// {
	// 	if ($presenter) {
	// 		bdump("attaching");
	// 		$this->attached($presenter);
	// 		$this->presenter = $presenter;
	// 	}
	// 	bdump($this, self::class);
	// }

	// protected function attached(Nette\ComponentModel\IComponent $obj): void
	// {
	// 	bdump($obj, "obj");
	// 	$this->presenter = $obj;
	// 	// bdump($this->getPresenter(), "presenter");
	// }

	public function render() {
		$template = $this->template;

		$this->id = $template->id = Strings::webalize($this->getParent()->getName()) . "-" . $this->lookupPath();
		$this->lookupPath = $template->lookupPath = $this->lookupPath();
	}

	public function renderScripts()
	{
		$template = $this->template;
		$rc = new \ReflectionClass(get_class($this));
		$file = dirname($rc->getFileName()) . "./templates/scripts.latte";
		if (file_exists($file)) {
			$template->setFile($file);
			$template->render();
		}

	}


	public function setRenderToString($state = true) {
		$this->renderToString = true;

		return $this;
	}

	public function renderIt($template) {
		$templateFile = $template->getFile();
		#bdump($templateFile, "templateFile");

		if ($this->renderToString) {
			#bdump(1);
			// bdump($template->getParameters(), "pars");
			$html = $template->getLatte()->renderToString($templateFile, $template->getParameters());
			#bdump($html, "basecontrol html");
			// $this->renderToString();
			return $html;
		} else {
			#bdump(2);
			$template->render();
		}
	}

	public function getId() {
		return Strings::webalize($this->getParent()->getName()) . "-" . $this->lookupPath();
	}

	public function getLookupPath() {
		return $this->lookupPath();
	}
}