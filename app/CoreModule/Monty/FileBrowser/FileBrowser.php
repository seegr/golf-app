<?php

namespace Monty;

use Nette;
use App;

use Nette\Utils\ArrayHash;

use Monty\FileBrowser\File;
use Monty\FileBrowser\Action;


class FileBrowser extends Nette\Application\UI\Control {

	protected
		$id,
		$files,
		$filesSource,
		$keys = [],
		$filePicker,
		$filePickerMultiple,
		$selectedActions = [];

	public
		$base,
		$thumbsSubfolder,
		$fileColClass = "col";
	
	public function __construct() {
		return $this;
	}


	public function render() {
		$this->id = "filebrowser-control-" . $this->lookupPath();

		$template = $this->template;

		$template->id = $this->id;
		$template->lookupPath = $this->lookupPath();
		$template->files = $this->getFilesArray();
		$template->base = $this->base;
		$template->fileColClass = $this->fileColClass;
		$template->filePicker = $this->filePicker;
		$template->filePickerMultiple = $this->filePickerMultiple;
		$template->selectedActions = $this->selectedActions;

		$template->setFile(__DIR__ . "/templates/filebrowser.latte");

		$template->render();
	}

	public function setFilesSource($files) {
		$this->filesSource = $files;

		return $this;
	}

	public function setKeySrc($key) {
		$this->keys["src"] = $key;

		return $this;
	}

	public function setKeyPath($key) {
		$this->keys["path"] = $key;

		return $this;
	}

	public function setKeyLabel($key) {
		$this->keys["label"] = $key;

		return $this;
	}

	public function setThumbsSubfolder($folder) {
		$this->thumbsSubfolder = $folder;

		return $this;
	}

	public function setBase($base) {
		$this->base = $base;

		return $this;
	}

	public function setFileColClass($class) {
		$this->fileColClass = $class;

		return $this;
	}

	public function setFilePicker($state = true, $multiple = false) {
		$this->filePicker = $state;
		$this->filePickerMultiple = $multiple;

		return $this;
	}

	public function addFile($id, $path, $src, $label = null) {
		$file = new File($this, $id, $path, $src, $label);

		$this->files[$id] = $file;

		return $file;
	}

	public function addSelectedAction($name, $label, $callback) {
		$action = new Action($name, $label, $callback);

		$this->selectedActions[$name] = $action;

		return $action;
	}

	public function getFilesArray() {
		$files = [];

		if ($this->filesSource) {
			bdump($this->filesSource, "filesSource");
			$keys = ArrayHash::from($this->keys);
			$keySrc = $keys->src;
			$keyLabel = $keys->label;
			$keyPath = $keys->path;

			foreach ($this->filesSource as $id => $item) {
				#bdump($id, "id");
				#bdump($item, "item");
				bdump($this->keys, "keys");

				$path = $item->$keyPath;
				$src = $item->$keySrc;
				$label = $item->$keyLabel;

				$file = new File($this, $id, $path, $src, $label);

				$files[] = $file;
			}
		} else {
			$files = $this->files;
		}

		if ($files) {
			return ArrayHash::from($files);	
		} else {
			return [];
		}
	}


	public function handleSelectedAction($action, array $selected) {
		if (!$this->filePickerMultiple) {
			$selected = $selected[0];
		}

		$callback = $this->selectedActions[$action]->callback;
		bdump($callback);

		$callback($selected);
	}

}