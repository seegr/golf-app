<?php

namespace App\CoreModule\Components;


use Nette;
// use Nette\Application\UI\Form;
use Monty\Form;


class BaseFormsFactory {

	public $fullWidthCols = "12";
	public $rowClass = "row";


	public function newForm($messages = true) {
		$form = new Form;

		// $renderer = new \Monty\BootstrapFormRenderer;
		// $renderer->setFullWidthCols($this->fullWidthCols);
		// $form->setRenderer($renderer);
		$form->renderAutocomplete("input");

		$form->onRender[] = [$this, "makeBootstrap4"];

		$form->onError[] = function($form) {
			\Tracy\Debugger::barDump($form->getErrors(), "form errors");
			// $vals = $form->getValues();
			// \Tracy\Debugger::barDump($vals, "vals");

			$presenter = $form->getPresenter();
			\Tracy\Debugger::barDump($presenter, "presenter");
			foreach ($form->getErrors() as $error) {
				$presenter->flashMessage($error, "alert-warning");
			}
		};

		$this->submitButtons($form, $messages);

		return $form;
	}

	public function submitButtons($form, $messages = true) {
		$form->addSubmit("save", "Uložit");
		$form->addSubmit("save_stay", "Uložit a zůstat");
		$form->addSubmit("cancel", "Zrušit")->setValidationScope([]);

		$form["save"]->onClick[] = function($btn) use ($form, $messages) {
			$presenter = $form->getPresenter();
			if ($messages) $presenter->flashMessage("Uloženo");			
		};

		$form["save_stay"]->onClick[] = function($btn) use ($form, $messages) {
			$presenter = $form->getPresenter();
			if ($messages) $presenter->flashMessage("Uloženo");			
		};

		$form["cancel"]->onClick[] = function($btn) use ($form, $messages) {
			$presenter = $form->getPresenter();
			if ($messages) $presenter->flashMessage("Neuloženo", "alert-warning");
		};

		return $form;
	}

	public function addSubmitButtons($form) {
		return $this->submitButtons($form);
	}

	public function getMultiSelectPrompt() {
		return [null => "Vyber jeden nebo více..."];
	}

	public function makeBootstrap4($form) {
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = "div class='" . $this->rowClass . "'";
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['pair']['.error'] = 'has-danger';
		// $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
		// $renderer->wrappers['label']['container'] = 'div class="col-sm-3 col-form-label"';
		$renderer->wrappers['control']['description'] = 'span class=form-text';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=form-control-feedback';
		$renderer->wrappers['control']['.error'] = 'is-invalid';

		// \Tracy\Debugger::barDump($form, "form");
		// \Tracy\Debugger::barDump($form->getGroups(), "groups");
		foreach ($form->getControls() as $name => $control) {
			// \Tracy\Debugger::barDump($control, "control");
			$type = $control->getOption('type');
			if ($type === 'button') {
				// $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-secondary');
				$control->getControlPrototype()->addClass("btn");
				// $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-secondary');
				$usedPrimary = true;
			} elseif (in_array($type, ['text', 'textarea', 'select'], true)) {
				$control->getControlPrototype()->addClass('form-control');
			} elseif ($type === 'file') {
				$control->getControlPrototype()->addClass('form-control-file');
			} elseif (in_array($type, ['checkbox', 'radio'], true)) {
				// \Tracy\Debugger::barDump($control, "control");
				// $wrap = $control->getSeparatorPrototype()->setName("div");
				// \Tracy\Debugger::barDump($wrap, "wrap");
				// $wrap->addClass('custom-control');
				// $wrap->addClass("custom-control-" . $type);
				// $control->getControlPrototype()->addClass("custom-control-input");
				// $control->getLabelPrototype()->addClass('custom-control-label');

				if ($control instanceof Nette\Forms\Controls\Checkbox) {
					$control->getLabelPrototype()->addClass('form-check-label');
				} else {
					$control->getItemLabelPrototype()->addClass('form-check-label');
				}
				$control->getControlPrototype()->addClass('form-check-input');
				$control->getSeparatorPrototype()->setName('div')->addClass('form-check');
			}
		}
	}

}