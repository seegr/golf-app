<?php

namespace App\CoreModule\FormsModule\AdminModule\Presenters;

use Monty\DataGrid;
use Monty\Html;
use Nette\Utils\ArrayHash;


class FormsPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter {

	use \App\CoreModule\FormsModule\Traits\FormsTrait;


	/** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
	public $FormsManager;

	/** @var \App\CoreModule\FormsModule\Components\FormsFactory @inject */
	public $FormsFactory;


    public function actionFormForm($id) {
		$template = $this->template;
		$formForm = $this["formForm"];

		if ($id) {
			$form = $this->FormsManager->getForm($id);
			$formForm->setDefaults($form);

			$template->frm = $form;
		}
	}

	public function actionFormFieldForm($id, $formId) {
		$template = $this->template;
		$form = $this["formFieldForm"];

		if ($id) {
			$field = $this->FormsManager->getFormField($id);

			$form->setDefaults($field);
			$template->field = $field;
			$template->formId = $formId = $field->form;
		} else {
			$form["form"]->setValue($formId);
			$template->formId = $formId;
		}

		$template->form = $this->FormsManager->getForm($formId);
	}

	public function actionFormFieldOptionForm($id, $fieldId) {
		$template = $this->template;
		$form = $this["formFieldOptionForm"];

		if ($id) {
			$option = $this->FormsManager->getFormFieldOption($id);

			$form->setDefaults($option);
			$template->option = $option;
			$template->fieldId = $option->field;
		} else {
			$form["field"]->setValue($fieldId);
			$template->fieldId = $fieldId;
		}
	}

	public function actionFormRecordForm($id) {

	}


	public function createComponentFormsList() {
		$list = new DataGrid;

		$list->setDataSource($this->FormsManager->getForms());
		$list->addColumnLink("title", "Název", "formForm")->setRenderer(function($i) {
			$a = Html::el("a");
			$a->href($this->link("formForm", $i->id));
			$a->setText($i->title);

			$desc = Html::el("div");

			bdump($i->id, $i->title);
			$fields = $this->FormsManager->getFormFields($i->id);
			bdump($fields->fetchAll(), "fields");
			$i = 1;
			foreach ($fields as $field) {
				$wrap = Html::el("span");
				$wrap->class[] = "badge badge-grey";
				$wrap->setText($field->label);
				$desc->addHtml($wrap);
				if ($i < count($fields)) {
					$wrap->class[] = "mr-1";
				}
				$i++;
			}

			return Html::el("div")
				->addHtml($a)
				->addHtml($desc);
		});
		$list->addAction("duplicate", "", "DuplicateForm!")
			->setClass("fad fa-clone text-primary ajax")
			->setTooltip("Duplikovat");
		$list->addAction("delete", "", "deleteForm!")
			->setClass("fas fa-trash ml-2 text-danger ajax")
			->setConfirm("Smazat?");

		return $list;
	}

	public function createComponentFormForm() {
		$form = $this->FormsFactory->formForm();

		$form["save"]->onClick[] = function($f, $v) {
			$this->FormsManager->saveForm($v);

			$this->redirect("formsList");
		};

		$form["save_stay"]->onClick[] = function($f, $v) {
			$id = $this->FormsManager->saveForm($v);

			$this->redirect("this", $id);
		};

		$form["cancel"]->onClick[] = function($f, $v) {
			$this->redirect("formsList");
		};

		return $form;
	}

	public function createComponentFormFieldsList() {
		$list = new DataGrid;

		$list->setSortable()->setSortableHandler("formFieldsReorder!");
		$list->setRefreshUrl(false);

		$replacement = [
			0 => "",
			1 => "*"
		];

		$list->setDataSource($this->FormsManager->getFormFields($this->getParameter("id"))->order("order"));
		$list->addColumnLink("label", "Popisek", "formFieldForm")->setRenderer(function($i) {
			$label = $i->summary_label ? $i->summary_label : $i->label;
			$a = Html::el("a");
			$a->href($this->link("formFieldForm", $i->id));
			$a->setText($label);

			return $a;
		});
		$list->addColumnText("name", "Name");
		$list->addAction("active", "", "formFieldActive!", ["fieldId" => "id"])
			->setIcon("fas fa-check")
			->setClass(function($item) {
				return $item->active ? "text-success ajax" : "text-muted ajax";
			});
		$list->addAction("delete", "", "deleteFormField!", ["fieldId" => "id"])
			->setIcon("fas fa-trash")
			->setClass("ml-1 text-danger")
			->setConfirm("Smazat?");
		$list->addColumnText("required", "Povinné")->setReplacement($replacement)->setFitContent()->setAlign("center");
		$list->addColumnText("admin", "Pro správce")->setReplacement($replacement)->setFitContent()->setAlign("center");
		// $list->addColumnText("in_summary", "V přehledu")->setReplacement($replacement)->setFitContent()->setAlign("center");
		// $list->addColumnText("summary_label", "Popisek v přehledu");

		return $list;
	}

