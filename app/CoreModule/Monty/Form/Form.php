<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Monty;

use Nette;
use Nette\Forms\Container;
use Monty\Forms\Controls\UploadControl;

/**
 * Web form adapted for Presenter.
 */
class Form extends Nette\Application\UI\Form
{

	public $autocomplete = "input";
	public $ajax = true;


	public function __construct(string $name = null)
	{
		parent::__construct($name);

		Container::extensionMethod('addTel', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label)
					->addRule(FormValidators::PHONE_FORMAT, "Nesprávný formát telefonního čísla", $form[$name])
					->setDefaultValue("+420");
			$control = $input->getControl();
			$input->getControlPrototype()->addClass("tel-mask");
			return $input;
		});
		Container::extensionMethod('addUrl', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label)
				->addRule(FormValidators::IS_URL_VALID, "Nesprávný formát URL odkazu", $form[$name])
				->addRule(Form::URL) //** adds http/https before
				->setHtmlType("url");
				// ->addRule(Form::URL, "Nesprávný formát url", $form[$name]);
			return $input;
		});
		Container::extensionMethod('addDate', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label);
			$control = $input->getControl();
			$input->getControlPrototype()->addClass("datepicker");
			return $input;
		});
		Container::extensionMethod('addDateTime', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label);
			$control = $input->getControl();
			$input->getControlPrototype()->addClass("datetimepicker");
			return $input;
		});
		Container::extensionMethod('addPrice', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label);
					// ->addRule(Form::INTEGER, "Nesprávný formát ceny", $form[$name]);
			$control = $input->getControl();
			$input->getControlPrototype()->addClass("price-mask");
			return $input;
		});
	}


	public function beforeRender()
	{
		parent::beforeRender();
		// \Tracy\Debugger::barDump("tralal");
		$this->renderAutocomplete();
		$this->setGroupsInput();

		if ($this->ajax) {
			$formEl = $this->getElementPrototype();
			// $formEl->class[] = "ajax";
			// $formEl->setAttribute("data-naja-force-redirect", true);
		}
	}


	public function setPlaceholders()
	{
		$controls = $this->getTextInputs();
		// $controls = $this->getControls();

		foreach ($controls as $control) {
			// \Tracy\Debugger::barDump($control, "control");
			$control->setAttribute("placeholder", $control->caption);
		}
	}

	public function renderAutocomplete()
	{
		$state = $this->autocomplete;
		// \Tracy\Debugger::barDump("renderAutocomplete");
		// \Tracy\Debugger::barDump($state, "state");

		foreach ($this->getComponents() as $control) {
			$cls = Helper::getObjectClassName($control);
			// \Tracy\Debugger::barDump($cls, "cls");
			if (!in_array($cls, ["TextInput", "SelectBox", "MultiSelectBox"])) continue;

			// \Tracy\Debugger::barDump($control, "control");
			if ($state === false) {
				$val = "off";
			} elseif ($state === true) {
				$val = null;
			} elseif ($state == "input") {
				$val = "new-" . $control->name;
			} else {
				$val = "off";
			}

			// \Tracy\Debugger::barDump($control->name, $val);
			$control->setAttribute("autocomplete", $val);
		}

		if ($state !== true) {
			// $cont = $this->getControlPrototype();
			// \Tracy\Debugger::barDump($cont, "form cont");
			$this->setHtmlAttribute("autocomplete", "off");
		}
	}

	public function setGroupsInput() {
		foreach ($this->getComponents() as $control) {
			// if ($control::class == )
			$cls = Helper::getObjectClassName($control);
			// \Tracy\Debugger::barDump($cls, "input class");

			if (in_array($cls, ["TextInput", "SelectBox", "MultiSelectBox"])) {
				$control->getControlPrototype()->addClass("form-control");
			} else if ($cls == "Checkbox") {
				$inputHtml = $control->getControlPrototype();
				// \Tracy\Debugger::barDump($inputHtml, "inputHtml");
				$labelHtml = $control->getLabelPrototype();
				// \Tracy\Debugger::barDump($labelHtml, "labelHtml");
			}
		}
	}

	public function getTextInputs()
	{
		$text = $this->getComponents(true, \Nette\Forms\Controls\TextBase::class);

		return $text;
	}

	public function setAutocomplete($state = true): Form
	{
		$this->autocomplete = $state;

		return $this;
	}

	public function getGroupValues($name)
	{
		$group = $this->getGroup($name);

		if (!$group) return null;

		$arr = [];
		foreach ($group->getControls() as $control) {
			$arr[$control->getName()] = $control->getValue();
		}

		\Tracy\Debugger::barDump($arr, "group vals");
		return ArrayHash::from($arr);
	}

	public function addUpload(string $name, $label = null): UploadControl
	{
		return $this[$name] = new UploadControl($label, false);
	}

	public function addMultiUpload(string $name, $label = null): UploadControl
	{
		return $this[$name] = new UploadControl($label, true);
	}

	// public function validateMaxPostSize(): void
	// {
	// 	\Tracy\Debugger::barDump("validateMaxPostSize...");
	// 	if (!$this->isSubmitted() || !$this->isMethod('post') || empty($_SERVER['CONTENT_LENGTH'])) {
	// 		return;
	// 	}
	// 	$maxSize = Nette\Forms\Helpers::iniGetSize('post_max_size');
	// 	if ($maxSize > 0 && $maxSize < $_SERVER['CONTENT_LENGTH']) {
	// 		$this->addError(sprintf(Nette\Forms\Validator::$messages[self::MAX_FILE_SIZE], $maxSize));
	// 	}
	// }

	// public function setValues($data, bool $erase = false)
	// {
	// 	\Tracy\Debugger::barDump($data, "form data");

	// 	if ($data instanceof \Traversable) {
	// 		$values = iterator_to_array($data);

	// 	} elseif (is_object($data) || is_array($data) || $data === null) {
	// 		$values = (array) $data;

	// 	} else {
	// 		throw new Nette\InvalidArgumentException(sprintf('First parameter must be an array or object, %s given.', gettype($data)));
	// 	}

	// 	foreach ($this->getComponents() as $name => $control) {
	// 		if ($control instanceof IControl) {
	// 			if (array_key_exists($name, $values)) {
	// 				$control->setValue($values[$name]);

	// 			} elseif ($erase) {
	// 				$control->setValue(null);
	// 			}

	// 		} elseif ($control instanceof self) {
	// 			if (array_key_exists($name, $values)) {
	// 				$control->setValues($values[$name], $erase);

	// 			} elseif ($erase) {
	// 				$control->setValues([], $erase);
	// 			}
	// 		}
	// 	}
	// 	return $this;
	// }

}