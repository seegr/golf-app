<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use Nette;


class UserPresenter extends FrontPresenter
{

	public function actionUserForm(): void
	{
		$form = $this->getForm();

		$data = $this->UsersManager->getUser($this->getUser()->id)->toArray();
		unset($data["password"]);
		
		$form->setDefaults($data);
	}

}