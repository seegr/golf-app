<?php

namespace App\CoreModule\Components;

use Nette;
use App;
use Nette\Utils\ArrayHash;
use Monty\Form;
use Monty\FormValidators;
use Monty\Helper;
use App\CoreModule\Model\ContentsManager;
use App\CoreModule\Model\EventsManager;


class FormsFactory extends BaseFormsFactory
{
	
	use Nette\SmartObject;

	private $User, $UsersManager, $ContentsManager, $NavigationsManager, $FormsManager, $Translator;

	private $maxFileSize;
	private $maxFileSizeStr;

	public $fullWidthCols = "12";
	public $rowClass = "row";


	public function __construct(
		Nette\Security\User $User,
		App\CoreModule\Model\UsersManager $UsersManager,
		App\CoreModule\Model\ContentsManager $ContentsManager,
		App\CoreModule\Model\NavigationsManager $NavigationsManager,
		App\CoreModule\FormsModule\Model\FormsManager $FormsManager,
		Nette\Localization\ITranslator $Translator
	)
	{
		$this->User = $User;
		$this->UsersManager = $UsersManager;
		$this->ContentsManager = $ContentsManager;
		$this->NavigationsManager = $NavigationsManager;
		$this->FormsManager = $FormsManager;
	}

	public function contentForm($type): Form
	{
		$form = $this->newForm();
		$form->setTranslator($this->Translator);

		$form->addHidden("id");
		$form->addHidden("type", $type);
		$form->addText("title", "Název")->setRequired("Vyplňte název");
		$form->addText("short", "Alias");
		$form->addText("heading", "Nadpis");
		$form->addSelect("category", "Kategorie")->setRequired("Vyber kategorii");
		// $form->addMultiSelect("categories", "Kategorie");
		$form->addMultiSelect("tags", "Tagy");
		// $form->addSelect("user", "Autor", $this->UsersManager->getEmps(null, true)->fetchPairs("id", "fullname"))->setRequired("Vyber autora");
		$form->addHidden("user", $this->User->id);
		// $form->addMultiSelect("contacts", "Kontakt", $this->getMultiSelectPrompt() + $this->EmpsManager->getEmps(null, true)->fetchPairs("id", "fullname"));
		// $form->addMultiSelect("editors", "Editoři", $this->getMultiSelectPrompt() + $this->UsersManager->getEmps(null, true)->fetchPairs("id", "fullname"));
		$form->addUpload("image", "Úvodní obrázek")->setRequired(false);
		$form->addUpload("header_image", "Obrázek hlavičky")->setRequired(false);
		$form->addCheckbox("default_header_image", "Zobrazit výchozí obrázek v hlavičce");
		$form->addMultiUpload("attachments");
		$sources = $form->addMultiplier("sources", function($container, $form) {
			$container->addText("title", "Název");
			$container->addText("url", "Odkaz")->addRule(Form::URL)->setRequired(false);
		}, 0);
		$sources->addCreateButton("Přidat")->addClass("btn btn-sm btn-primary ajax");
		$sources->addRemoveButton("Smazat")->addClass("btn btn-sm btn-danger ajax");

		$form->addTextArea("short_text", "Krátký text");
		$form->addTextArea("text", "Text");
		$form->addUpload("file", "Soubor");
		$form->addCheckbox("active", "Publikováno")->setDefaultValue(true);
		// $form->addCheckbox("in_news", "Zobrazit v aktualitách");
		$form->addText("created", "Vytvořeno");
		$form->addText("start", "Publikovat od");
		$form->addText("end", "Publikovat do");
		// $form->addText("url")->addRule(Form::URL, "Neplatný url odkaz")->setRequired(false);
		$form->addTextArea("meta_keys", "Meta keys");
		$form->addTextArea("meta_desc", "Meta desc");

		$form->addSelect("registration", "Registrace", [null => "- Bez registrace -"] + EventsManager::EVENT_REGISTRATION);
		$form->addSelect("reg_form", "Formulář", [null => "- Registrační formulář -"] + $this->FormsManager->getForms()->fetchPairs("id", "title"));
		$form->addInteger("reg_part", "Účastníků")
			->setDefaultValue(0)
			->addRule(Form::MIN, "Minimum je 0 (neomezeně)", 0);
        $form->addInteger("reg_sub", "Náhradníků")
            ->setDefaultValue(0)
            ->addRule(Form::MIN, "Minimum je 0 (neomezeně)", 0);

		$customFields = $this->ContentsManager->getContentCustomFields($type);
		// bdump($customFields, "customFields");

		$group = $form->addContainer("custom_fields");
		foreach ($customFields as $name => $field) {
			$field = ArrayHash::from($field);
//            bdump($field, "field");
			$title = $field->title;
			$type = $field->type;

			if (in_array($type, ["text"])) {
				$control = $group->addText($name, $title);
			} else if ($type == "tel") {
				$control = $group->addTel($name, $title);
			} else if ($type == "url") {
				$control = $group->addUrl($name, $title);
			} else if ($type == "price") {
				$control = $group->addPrice($name, $title);
			} else if ($type == "select") {
                $control = $group->addSelect($name, $title, (array)$field->options);
            }

			// PHP 8
			// $control = match($type) {
			// 	"text" => $group->addText($name, $title),
			// 	"tel" => $group->addTel($name, $title),
			// 	"url" => $group->addUrl($name, $title),
			// 	"price" => $group->addPrice($name, $title),
			// };
			
			$control->setRequired(!empty($field->required) ? $field->required : false);
		}

		return $form;
	}

