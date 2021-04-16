<?php

namespace App\CoreModule\AdminModule\Presenters;

use Nette\Utils\Json;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use App\CoreModule\FormsModule\Model\FormsManager;


class MaintenancePresenter extends AdminPresenter {

  /** @inject */
  public FormsManager $FormsManager;


	public function beforeRender(): void
	{
		parent::beforeRender();
		$this->setLayout(__DIR__ . "/../templates/@layout.latte");
		$this->template->setFile(__DIR__ . "/../templates/Maintenance/default.latte");
	}

	public function handleTest() {}

	public function actionDefault($act = null, $args = null) {
		if ($act) {
			$this->$act($args);
		}
		
		$this->template->act = $act;

		$class = new \ReflectionClass(__CLASS__);
		// \Tracy\Debugger::barDump(__CLASS__, "class");
		// $methods = get_class_methods("\App\AdminModule\Presenters\MaintenancePresenter");
		$methods = [];
		foreach ($class->getMethods() as $m) {
			if ($m->class == __CLASS__) {
				$methods[] = $m->name;
			}
		}
		// \Tracy\Debugger::barDump($methods, "methods");

		$handles = [];
		foreach (preg_grep("/handle(.*)/", $methods) as $index => $handle) {
			$name = substr($handle, 6);
			$handles[] = $name;
			unset($methods[$index]);
		}

		$unset = ["beforeRender", "actionDefault"];
		foreach ($methods as $i => $m) {
			if (in_array($m, $unset)) {
				unset($methods[$i]);
			}
		}
		if (($key = array_search("beforeRender", $methods)) !== false) {
		    unset($methods[$key]);
		}
		\Tracy\Debugger::barDump($methods, "methods");

		$this->template->handles = $handles;
		$this->template->methods = $methods;
	}

	public function generateContentsTitles(): void
	{
		foreach ($this->ContentsManager->getContents() as $cont) {
			$name = $cont->ref("type")->title . " " . $cont->id;
			$cont->update(["title" => $name]);
		}

		exit();
	}

	public function randomizeContentImage(): void
	{
		$images = $this->FilesManager->getImages(false);
		// \Tracy\Debugger::barDump($images->fetchAll());

		foreach ($this->ContentsManager->getContents() as $c) {
			$rand = $this->FilesManager->getRandomRows($images);
			// \Tracy\Debugger::barDump($rand);
			$c->update(["image" => $rand->id]);
		}

		exit();
	}

	public function generateContentAliases(): void
	{
		foreach ($this->ContentsManager->getContents() as $cont) {
			$this->AliasesManager->saveAlias("contents", $cont);
		}

		exit();
	}

  public function redefineFormRecordsCols(): void {
    foreach ($this->FormsManager->getFormsRecords() as $rec) {
      $data = $rec->data;
      $data = str_replace('"jmeno":', '"firstname":', $data);
      $data = str_replace('"prijmeni":', '"lastname":', $data);
      bdump($data, "data replaced");
      $rec->update(['data' => $data]);
    }
    exit();
  }

}