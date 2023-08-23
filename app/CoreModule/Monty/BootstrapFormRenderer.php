<?php

namespace Monty;

use Nette;
use Nette\Utils\Html;
use Nette\Utils\IHtmlString;


class BootstrapFormRenderer extends \Nette\Forms\Rendering\DefaultFormRenderer {

	public $fullWidthCols = "12";


	public function setFullWidthCols($cols) {
		$this->fullWidthCols = $cols;

		return $this;
	}

	public function renderPairMulti(array $controls): string
	{
		// bdump($controls, "controls");
		$s = [];
		foreach ($controls as $control) {
			if (!$control instanceof Nette\Forms\IControl) {
				throw new Nette\InvalidArgumentException('Argument must be array of Nette\Forms\IControl instances.');
			}
			$description = $control->getOption('description');
			if ($description instanceof IHtmlString) {
				$description = ' ' . $description;

			} elseif ($description != null) { // intentionally ==
				if ($control instanceof Nette\Forms\Controls\BaseControl) {
					$description = $control->translate($description);
				}
				$description = ' ' . $this->getWrapper('control description')->setText($description);

			} else {
				$description = '';
			}

			$control->setOption('rendered', true);
			$el = $control->getControl();
			if ($el instanceof Html && $el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), true);
			}
			$s[] = $el . $description;
		}
		$pair = $this->getWrapper('pair container');
		// bdump($pair, "pair");
		$pair->setClass("form-group col-auto w-100");

		$pair->addHtml($this->renderLabel($control));
		$pair->addHtml($this->getWrapper('control container')->setHtml(implode(' ', $s)));
		return $pair->render(0);
	}

	public function renderPair(Nette\Forms\IControl $control): string
	{
		// bdump($control, "control");
		$pair = $this->getWrapper('pair container');
		// bdump($pair, "pair");
		// $class = $pair["attrs"]["class"];
		$class = $pair->getAttribute("class");

		$class .= " col-";
		if ($control->getOption("type") == "textarea") {
			$class .= $this->fullWidthCols;
		} else {
			$class .= $this->fullWidthCols . " col-lg-" . $this->fullWidthCols / 2;
		}

		$pair->setClass($class);
		$pair->addHtml($this->renderLabel($control));
		$pair->addHtml($this->renderControl($control));
		$pair->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), true);
		$pair->class($control->hasErrors() ? $this->getValue('pair .error') : null, true);
		$pair->class($control->getOption('class'), true);
		if (++$this->counter % 2) {
			$pair->class($this->getValue('pair .odd'), true);
		}
		$pair->id = $control->getOption('id');
		return $pair->render(0);
	}

}