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
	// 		\Tracy\Debugger::barDump("attaching");
	// 		$this->attached($presenter);
	// 		$this->presenter = $presenter;
	// 	}
	// 	\Tracy\Debugger::barDump($this, self::class);
	// }

	// protected function attached(Nette\ComponentModel\IComponent $obj): void
	// {
	// 	\Tracy\Debugger::barDump($obj, "obj");
	// 	$this->presenter = $obj;
	// 	// \Tracy\Debugger::barDump($this->getPresenter(), "presenter");
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
		#\Tracy\Debugger::barDump($templateFile, "templateFile");

		if ($this->renderToString) {
			#\Tracy\Debugger::barDump(1);
			// \Tracy\Debugger::barDump($template->getParameters(), "pars");
			$html = $template->getLatte()->renderToString($templateFile, $template->getParameters());
			#\Tracy\Debugger::barDump($html, "basecontrol html");
			// $this->renderToString();
			return $html;
		} else {
			#\Tracy\Debugger::barDump(2);
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