	public function userForm(): Form
	{
		$form = $this->newForm();

		$form->addHidden("id");
		$form->addText("firstname", "Jméno")->setRequired();
		$form->addText("lastname", "Příjmení")->setRequired();
		$form->addText("short", "Zkratka");
		$form->addText("pre_title", "Titul");
		$form->addText("suf_title", "Titul za jménem");
		$form->addText("username", "Uživatelské jméno");
		$form->addText("vocative", "Oslovení");
		$form->addPassword("password", "Heslo")->setRequired();
		$form->addPassword("password_again", "Heslo pro kontrolu")
			->setRequired()
			->addRule(Form::EQUAL, "Heslo se neshoduje", $form["password"])
			->addRule($form::MIN_LENGTH, "Heslo musí mít minimálně 7 znaků", 7);
		$form->addEmail("email", "E-mail")->setRequired();
		$form->addEmail("gmail", "Gmail");
		$form->addUpload("image", "Fotka")->setRequired(false);
		$form->addText("tel", "Telefon");

		return $form;
	}

	public function loginForm(): Form
	{
		$form = new Form;

		$form->addText("user", "Uživatel")->setRequired();
		$form->addPassword("password", "Heslo")->setRequired();
		$form->addCheckbox("remember", "Pamatuj si mě");
		$form->addSubmit("login", "Přihlásit");

		return $form;
	}

	public function userRolesForm(): Form
	{
		$form = $this->newForm();

		$roles = $this->UsersManager->getRoles()->where("short != ?", "guest")->fetchPairs("id", "title");
		bdump($roles, "roles");

		// $form->addHidden("id");
		$form->addMultiSelect("roles", "Role", $roles);

		return $form;
	}

	public function navigationItemForm(): Form
	{
		$form = $this->newForm();

		$form->addText("title");
		$form->addText("short");
		$form->addText("route");
		$form->addText("url");
		$form->addText("params");
		$form->addText("class");
		$form->addText("template");
		$form->addSelect("content", "Obsah");
		$form->addSelect("contentsList", "Výpis obsahu");
		$form->addSelect("item_alias", null)->setPrompt("- Vyber alias nav itemu -");
		$form->addCheckbox("active")->setDefaultValue(true);
	
		$form->addHidden("id");
		$form->addHidden("navigation");
		$form->addHidden("parent");

		return $form;
	}

	public function eventDateRepeatForm() {
		$form = $this->newForm();

		$form->addHidden("parent");
		$form->addText("start", "Interval od")->setRequired(false);
		$form->addText("end", "Interval do")->setRequired(false)
			->addRule(FormValidators::CHECK_START_END_DATE, "Konec musí být později než začátek", $form["start"]);
		/*$form->addInteger("repeats")
			->addCondition(Form::BLANK)
			->addRule(FormValidators::EVENT_REPEAT_END_OR_REPEATS, "Musíš zadat konec nebo počet opakování", $form["end"]);*/
		$form->addText("dates");
		$form->addCheckbox("regform_settings", "Podědit nastavení registrace")->setDefaultValue(true);

		foreach (Helper::getWeek() as $dayNo => $day) {
			$days[$dayNo] = $day->label;
		}

		$form->addRadioList("type", null, [
			"dates" => "Termíny",
			"days" => "Opakovat ve dny",
			"weekly" => "Týdně",
			"monthly" => "Měsíčně"
		])->setDefaultValue("dates");

		$form->addCheckboxList("days", "Opakovat ve dny", $days);

		return $form;
	}

	public function eventDateForm()
	{
		$form = $this->newForm();

		$form->setAutocomplete(false);

		$form->addHidden("content");
		$form->addDateTime("start", "Začátek");
		$form->addDateTime("end", "Konec");

		return $form;
	}


}