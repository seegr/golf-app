<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use Nette;
use Monty\Form;


class ContentsListPresenter extends FrontPresenter
{
	use \App\CoreModule\Traits\NavigationsTrait;
	use \App\CoreModule\AdminModule\Traits\ContentsTrait;


    public $limit = 5;

	/** @persistent */
	public $category;
	/** @persistent */
	public $tags = [];
	/** @persistent */
	public $text;
    /** @persistent */
    public $offset;


	public function renderContentsList($type): void
	{
		$template = $this->template;
		$type = $template->type = $this->ContentsManager->getContentType($type);

		$this->bodyClass[] = $type->short;

		\Tracy\Debugger::barDump($this->navItem, "kuk");
		if ($navItem = $this->navItem) {
			$template->navItem = $navItem;
			$template->pageHeading = $navItem->title;
			$template->headerImage = $navItem->header_image ? $navItem->ref("header_image")->url : null;
		} else {
			$template->pageHeading = $this->Translator->translate("global.content.types." . $type->short, 2);
		}

		\Tracy\Debugger::barDump($this);

		$filter = $this["filter"];
		$filter["category"]->setItems([null => "- Kategorie -"] + $this->ContentsManager->getContentTypeCategories($type)->fetchPairs("id", "title"));
		$filter["tags"]->setItems($this->ContentsManager->getContentTypeTags($type)->fetchPairs("id", "title"));

		switch ($type->short) {
			case "event":
				\Tracy\Debugger::barDump("events!");
				$items = $this->ContentsManager->getFutureEvents(false, true);
				$items->order("contents_events_dates.start ASC");
				$colClass = "col-12 col-md-6 col-lg-4";
			break;

			case "product":
				$items = $this->ContentsManager->getActiveContents($type);
				$colClass = "col-12";
			break;

			default:
				$items = $this->ContentsManager->getActiveContents($type);
				$items->order("created DESC");
			break;
		}

		if (!empty($this->category)) {
			$category = $this->CategoriesManager->getCategory($this->category);
			$items->where("category", $category->id);
			$template->category = $category;
		}
		if ($this->tags) $items->where(":contents_tags.tag", $this->tags);
		if ($this->text) $items->whereOr([
			"title LIKE" => "%$this->text%"
		]);

		$colClass = empty($colClass) ? "col-12 col-md-6 col-lg-4" : $colClass;

		$filter->setDefaults($this->getParameters());

		$allItems = (clone $items);
		$template->allItems = $this->limit + $this->offset >= count($allItems) ? true : false;

		\Tracy\Debugger::barDump($items, "items");

		$items->limit($this->limit, $this->offset);

		$template->items = $items;
		\Tracy\Debugger::barDump($items->fetchAll());
		$template->colClass = $colClass;

		$navItemPars = $this->getCurrentNavItemPars();
		\Tracy\Debugger::barDump($navItemPars, "navItemPars");
		$template->filter = isset($navItemPars->filter) ? $navItemPars->filter : null;
	}

	public function createComponentFilter(): Form
	{
		$form = new Form;

		$form->setMethod("get");
		$form->addText("text");
		$form->addSelect("category");
		$form->addMultiSelect("tags");
		$form->addSubmit("submit");

		$form->onSuccess[] = function() {
			$this->redrawControl("items");
		};

		return $form;
	}

	public function handleMoreItems() {
		$this->offset = $this->offset + $this->limit;

		$this->redrawControl("items");
		$this->redrawControl("items-buttons");
	}

}
