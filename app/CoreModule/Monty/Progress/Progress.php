<?php

namespace Monty;

use Nette;
use Exception;


class Progress extends Nette\Application\UI\Control {

	public function render() {
		$template = $this->template;

		$template->setFile(__DIR__ . "./templates/circle.latte");

		$template->render();
	}

}