	public function createComponentFormFieldForm() {
		$form = $this->FormsFactory->formFieldForm();

		$form["save"]->onClick[] = function($f, $v) {
			bdump($v, "v");
			$this->FormsManager->saveFormField($v);

			$this->redirect("formForm", $v->form);
		};

		$form["save_stay"]->onClick[] = function($f, $v) {
			$id = $this->FormsManager->saveFormField($v);

			$this->redirect("this", $id);
		};

		$form["cancel"]->onClick[] = function($f, $v) {
			bdump($v, "vals");
			$this->redirect("formForm", $v->form);
		};

		return $form;
	}

	public function createComponentFormFieldOptionsList() {
		$list = new DataGrid;

		$list->setSortable()->setSortableHandler("formFieldOptionsReorder!");

		$list->setDataSource($this->FormsManager->getFormFieldOptions($this->getParameter("id"))->order("order"));
		$list->addColumnLink("label", "Popisek", "formFieldOptionForm");
		$list->addColumnText("selects_limit", "Limit výběrů");
		$list->addAction("active", "", "formFieldOptionActive!", ["optionId" => "id"])
			->setIcon("fas fa-check")
			->setClass(function($item) {
				return $item->active ? "text-success ajax" : "text-muted ajax";
			});
		$list->addAction("delete", "", "deleteFormFieldOption!", ["optionId" => "id"])
			->setIcon("fas fa-trash")
			->setClass("ml-1 text-danger")
			->setConfirm("Smazat?");

		return $list;
	}

	public function createComponentFormFieldOptionForm() {
		$form = $this->FormsFactory->formFieldOptionForm();

		$form["save"]->onClick[] = function($f, $v) {
			$this->FormsManager->saveFormFieldOption($v);

			$this->redirect("formFieldForm", $v->field);
		};

		$form["save_stay"]->onClick[] = function($f, $v) {
			$id = $this->FormsManager->saveFormFieldOption($v);

			$this->redirect("this", $id);
		};

		$form["cancel"]->onClick[] = function($f, $v) {
			$this->redirect("formFieldForm", $v->field);
		};

		return $form;
	}


	public function handleDeleteForm($id) {
		$this->FormsManager->deleteForm($id);

		$this->flashMessage("Smazáno", "alert-danger");
		$this->redrawControl();
	}

	public function handleFormFieldActive($fieldId) {
		$field = $this->FormsManager->getFormField($fieldId);

		$field->update(["active" => !$field->active]);
		$this["formFieldsList"]->reload();
	}

	public function handleDeleteFormField($fieldId) {
		$this->FormsManager->getFormField($fieldId)->delete();
		$this["formFieldsList"]->reload();
	}

	public function handleDeleteFormFieldOption($optionId) {
		$this->FormsManager->getFormFieldOption($optionId)->delete();
		$this["formFieldOptionsList"]->reload();
	}

	public function handleFormFieldOptionActive($optionId) {
		$option = $this->FormsManager->getFormFieldOption($optionId);

		$option->update(["active" => !$option->active]);
		$this["formFieldOptionsList"]->reload();
	}

	public function handleFormFieldsReorder($item_id, $prev_id, $next_id) {
		bdump($item_id, "item_id");
		bdump($prev_id, "prev_id");
		bdump($next_id, "next_id");

		$field = $this->FormsManager->getFormField($item_id);
		bdump($field, "field");
		$items = $this->FormsManager->getFormFields($field->form);
		bdump($items->fetchAll(), "items");

		$this->FormsManager->itemOrderChange($item_id, $prev_id, $next_id, $items);

		$this["formFieldsList"]->reload();
	}

	public function handleFormFieldOptionsReorder($item_id, $prev_id, $next_id) {
		$opt = $this->FormsManager->getFormFieldOption($item_id);
		$items = $this->FormsManager->getFormFieldOptions($opt->field);
		$this->FormsManager->itemOrderChange($item_id, $prev_id, $next_id, $items);

		$this["formFieldsList"]->reload();
	}

	public function handleDuplicateForm($id) {
		$form = $this->FormsManager->getForm($id);
		$fields = $this->FormsManager->getFormFields($id);

		$form = $form->toArray();
		$fields = $fields->fetchAll();
		bdump($form, "form");

		unset($form["id"]);
		$newForm = $this->FormsManager->saveForm(ArrayHash::from($form));
		
		foreach ($fields as $field) {
			$field = $field->toArray();
			bdump($field, "field");
			$fieldId = $field["id"];
			unset($field["id"]);
			$field["form"] = $newForm;
			$newField = $this->FormsManager->saveFormField(ArrayHash::from($field));

			foreach ($this->FormsManager->getFormFieldOptions($fieldId) as $option) {
				$option = $option->toArray();
				bdump($option, "option");
				unset($option["id"]);
				$option["field"] = $newField;
				$this->FormsManager->saveFormFieldOption(ArrayHash::from($option));				
			}
		}

		$this["formsList"]->reload();
	}

}