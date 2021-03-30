<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;
use Google_Service_Oauth2;


class UsersPresenter extends FrontPresenter
{

	use App\CoreModule\Traits\UsersTrait;
	

	public function actionLoginForm(): void
	{
		$template = $this->template;
	}

	public function actionLogout(): void
	{	
		if ($this->User->isLoggedIn()) {
			$vocative = $this->User->getIdentity()->vocative;
			$this->User->logout(true);
			// $this->flashMessage("Měj se hezky $vocative :)", "alert-warning");
			$this->bigMessage("Měj se hezky $vocative :)");
		}

		$this->redirect($this->homeRoute);
	}

	public function actionRegisterForm(): void
	{
		$this->isRegistrationAllowed(true);

		$f = $this->getComponent("userForm");
		unset($f["username"], $f["short"], $f["vocative"], $f["gmail"]);

		$f->onSuccess[] = function($f, $v) {
			try {
				$id = $this->UsersManager->saveUser($v);
				$user = $this->UsersManager->getUser($id);
				\Tracy\Debugger::barDump($user, "user");

				$user->update(["active" => false]);

				// $mail = $this->Mailer->addEmail();
				// $mail->setSubject("Potvrzení registrace");
				// $mail->addTo($user->email);
				// $mail->setTemplate("confirmUserRegEmail.latte", [
				// 	"link" => $this->link("//:Front:Users:userRegConfirm", $user->token)
				// ]);
				// $mail->send();

				$this->messagePage("Díky za registraci $user->vocative", "Do emailu jsme Vám zaslali další pokyny.");
			} catch (\Exceptions\DuplicateEmailException $e) {
				$this->flashMessage("E-mail již existuje", "alert-danger");
			}
		};
	}

}