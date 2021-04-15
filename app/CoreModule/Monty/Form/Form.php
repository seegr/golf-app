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
					->addRule(FormValidators::PHONE_FORMAT, "Nesprávný formát telefonního čísla", $form[$name]);
					// ->setDefaultValue("+420");
			$control = $input->getControl();
			$input->getControlPrototype()
				->addClass("tel-mask")
				->addAttributes(["placeholder" => "+420 ### ### ###"]);
			return $input;
		});
		Container::extensionMethod('addUrl', function (Container $form, string $name, string $label = null) {
			$input = $form->addText($name, $label)
				->addRule(FormValidators::IS_URL_VALID, "Nesprávný formát URL odkazu", $form[$name])
				->addRule(Form::URL) //** adds http/https before
				->setHtmlType("url");
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
		$this->renderAutocomplete();
		$this->setGroupsInput();

		// if ($this->ajax) {
		// 	$formEl = $this->getElementPrototype();
		// }
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

	// public function setControlsDefaults(): void
	// {
	// 	foreach ($this->getTextInputs() as $control) {
	// 		\Tracy\Debugger::barDump($control, "control");
	// 	}
	// }

}