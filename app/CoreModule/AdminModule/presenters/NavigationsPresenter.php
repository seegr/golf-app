<?php

namespace App\CoreModule\AdminModule\Presenters;

use App;
use Nette;
use Nette\Utils\Html;
use Monty\DataGrid;
use Monty\Form;
use Monty\Tree;
use Monty\Helper;


class NavigationsPresenter extends AdminPresenter
{

	/** @var \App\CoreModule\Model\NavigationsManager @inject */
	public $NavigationsManager;

    /** @var \App\CoreModule\Model\ContentsManager @inject */
    public $ContentsManager;


    protected $id;


	public function actionNavigationsList() {

	}

	public function renderNavigation($id) {
		if ($id) {
			$this->id = $id;
			$tree = $this["navigationTree"];
			$tree->setData($this->NavigationsManager->getNavigationTree($id));
		}
	}

	public function actionNavigationItemForm($id, $navigation) {
		$form = $this["navigationItemForm"];
		$template = $this->template;

		if ($id) {
			$this->id = $id;
			$item = $template->item = $this->NavigationsManager->getNavigationItem($id);
			\Tracy\Debugger::barDump($item, "itemData");
			$form->setDefaults($item);
		} else {
			$form["navigation"]->setValue($navigation);
		}

		// if ($parentId) {
		// 	$item = $this->NavigationsManager->getNavigationItem($parentId);
		// 	$form["parent"]->setValue($item->id);
		// 	$form["navigation"]->setValue($item->navigation);
		// }
	}

	public function renderNavigationItemForm($id, $navId) {
		\Tracy\Debugger::barDump($id, "renderNavigationItemForm id");
		$this->template->item = $this->NavigationsManager->getNavigationItem($id);
	}


	public function createComponentNavigationsList() {
		$list = new DataGrid;

		$list->setDataSource($this->NavigationsManager->getNavigations());
		$list->addColumnLink("title", "Title", "navigation");
		
		return $list;
	}

	public function createComponentNavigationTree() {
		$tree = new Tree;

		$tree->setEditAction("navigationItemForm", ["navigation" => $this->id]);
		$tree->setDeleteAction("navigationItemDelete!");
		$tree->onOrderChange[] = function($itemId, $nextItemId, $prevItemId, $parentId) {
			\Tracy\Debugger::barDump($itemId, "itemId");
			\Tracy\Debugger::barDump($nextItemId, "nextItemId");
			\Tracy\Debugger::barDump($prevItemId, "prevItemId");
			\Tracy\Debugger::barDump($parentId, "parentId");
			// $item = $this->NavigationsManager->getNavigationItem($itemId);
			$sel = $this->NavigationsManager->getNavigationsItems();
			\Tracy\Debugger::barDump($sel->fetchAll(), "sel fetch");
			\Tracy\Debugger::barDump($parentId, "parentId");
			if ($parentId) {
				$sel->where("parent", $parentId);
				\Tracy\Debugger::barDump($sel->fetchAll(), "sel fetch");
			}
			
			$this->NavigationsManager->itemOrderChange($itemId, $prevItemId, $nextItemId, $sel);
			$this->redrawControl();
		};

		return $tree;
	}

	public function createComponentNavigationItemForm() {
		$form = $this->FormsFactory->navigationItemForm();

		$form["item_alias"]->setItems($this->NavigationsManager->getNavigationItems($this->getParameter("id"))->fetchPairs("id", "title"));
		$form["content"]->setItems([null => "- Volitelné -"] + $this->ContentsManager->getContents()->fetchPairs("id", "title"));
		$form["contentsList"]->setItems([null => "- Volitelné -"] + $this->ContentsManager->getContentTypes()->fetchPairs("short", "title"));

		$form->onSuccess[] = function($form, $v) {
			if (!empty($v->contentsList)) {
				$v->route = ":Core:Front:ContentsList:contentsList";
				Helper::merge($v->params, ["type" => $v->contentsList], "json");
			} else if (!empty($v->content)) {
				$v->route = ":Core:Front:Contents:contentDetail";
				$v->params = '{"id":' . $v->content . '}';
			}
			$this->NavigationsManager->saveNavigationItem($v);
			$this->redirect("navigation", $v->navigation);
			$this->redrawControl();
		};

		return $form;
	}


	// public function handleNavigationItemForm($itemId, $parentId) {
	// 	$form = $this["navigationItemForm"];
	// 	$navId = $this->getParameter("id");

	// 	if ($itemId) {
	// 		$item = $this->NavigationsManager->getNavigationItem($itemId);
	// 		\Tracy\Debugger::barDump($item, "itemData");
	// 		$form->setDefaults($item);
	// 	} else {
	// 		$form["navigation"]->setValue($navId);
	// 	}

	// 	if ($parentId) {
	// 		$item = $this->NavigationsManager->getNavigationItem($parentId);
	// 		$form["parent"]->setValue($item->id);
	// 		$form["navigation"]->setValue($item->navigation);
	// 	}

	// 	$template = $this->template;
	// 	$latte = $template->getLatte();

	// 	$this->modal([
	// 		"title" => "Navigační tlačítko",
	// 		"content" => $latte->renderToString(__DIR__ . "/../templates/Navigations/navigationItemForm.latte")
	// 	]);
	// }

	public function handleNavigationItemDelete($itemId) {
		$this->NavigationsManager->getNavigationItem($itemId)->delete();

		$this->flashMessage("Smazáno");
		$this->redrawControl();
	}

	public function createComponentHeaderImageCropper() {
		$cropper = new \Monty\Cropper;

		$cropper->setMaxSizes(1920, null);
		// $cropper->setRatio("1/0.156");

		$cropper->onCropp[] = function($vals) {
			\Tracy\Debugger::barDump($vals, "headerImage cropper vals");
			$fileId = $this->FilesManager->uploadImage($vals->path, $this->getUser()->id);
			// $this->UsersManager->getUser($user->id)->update(["image" => $vals->image]);
			$this->NavigationsManager->getNavigationItem($this->id)->update(["header_image" => $fileId]);
			$this->redrawControl("headerImageCropper");
		};

		return $cropper;
	}

}