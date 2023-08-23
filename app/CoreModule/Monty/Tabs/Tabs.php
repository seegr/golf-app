<?php


namespace App\Components;

use Nette;
use Nette\Utils\Html;
use Exception;

use App\Components\Tabs\Tab;


class Tabs extends Nette\Application\UI\Control {

	public
		$presenter,
		$id,
		$class = [],
		$tabs = [];


	public function __construct() {
		return $this;
	}


	public function render() {
		$this->id = $this->getHtmlId();
		$this->presenter = $this->getPresenter();

		$template = clone $this->presenter->template;
		bdump($template, "template");
		// $template = $this->template;
		// bdump($template, "template");

		$template->setFile(__DIR__ . "/templates/tabs.latte");

		$template->id = $this->id;
		$template->tabs = $this->tabs;

		$template->render();
	}

	public function setPresenter($presenter) {
		$this->presenter = $presenter;

		return $this;
	}

	public function addTab($id, $text = null, $icon = null) {
		$tab = new Tab($this, $id, $text);
		$tab->setIcon($icon);

		$this->tabs[$id] = $tab;

		return $tab;
	}

	public function getHtmlId() {
		return "tabs-control-" . $this->lookupPath();
	}

	public function getItemsCount() {
		return count($this->tabs);
	}

}
