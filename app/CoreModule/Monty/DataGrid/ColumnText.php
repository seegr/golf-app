<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Monty\DataGrid;

class ColumnText extends \Ublaboo\DataGrid\Column\Column
{
	public function setEllipsis($state = true) {
		($state) ? $this->addAttributes(['class' => 'col-ellipsis']) : null;

		return $this;
	}

	public function setFitCenter() {
		$this->setFitContent();
		$this->setAlign("center");

		return $this;
	}
}
