<?php

namespace Monty;

use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;


class Modal extends BaseControl {

	public $title;
	public $buttonTitle;
	public $buttonCloseTitle;
	public $content;
	public $latteContent = false;
	public $show = false;


	public function render()
	{
		parent::render();
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/modal.latte");

		$template->title = $this->title;
		if ($this->latteContent) {
			$temp = $this->getPresenter()->getTemplate();
			$pars = $temp->getParameters();
			// \Tracy\Debugger::barDump($pars, "pars");
			$template->content = $this->getPresenter()->template->getLatte()->renderToString($this->content, $pars);
		} else {
			$template->content = $this->content;
		}
		$template->buttonCloseTitle = $this->buttonCloseTitle;

		if ($this->show) {
			$template->show = $this->show;
			$this->redrawControl();
		}

		$template->render();
	}

	public function renderButton()
	{
		parent::render();
		$template = $this->template;
		$template->setFile(__DIR__ . "/templates/button.latte");
		$template->title = $this->title;
		$template->buttonTitle = $this->buttonTitle;

		$template->render();
	}

	// public function renderButtonClose()
	// {
	// 	parent::render();
	// 	$template = $this->template;
	// 	$template->setFile(__DIR__ . "/templates/buttonClose.latte");
	// 	$template->buttonCloseTitle = $this->buttonCloseTitle;

	// 	$template->render();
	// }

	public function setContent($content) {
		// \Tracy\Debugger::barDump("modal setContent");
		$this->content = $content;

		if (strrpos($content, ".latte")) {
			$this->latteContent = true;
		}

		return $this;
	}

	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	public function setButtonTitle($title)
	{
		$this->buttonTitle = $title;

		return $this;
	}

	public function setButtonCloseTitle($title)
	{
		$this->buttonCloseTitle = $title;

		return $this;
	}

	public function show($state = true) {
		$this->show = $state;

		$this->reload();

		return $this;
	}

	public function reload()
	{
		$this->redrawControl();
	}

}