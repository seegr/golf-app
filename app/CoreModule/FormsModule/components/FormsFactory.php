<?php

namespace App\CoreModule\FormsModule\Components;

use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Monty\DataGrid;
use Monty\FormValidators;

class FormsFactory extends \App\CoreModule\Components\BaseFormsFactory {

	use \App\CoreModule\FormsModule\Traits\FormsTrait;

	const CUSTOM_FORM_TEMPLATE = __DIR__ . "/../templates/customFormTemplate.latte";

	protected $FormsManager;

	public function __construct(\App\CoreModule\FormsModule\Model\FormsManager $FormsManager) {
		$this->FormsManager = $FormsManager;
	}


	public function formForm() {
		$form = $this->newForm();

		$form->addHidden("id");
		$form->addText("title", "Název")->setRequired();
		$form->addTextArea("intro_text", "Text před formulářem");
		$form->addTextArea("end_text", "Text na konci");

		return $form;
	}

	public function formFieldForm() {
		bdump($this->FormsManager, "formsmanager");
		$form = $this->newForm();

		$form->addHidden("id");
		$form->addHidden("form");
		$form->addSelect("type", "Typ", $this->FormsManager->getFormsFieldsTypes()->fetchPairs("id", "title"))->setRequired();
		$form->addText("label", "Popisek")->setRequired();
		$form->addCheckbox("required", "Povinný");
		$form->addCheckbox("in_summary", "Zobrazit ve výpisu")->setDefaultValue(true);
		$form->addText("summary_label", "Popisek ve výpisu");
		$form->addTextArea("desc", "Dlouhý popis");
		$form->addCheckbox("admin", "Pouze pro správce");
		$form->addCheckbox("active", "Aktivní")->setDefaultValue(true);

		return $form;
	}

	public function formFieldOptionForm() {
		$form = $this->newForm();

		$form->addHidden("id");
		$form->addHidden("field");
		$form->addText("label", "Popisek")->setRequired();
		// $form->addText("value", "Hodnota");
		$form->addInteger("selects_limit", "Limit výběrů");
		$form->addCheckbox("active", "Aktivní")->setDefaultValue(true);

		return $form;
	}

	public function customForm($id, $records = null) {
		// \Tracy\Debugger::barDump($id, "custom form");
		// if ($records) \Tracy\Debugger::barDump($records->fetchAll(), "records");

		$form = $this->newForm();
		// \Tracy\Debugger::barDump($form, "form");
		// $renderer = $form->getRenderer();
		// \Tracy\Debugger::barDump($renderer, "renderer");
		// $template = $renderer->template;
		// $template->setFile(__DIR__ . "/../templates/Forms/customForm.latte");

		$f = $this->FormsManager->getForm($id);
		// \Tracy\Debugger::barDump($f, "f");
		$formId = $f->id;
		$fields = $this->FormsManager->getFormFields($id)->order("order");
		if ($records) {
			$data = [];
			foreach ($records as $rec) {
				$data[] = Json::decode($rec->data);
			}
			$records = $data;
			// \Tracy\Debugger::barDump($records, "records");
		}
		// \Tracy\Debugger::barDump($fields->fetchAll(), "fields");

		foreach ($fields as $field) {
			// \Tracy\Debugger::barDump($field, "field");
			$type = $field->ref("type");
			$method = $type->method;

			if (!$field->active) continue;
			$control = $form->$method($field->name, $field->label)->setRequired($field->required ? true : false);

			if ($this->isFieldSelector($field->id)) {
				$options = $this->FormsManager->getFormFieldOptions($field->id)->where("active", true);

				//** limit check
				$items = [];
				foreach ($options as $opt) {
					if ($records && $opt->selects_limit) {
						// \Tracy\Debugger::barDump($opt->selects_limit, "je tam limit");
						$count = 0;
						foreach ($records as $rec) {
							$fieldName = $field->name;
							if (!empty($rec->$fieldName) && $rec->$fieldName == $opt->label) $count++;
						}
						// \Tracy\Debugger::barDump($count, "count");
						if ($count >= $opt->selects_limit) continue;
					}

					$items[$opt->label] = $opt->label;
				}
				$control->setItems($items);

				// if ($type == "radio") $control->setDefaultValue();
			}

			// \Tracy\Debugger::barDump($type, "type");
			switch ($type->short) {
				case "tel":
						if ($control->isRequired()) {
							$control->setDefaultValue("+420");
						}
					break;
			}
		}

		// \Tracy\Debugger::barDump($form, "form");
		unset($form["save"], $form["save_stay"], $form["cancel"]);

		$form->addHidden("id");
		$form->addHidden("hash");
		$form->addSubmit("submit")
			->getControlPrototype()->addClass("btn-primary ajaxx")->addAttributes([
				"data-loader" => "this"
			]);

		// $form["save"]->onClick[] = function($f, $v) use ($formId) {
		// 	\Tracy\Debugger::barDump("save click");
		// 	$this->submitCustomForm($formId, $v);
		// };

		// $form["save_stay"]->onClick[] = function($f, $v) use ($formId) {
		// 	\Tracy\Debugger::barDump("save_stay click");
		// 	$this->submitCustomForm($formId, $v);
		// };

		// $form->onSuccess[] = function($f, $v) {
		// 	\Tracy\Debugger::barDump("success");
		// };

		// $form->onError[] = function($f, $v) {
		// 	\Tracy\Debugger::barDump("error");
		// };

		return $form;
	}

	public function formRecordsList($id) {
		$list = new DataGrid;

		$fields = $this->FormsManager->getFormFields($id);
		// \Tracy\Debugger::barDump($fields->fetchAll(), "fields");
		foreach ($fields as $field) {
			$name = $field->name;
			$label = $field->summary_label ? $field->summary_label : $field->label;
			// \Tracy\Debugger::barDump($name, "records list - field name");

			switch ($field->ref("type")->short) {
				case "email":
					$list->addColumnLink($name, $label)->setRenderer(function($i) use ($name) {
						$a = Html::el("a");
						$a->href("mailto:" . $i[$name]);
						$a->setText($i[$name]);
						return $a;
					});
				break;

				case "textarea":
					$list->addColumnText($name, $label);
				break;

				default:
					$list->addColumnText($name, $label);
				break;
			}
		}
		$list->addColumnDateTime("edited", "Upraveno")->setFormat("j.n.Y H:i:s")->setFitContent();
		$list->addColumnDateTime("time", "Vloženo")->setFormat("j.n.Y H:i:s")->setFitContent();

		// $list->addAction("edit", "", ":Forms:Admin:Forms:formRecord");

		return $list;
	}

	public function recordForm($id) {
		$record = $this->FormsManager->getFormRecord($id);
		$data = $this->FormsManager->getFormRecord($id, true);
		$f = $record->ref("form");

		$form = $this->customForm($f->id);
		$form->setDefaults($data);

		$form->onSuccess[] = function($form, $v) use ($f) {
			$this->FormsManager->saveFormRecord($f->id, $v);
		};

		return $form;
	}

	// public function setTemplateRender($state = true) {
	// 	$this->templateRender = $state;

	// 	return $this;
	// }

	// public function submitCustomForm($formId, $vals) {
	// 	\Tracy\Debugger::barDump($vals, "vals");
	// 	$this->FormsManager->saveFormData($formId, $vals);
	// }

}