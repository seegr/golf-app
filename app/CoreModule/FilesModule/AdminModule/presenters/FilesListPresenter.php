<?php

namespace App\CoreModule\FilesModule\AdminModule\Presenters;

use Nette;
use Monty\DataGrid;
use Monty\Html;
use Monty\Filter;

use App\CoreModule\Model\FilesManager;


class FilesListPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter {

	use \App\CoreModule\AdminModule\Traits\FilesTrait;


	public $files;

	/** @persistent */
	public $type;

	public function startup(): void
	{
		parent::startup();

		// $filter = $this["filter"]->getFilter();
		// bdump($filter, "filter");

		$this["fileUploadForm"]->onSuccess[] = function() {
			$this->redrawFilesList();
		};
	}

	public function renderFilesList($type = "all", $page = 1) {
		$this->template->contentBlock = "content";
		$this->getFilesList($type, $page);
		bdump("renderFilesList");
	}

	// public function renderFilePicker($type = "all", $page = 1) {
	// 	$this->getFilesList($type, $page);
	// 	$this->template->contentBlock = "custom";
	// 	bdump("renderFilePicker");
	// }

	public function getFilesList($type, $page) {
		bdump("getFilesList");
		bdump($type, "type");

		$template = $this->template;
		$template->setFile(__DIR__ . "/../templates/FilesList/filesList.latte");

		$user = $this->getUser();

		$template->type = $type;

		if ($this->isAjax()) {
			$this->redrawFilesList();
		}

		// $files = $this->files = $this->FilesManager->getFiles()->where("key NOT LIKE ?", "%thumb_%");
		$files = $this->files = $this->FilesManager->getFiles()->where(":files.id IS NULL");

		if (!$user->isInRole("superadmin")) $files->where("files.user", $this->getUser()->id);

		switch ($type) {
			case "image":
				$files->where("files.ext", FilesManager::IMAGES_EXT);
			break;

			case "file":
				$files->where("files.ext NOT", FilesManager::IMAGES_EXT);
			break;

			default:
			break;
		}

		$filter = $this["filter"]->getFilter();
		bdump($filter, "filter");

		if (!empty($filter->order)) {
			$files->order($filter->order);
		} else {
			$files->order("inserted DESC");
		}

		if (!empty($filter->text)) {
			bdump("name jojo");
			$files->whereOr([
				"files.name LIKE ?" => "%$filter->text%"
			]);
		}

		$this->files = $files;
		$count = count($files);

		$pagi = new Nette\Utils\Paginator;
		$pagi->setItemCount($count);
		$pagi->setItemsPerPage(20);
		$pagi->setPage($page);

		$files->limit($pagi->getLength(), $pagi->getOffset());

		$template->files = $files;
		$template->setParameters([
			"files" => $files,
			"count" => $count,
			"pagi" => $pagi
		]);
	}


	public function createComponentFilter() {
		$filter = new Filter;

		$filter->addOrder([
			"name" => "Názvu",
			"inserted DESC" => "Vložení"
		]);
		// $filter->setDefaults([
		// 	"order" => "inserted DESC"
		// ]);

		$filter->onSubmit[] = function() {
			$this->redrawFilesList();
		};

		return $filter;
	}

	public function handleDeleteFile($id) {
		$this->deleteFile($id);
		$this->redrawFilesList();
	}

	public function redrawFilesList() {
		bdump("redraw");
		$this->redrawControl("files-wrap-area");
		$this->redrawControl("files-wrap");
	}


	// public function createComponentFilesList() {
	// 	$list = new DataGrid;

	// 	$list->setTemplateFile(__DIR__ . "/../templates/FilesList/datagridtemplate.latte");
	// 	$list->setDataSource($this->FilesManager->getFiles());
	// 	$list->setOuterFilterRendering();
	// 	$list->setCollapsibleOuterFilters(false);

	// 	// $list->addFilterText('name', 'Name');

	// 	$list->addColumnText("name", "Název")->setFilterText();
	// 	$list->addAction("download", "")->setRenderer(function($item) {
	// 		$btn = Html::el("a");

	// 		$btn->addAttributes([
	// 			"href" => $item->url,
	// 			"target" => "_blank"
	// 		]);
	// 		$btn->setClass("fad fa-download btn btn-sm btn-primary");

	// 		return $btn;
	// 	});

	// 	return $list;
	// }

}