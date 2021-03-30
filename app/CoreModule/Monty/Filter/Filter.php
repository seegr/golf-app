<?php

namespace Monty;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;


class Filter extends \Nette\Application\UI\Control { 

	public $onSubmit;

	/** @persistent */
	public $pars = [];


	public function render() {
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/filter.latte");

		$template->render();
	}

	public function createComponentFilter() {
		$form = new Form;

		$form->setMethod("get");
		$form->addText("text", "Text");
		$form->addSubmit("submit", "Hledat");

		$form->onSuccess = $this->onSubmit;

		$form->onSuccess[] = function($f, $v) {
			$this->pars = $f->getValues(true);
		};

		return $form;
	}

	public function addOrder($vals) {
		$form = $this["filter"];

		$form->addSelect("order", "Seřadit podle", [null => "- Seřadit podle -"] + $vals);

		return $this;
	}

	public function getPars() {
		return ArrayHash::from($this->pars);
	}

	public function getFilter() {
		return $this->getPars();
	}

	public function setDefaults($vals) {
		$this["filter"]->setDefaults($vals);

		return $this;
	}

}