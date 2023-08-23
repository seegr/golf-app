<?php

namespace Monty\DataGrid;


class Action extends \Ublaboo\DataGrid\Column\Action {

	public function setTooltip($tooltip) {
		$this->addAttributes([
			"data-toggle" => "tooltip",
			"title" => $tooltip
		]);

		return $this;
	}

	public function setConfirm(string $text): self
	{
		$this->addAttributes([
			"data-confirm" => $text
		]);

		return $this;
	}

	/*public function hideAction($state = true) {
		$this->checkPropertyStringOrCallable($state, 'state');

		$this->hideState = $state;

		return $this;
		// if ($state) {
		// 	bdump($this, "this");
		// 	bdump($this->key, "key");
		// 	$actions = $this->grid->getActions();
		// 	bdump($actions, "actions");
		// 	$this->grid->removeAction($this->key);
		// }
	}*/

	public function setKey($key) {
		$this->key = $key;

		return $this;
	}

}