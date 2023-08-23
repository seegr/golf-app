<?php

declare(strict_types=1);

namespace App\CoreModule\AdminModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;
use Monty\Form;


class UsersPresenter extends AdminPresenter
{

	use App\CoreModule\Traits\UsersTrait;


	public function actionUserForm($id): void
	{
		$form = $this->getForm();

		if ($id) {
			$user = $this->UsersManager->getUser($id);
			bdump($user, "user");
			$form->setDefaults($user);
			$form["password"]->setRequired(false);
			$form["password_again"]->setRequired(false);
		}

		$form = $this->getForm();
		// bdump($form, "form");

		$form["cancel"]->onClick[] = function() {
			$this->redirect("usersList");
		};
		$form["save"]->onClick[] = function($f, $v) {
			$this->saveUser($v);
			$this->redirect("usersList");
		};

		$form["save_stay"]->onClick[] = function($f, $v) {
			$id = $this->saveUser($v);
			$this->redirect("this", $id);
		};
	}

	public function actionUserRolesForm($id): void
	{
		$this->template->usr = $this->getUserData($id);

		$form = $this->getForm();

		$form->setDefaults([
			"roles" => $this->UsersManager->getUserRoles($id)->fetchPairs(null, "role")
		]);

		$form["save"]->onClick[] = function($f, $v) use ($id) {
			$this->UsersManager->userRolesSave($id, $v->roles);
			$this->redirect("usersList");
		};
		$form["save_stay"]->onClick[] = function($f, $v) use ($id) {
			$this->UsersManager->userRolesSave($id, $v->roles);
			$this->redirect("this");
		};
		$form["cancel"]->onClick[] = function($f, $v) {
			$this->redirect("usersList");
		};
	}


	public function createComponentUsersList(): DataGrid
	{
		$list = new DataGrid;

		$list->setDataSource($this->UsersManager->getUsers());
		$list->addColumnText("fullname", "Jméno")->setFilterText();
		$list->addColumnText("email", "E-mail")->setFilterText();
		$list->addColumnDateTime("registered", "Registrace")->setFormat(self::DATETIME_FORMAT);
		$list->addAction("edit", "", "userForm")->setClass("fas fa-pencil btn btn-warning");
		$list->addAction("roles", "", "userRolesForm")->setClass("fas fa-key btn btn-primary");

		return $list;
	}

	public function createComponentUserRolesForm(): Form
	{
		return $form = $this->Forms->userRolesForm();
	}

}