<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use Nette;
use Nette\Utils\Json;
use Nette\Utils\ArrayHash;


class ApiPresenter extends FrontPresenter
{

	public function actionDefault($act) {
		$data = $this->$act();

		$this->sendJson(Json::encode($data));
		exit();
	}

	public function getapplinks()
	{
		$links = [
			"basePath" => $this->template->basePath,
			"baseUrl" => $this->template->baseUrl,
			"filePicker" => $this->link(":Core:Admin:FilesList:filesList"),
		];

		return $links;
	}

}