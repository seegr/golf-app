<?php

namespace Monty;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\ArrayHash;
use Exception;

use Monty\Tree;


class Tree extends Nette\Application\UI\Control {

	protected $data;
	protected $editable = false;
	protected $expanded = true;
	protected $checkable = false;
	protected $checked = [];
	protected $editRoute, $deleteAction, $sortableAction;
	public $editActionAjax;
	public $editAttrs;
	public $onOrderChange = [];
	public $sortable;

	public $onGetChecked = [];
	public $checkedButtonLabel;


	public function __construct() {
		return $this;
	}


	public function render() {
		$template = $this->template;

		$template->id = Strings::webalize($this->getParent()->getName()) . "-" . $this->lookupPath();
		$template->lookupPath = $this->lookupPath();

		$template->data = $this->data;

		$template->editable = $this->editable;
		$template->expanded = $this->expanded;
		$template->checkable = $this->checkable;
		$template->checked = $this->checked;
		$template->sortable = $this->sortable || count($this->onOrderChange) ? true : false;

		$template->setFile(__DIR__ . "/templates/tree.latte");

		$template->render();
	}


	public function setData($data) {
		#\Tracy\Debugger::barDump("setData");
		$this->data = $data;

		// \Tracy\Debugger::barDump($this->data, "data");

		// $items = [];
		// foreach ($this->data as $item) {
		// 	$items[] = $item->id;
		// }

		// $this["checkedForm"]["items"]->setItems($items);

		return $this;
	}

	public function setEditable($state = true) {
		$this->editable = $state;

		return $this;
	}

	public function setExpanded($state = true) {
		$this->expanded = $state;

		return $this;
	}

	public function setCheckable($state = true) {
		$this->checkable = $state;

		return $this;
	}

	public function setChecked(array $checked) {
		// \Tracy\Debugger::barDump($checked, "checked");
		$this->checked = $checked;

		return $this;
	}

	public function setCheckedButtonLabel($label) {
		$this->checkedButtonLabel = $label;

		return $this;
	}

	public function setEditAction($route, $attrs = [], $ajax = false) {
		$this->setEditable();
		$this->editAttrs = $attrs;
		$this->editRoute = $route;
		$this->editActionAjax = $ajax;

		return $this;
	}

	public function addEditAttributes(array $attrs) {
		$this->editAttrs = $attrs;

		return $this;
	}

	public function setDeleteAction($action) {
		$this->setEditable();
		$this->deleteAction = $action;

		return $this;
	}

	public function setSortable() {
		$this->sortable = true;

		return $this;
	}

	public function setSortableAction($action) {

	}

	public function isEditable() {
		return $this->editRoute ? true : false;
	}

	public function isDeletable() {
		return $this->deleteAction ? true : false;
	}

	public function getEditLink(array $attrs) {
		#\Tracy\Debugger::barDump($attrs, "attrs");
		$presenter = $this->getPresenter();

		$attrs = [
			"id" => !empty($attrs["itemId"]) ? $attrs["itemId"] : null,
			"parentId" => !empty($attrs["parentId"]) ? $attrs["parentId"] : null
		];

		if ($this->editAttrs) {
			$attrs = $attrs + $this->editAttrs;
		}

		return $presenter->link($this->editRoute, $attrs);
	}

	public function getDeleteLink($itemId) {
		$presenter = $this->getPresenter();

		return $presenter->link($this->deleteAction, $itemId);
	}


	// public function createComponentCheckedForm() {
	// 	\Tracy\Debugger::barDump("createComponentCheckedForm");
	// 	$form = new Form;

	// 	$form->addCheckboxList("items");

	// 	return $form;
	// }


	public function handleGetChecked(array $items) {
		#\Tracy\Debugger::barDump($items, "items");

		foreach ($this->onGetChecked as $callback) {
			$callback($items);
		}
	}

	public function handleOrderChange($itemId, $nextItemId, $prevItemId, $parentId) {
		// \Tracy\Debugger::barDump($itemId, "itemId");
		// \Tracy\Debugger::barDump($nextItemId, "nextItemId");
		// \Tracy\Debugger::barDump($prevItemId, "prevItemId");
		// \Tracy\Debugger::barDump($parentId, "parentId");
		$vars = get_defined_vars();
		\Tracy\Debugger::barDump($vars, "vars");
		foreach ($this->onOrderChange as $callback) {
			$callback($itemId, $nextItemId, $prevItemId, $parentId);
		}
	}

}