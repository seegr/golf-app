<?php

namespace Monty;

use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

use Nette\Forms\Controls;

use \App\Components\Dropzone\Input;


class Dropzone extends Control {

	protected $name;
	protected $message = "Klikni pro výběr nebo jednoduše přetáhni soubory/složku s obrázky";
	protected $autoUpload = false;
	protected $uploadButtonLabel = "Nahrát";
	protected $title;
	protected $acceptedFiles = null;
	protected $inputs = [];
	protected $fileInputs = [];
	protected $uploadedFiles = [];
	protected $inputsForm, $fileInputsForm;
	protected
		$maxFileSize = 10,
		$itemColClass;

	public $onUpload = [];
	public $onConfirm = [];
	public $addedFileCallback = [];
	public $onUploadComplete = [];


	public function __construct() {
		$this->inputsForm = new Form;
		$this->fileInputsForm = new Form;
	}


	public function render() {
		$template = $this->template;

		$template->setFile(__DIR__ . "/dropzone.latte");
		$this->name = $name = $this->getName();
		$template->id = "frm" . ucfirst($name);
		$template->formId = "frm-" . $name . "-dropzoneForm";
		$template->inputsFormId = "frm-" . $name . "";
		$template->controlId = $this->lookupPath();
		$template->name = $name;
		$template->message = $this->message;
		$template->autoUpload = $this->autoUpload;
		$template->uploadButtonLabel = $this->uploadButtonLabel;
		$template->title = $this->title;
		$template->acceptedFiles = $this->acceptedFiles;
		$template->maxFileSize = $this->maxFileSize;
		$template->inputs = $this["inputs"]->getComponents();
		$template->inputsNames = $this->getInputsNames($this["inputs"]);
		$template->inputsCol = $this->getInputsColClass();
		$template->fileInputs = $this["fileInputs"]->getComponents();
		$template->fileInputsNames = $this->getInputsNames($this["fileInputs"]);
		$template->itemColClass = $this->itemColClass ? $this->itemColClass : "col-3";
		
		$this["dropzoneForm"]["session"]->setValue($this->name . "_" . time());

		$template->render();
	}


	public function createComponentDropzoneForm() {
		$form = new Form;

		$form->addUpload("file")->setRequired(false);
		$form->addHidden("session");


		$form->onSuccess[] = function($form, $vals) {
			$hVals = $form->getHttpData();
			#bdump($vals, "vals");
			$uploadedFiles[] = ArrayHash::from([
				"file" => $vals->file,
				"vals" => $vals
			]);

			foreach ($this->onUpload as $callback) {
				#bdump($callback, "callback");
				//bdump($hVals, "hVals");
				$file = $vals->file;
				unset($vals["file"]);
				$fileName = pathinfo($file->name)["filename"];
				$vals->name = $fileName;
				$vals->webalizeName = Strings::webalize($fileName);
				$callback($file, $vals);
			}
		};

		$this->appendDropzoneInputs($form, "inputs");
		$this->appendDropzoneInputs($form, "fileInputs");

		return $form;
	}

	public function createComponentInputs() {
		return $this->inputsForm;
	}

	public function createComponentFileInputs() {
		return $this->fileInputsForm;
	}


	public function handleConfirm() {
		bdump("confirm handle");

		foreach ($this->onConfirm as $callback) {
			#bdump($callback, "callback");
			#$callback();
		}
	}


	public function setMessage($message) {
		$this->message = $message;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function setImageColClass($class) {
		$this->itemColClass = $class;
	}

	public function setAcceptedFiles($accepted) {
		if (is_array($accepted)) {
			$this->acceptedFiles = \Monty\FileSystem::getMime($accepted);
		} else {
			$this->acceptedFiles = $accepted;
		}
	}

	public function getInputsNames($form) {
		$names = [];
		foreach ($form->getComponents() as $id => $input) {
			#bdump($id, "input id");
			$names[] = $id;
		}

		return $names;
	}

	public function getInputsColClass() {
		$count = count($this["inputs"]->getComponents());
		#bdump($count);

		if ($count && $count <= 4) {
			return "col-" . (12 / $count);
		} else {
			return "col-3";
		}
	}

	public function getFileInputsForm() {
		return $this->fileInputsForm;
	}

	protected function appendDropzoneInputs($dropzoneForm, $inputsType) {
		foreach ($this[$inputsType]->getComponents() as $inputId => $input) {
			//bdump($input, "input");
			#$id = $inputsType == "fileInputs" ?  "file_" . $input->name : $input->name;

			$input->setParent($dropzoneForm->getParent());
			$dropzoneForm[$input->name] = $input;
		}
	}

	public function handleUploadComplete() {
		#bdump($this->uploadedFiles, "uploadedFiles");
		
		foreach ($this->onUploadComplete as $callback) {
			#bdump("onUploadComplete");
			#bdump($callback, "callback");
			$callback($this->uploadedFiles);
		}
	}

}
