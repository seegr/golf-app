<?php

declare(strict_types=1);

namespace Monty;

use Monty\DataGrid\Action;


class DataGrid extends \Ublaboo\DataGrid\DataGrid
{

	protected $tableClass = ["table", "table-sm", "table-hover", "table-bordered", "table-striped", "datagrid-table"];

	public function __construct(Nette\ComponentModel\IContainer $parent = null, $name = null) {
		parent::__construct($parent, $name);

		$this->setTranslator(new \Ublaboo\DataGrid\Localization\SimpleTranslator([
		'ublaboo_datagrid.no_item_found_reset' => 'Žádné položky nenalezeny. Filtr můžete vynulovat',
		'ublaboo_datagrid.no_item_found' => 'Žádné položky nenalezeny.',
		'ublaboo_datagrid.here' => 'zde',
		'ublaboo_datagrid.items' => 'Položky',
		'ublaboo_datagrid.all' => 'všechny',
		'ublaboo_datagrid.from' => 'z',
		'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
		'ublaboo_datagrid.group_actions' => 'Hromadné akce',
		'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
		'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
		'ublaboo_datagrid.action' => 'Akce',
		'ublaboo_datagrid.previous' => 'Předchozí',
		'ublaboo_datagrid.next' => 'Další',
		'ublaboo_datagrid.choose' => 'Vyberte',
		'ublaboo_datagrid.execute' => 'Provést',
		"ublaboo_datagrid.cancel" => "Zrušit",
		"ublaboo_datagrid.save" => "Uložit",
		"ublaboo_datagrid.add" => "Přidat",
		"ublaboo_datagrid.edit" => "Upravit",

		'Name' => 'Jméno',
		'Inserted' => 'Vloženo'
		]));

		// $this->setTemplateFile(__DIR__ . '/template.latte');
		$this->setItemsPerPageList([20, 50, 100]);
	}

	public function onRender() {
		parent::onRender();
		// \Tracy\Debugger::barDump("tralala");

		$this->getTemplate()->tableClass = $this->tableClass;
		$this->setFilterControls();
	}

	public function renderCounter($text = null) {
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/counter.latte");
		$template->text = $text ? $text : "Počet záznamů";

		$count = count($this->getDataSource()->getData());

		$template->count = $count;

		$template->render();
	}


	// public function getPerPage() {
	// 	$items_per_page_list = $this->getItemsPerPageList();

	// 	$per_page = $this->per_page ?: reset($items_per_page_list);
	// 	if (($per_page !== 'all' && !in_array((int) $this->per_page, $items_per_page_list, true))
	// 		|| ($per_page === 'all' && /* changed -----> */ !isset($items_per_page_list[$this->per_page]))) {
	// 		$per_page = reset($items_per_page_list);
	// 	}

	// 	return $per_page;
	// }

	public function addTableClass($class) {
		$this->tableClass[] = $class;

		return $this;
	}

	// public function addColumnText($key, $name, $column = null)
	// {
	// 	$this->addColumnCheck($key);
	// 	$column = $column ?: $key;

	// 	return $this->addColumn($key, new DataGrid\ColumnText($this, $key, $column, $name));
	// }

	public function getDataCount() {
		return count($this->getDataSource()->getData());
	}

	public function addAction(
		string $key,
		string $name,
		?string $href = null,
		?array $params = null
	): Action
	{
		$this->addActionCheck($key);

		$href = $href ?? $key;

		if ($params === null) {
			$params = [$this->primaryKey];
		}

		return $this->actions[$key] = new Action($this, $key, $href, $name, $params);
	}

	// public function addAction($key, $name, $href = null, array $params = null): Action
	// {
	// 	$this->addActionCheck($key);

	// 	$href = $href ?: $key;

	// 	if ($params === null) {
	// 		$params = [$this->primary_key];
	// 	}

	// 	$action = new \Monty\DataGrid\Action($this, $href, $name, $params);
	// 	$action->setKey($key);

	// 	return $this->actions[$key] = $action;
	// }

	// protected function addColumn($key, Column\Column $column) {
	// 	$this->onColumnAdd($key, $column);

	// 	$this->columns_visibility[$key] = [
	// 		'visible' => true,
	// 	];

	// 	return $this->columns[$key] = $column;
	// }

	public function orderColumns(array $keys) {
		// \Tracy\Debugger::barDump($this->columns, "columns");

		$oldCols = $this->columns;
		$cols = [];

		foreach ($keys as $key) {
			if (isset($oldCols[$key])) $cols[$key] = $oldCols[$key];
			unset($oldCols[$key]);
		}

		// \Tracy\Debugger::barDump($cols, "cols");

		$this->columns = $cols + $oldCols;

		// \Tracy\Debugger::barDump($this->columns, "cols");
	}

	public function moveColumnStart($key) {
		$oldCols = $this->columns;
		$cols = [];

		if (isset($oldCols[$key])) {
			$cols[$key] = $oldCols[$key];
			unset($oldCols[$key]);
		}

		$this->columns = $cols + $oldCols;
	}

	public function getActions() {
		return $this->actions;
	}

	public function setFilterControls()
	{
		foreach ($this->filters as $filter) {
			// \Tracy\Debugger::barDump($filter, "filter");
			// \Tracy\Debugger::barDump($filter->getPrototype());
			$filter->setAttribute("class", $filter->getAttributes()["class"] + ["datagrid-form-control"]);
			$classes = $filter->getAttributes()["class"];
			$classes[] = "datagrid-form-control";
			// \Tracy\Debugger::barDump($classes, "classes");
			$filter->setAttribute("class", $classes);
			$filter->setAttribute("autocomplete", "off");
		}		
	}

}