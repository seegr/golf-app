<?php

declare(strict_types=1);

namespace App\CoreModule\AdminModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;


class CustomPresenter extends AdminPresenter
{

	public function actionTest(): void
	{
		// $this->flashMessage("test message");
		// $this->flashMessage("test message", "warning", true);
		// $this->bigMessage("test tralala", true);

	}

	public function handleTest() {
		$this->flashMessage("bum! :)");
	}

	public function handleTest2() {
		$this->flashMessage("bum 2! :)", "orange");
	}

	public function handleTest3() {
		$this->flashMessage("bum 3! :)", "blue");
	}

	public function handleTest4() {
		$this->flashMessage("bum 4! :)", "red");
		// $this->payload->redirect = true;
		// $this->redirect("this");
	}

}