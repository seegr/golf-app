<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App;
use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Monty\Navigation;
use Monty\Modal;
use Monty\Form;
use Monty\Helper;
use Nette\Database\Table\ActiveRow;
use App\CoreModule\FormsModule\Components\FormsFactory as FormsFormsFactory;
use App\CoreModule\Model\ClientUniqueId;
use Tracy\Debugger;

// use Contributte\MenuControl\UI\IMenuComponentFactory;
// use Contributte\MenuControl\UI\MenuComponent;


class BasePresenter extends Nette\Application\UI\Presenter
{
	use App\CoreModule\Traits\UsersTrait;
	use App\CoreModule\Traits\FilesTrait;
	use App\CoreModule\AdminModule\Traits\ContentsTrait;


	/** @var App\CoreModule\Model\BaseManager @inject */
	public $BaseManager;

	/** @var App\CoreModule\Model\NavigationsManager @inject */
	public $NavigationsManager;

	/** @var App\CoreModule\Model\ContentsManager @inject */
	public $ContentsManager;

	/** @var App\CoreModule\Model\EventsManager @inject */
	public $EventsManager;

    /** @var \App\CoreModule\Model\CategoriesManager @inject */
    public $CategoriesManager;

	/** @var App\CoreModule\Components\FormsFactory @inject */
	public $FormsFactory;

	/** @var App\CoreModule\FormsModule\Components\FormsFactory @inject */
	public $FormsFormsFactory;

	/** @var App\CoreModule\Model\FilesManager @inject */
	public $FilesManager;

	/** @var App\CoreModule\Model\UsersManager @inject */
	public $UsersManager;

	/** @var App\CoreModule\Model\AliasesManager @inject */
	public $AliasesManager;
    
    /** @var Nette\Localization\ITranslator @inject */
    public $Translator;
    
    /** @var App\CoreModule\Model\SettingsManager @inject */
    public $SettingsManager;

    /** @var ClientUniqueId @inject */
    public ClientUniqueId $clientUniqueId;

	// * @var IMenuComponentFactory @inject 
	// public $MenuFactory;


	const
		APP_ROOT = __DIR__ . "/../../",
		ADMIN_ROOT = self::APP_ROOT . "/AdminModule/",
		FRONT_ROOT = self::APP_ROOT . "/FrontModule/",
		WWW_DIR = __DIR__ . "/../../../www/",
		FORMS_TEMPLATES = __DIR__ . "/../templates/forms/",
		ADMIN_FORMS_TEMPLATES = __DIR__ . "/../AdminModule/templates/forms/",
		FRONT_FORMS_TEMPLATES = __DIR__ . "/../FrontModule/templates/forms/",
		TEMPLATE_BLOCKS = __DIR__ . "/../templates/blocks.latte",
		// HOME_ROUTE = ":Front:Homepage:homepage",
		// LOGIN_ROUTE = ":Core:Front:Users:loginForm",
		DATE_FORMAT = "j.n.Y",
		DATETIME_FORMAT = "j.n.Y H:i",
		TEMPLATE_ROOT_LAYOUT = __DIR__ . "/../templates/@layout.latte",
		TEMPLATE_FRONT_LAYOUT = __DIR__ . "/../FrontModule/templates/@layout.latte",
		TEMPLATE_ADMIN_LAYOUT = __DIR__ . "/../AdminModule/templates/@layout.latte",
		CORE_DIR = __DIR__ . "/../",
		CORE_ADMIN_DIR = __DIR__ . "/../AdminModule/";


	protected $User;
	protected $Forms;
	protected $navItem;
	public $notAllowedMsg = "Huš! Na tuhle stránku nemáš přístup!";
	public $bodyClass = [];
	public $appName;
	public $maintenance;
	public $homeRoute;
	protected $_adminLayout;
	protected $_frontLayout;

	/** @persistent */
	public $lang;



	public function injectServices() {
		$this->User = $this->getUser();
	}


	public function startup(): void
	{
		parent::startup();
		// bdump($this, "presenter");
		// bdump($this->name, "name");
		// bdump($this->getParameters(), "parameters");
		
		// $locales = \ResourceBundle::getLocales('');
		// bdump($locales);
		// $locale = setlocale(LC_ALL, "cs_CZ", "cs_cz", "cs");
		// bdump($locale, "locale");
		
		$template = $this->template;

		$this->Forms = $this->FormsFactory;
        $this->clientUniqueId->set();
		$this->defineVariables();
		$this->checkPermissions();
		$this->loginStartup();
		$this->homeRoute = $this->getHomeRoute();
		$this->setLangs();
	}

	public function beforeRender(): void
	{
		// bdump("beforerender");
		parent::beforeRender();

		$template = $this->template;

		$this->setTemplatePars();
		$this->setBodyClass();
		$this->setMainNavigation();
		$this->navItem = $this->getCurrentNavItem();
		// $templateFile = $this->getTemplateFile();
		// bdump($templateFile, "templateFile");
		// $template->setFile($templateFile);

		// bdump($this->formatTemplateFiles(), "formatTemplateFiles");
	}

	public function afterRender(): void
	{
		// bdump("afterRender");
		parent::afterRender();
		$this->getBigMessage();
	}


	public function createComponentMainNav(): Navigation
	{
		$nav = new Navigation($this);

		$nav->setDepth(2);

		// $nav->setLinksGenerator();

		return $nav;
	}

	public function createComponentAdminNav(): Navigation
	{
		$nav = new Navigation;

		$nav->setLinksGenerator();

		$nav->addItem(null, $this->link($this->getHomeRoute()))->setIcon("fad fa-home");

		$content = $nav->addItem("Obsah", "");
			foreach ($this->ContentsManager->getContentTypes() as $type) {
				$title = $this->Translator->translate("global.content.types." . $type->short, 2);
				$content->addItem($title, $this->link(":Core:Admin:ContentsList:contentsList", $type->short));
			}
			// $content->addItem("Články", $this->link(":Core:Admin:Contents:contentsList", "article"));
			// $content->addItem("Akce", $this->link(":Core:Admin:Contents:contentsList", "event"));
			// $content->addItem("Galerie", $this->link(":Core:Admin:Contents:contentsList", "gallery"));
		
		$nav->addItem("Uživatelé", $this->link(":Core:Admin:Users:usersList"));
		$nav->addItem("Navigace", $this->link(":Core:Admin:Navigations:navigationsList"));

		$nav->addClass("navbar-dark bg-dark");

		return $nav;
	}

	public function createComponentLoginFormModal(): Modal
	{
		$modal = new \Monty\Modal;

		$modal->setTitle("Přihlášení");
		$modal->setContent($this->getFormTemplatePath("loginForm"));

		return $modal;
	}


	//** global functions move them out?
	public function templateToString($templatePath): string
	{
		return $this->template->getLatte()->renderToString($templatePath);
	}

	public function defineVariables(): void
	{
		$class = new \ReflectionClass(__CLASS__);
		$consts = $class->getConstants();
		// bdump($consts, "constants");

		foreach ($consts as $name => $val) {
			if (!defined($name)) define($name, $val);
		}

		$template = $this->template;
		$template->appName = $this->BaseManager->getSetting("appName");
		$template->maintenance = $this->BaseManager->maintenance;
		$template->_blocks = self::TEMPLATE_BLOCKS;
		$template->_contentBlocks = self::CORE_DIR . "FrontModule/templates/content-blocks.latte";
	}

	public function getTemplateFile($route = null): ?string
	{
		$route = $route ? $route : $this->name . ":" . $this->action;
		// bdump($route, "route");
		$route = explode(":", $route);
		// bdump($route, "route");

		$roots = [self::FRONT_ROOT, self::APP_ROOT];

		$len = count($route);
		// bdump($len, "len");
		foreach ($roots as $root) {
			$path = $root;
			$i = 1;
			$templateDir = "";
			foreach ($route as $part) {
				if ($i < ($len - 1)) {
					// $path .= "/" . $part;
					$path .= "/" . ucfirst($part) . "Module";
				} else {
					if ($i != $len) {
						$path .= "/templates/" . ucfirst($part);
					} else {
						$path .= "/" . $part;
					}
				}

				$i++;
			}

			$path .= ".latte";

			// bdump($path, "path");
			if (file_exists($path)) {
				return $path;
			}
		}

		// bdump($path, "path");
		return null;
	}

	public function setBaseParameters($config): void
	{
		$this->config = ArrayHash::from($config);
	}

	public function getFormTemplatePath($form): ?string
	{
		// $path = explode(":", $this->name);
		// bdump($path, "path");

		$form = $form ? $form : $this->action;
		// bdump($form, "form");

		$file = $form . ".latte";

		$dirs = [
			self::APP_ROOT . "templates/forms/",
			self::APP_ROOT . "CoreModule/templates/forms/",
			self::ADMIN_FORMS_TEMPLATES,
			self::FRONT_FORMS_TEMPLATES
		];

		// bdump($dirs, "dirs");

		foreach ($dirs as $dir) {
			$path = $dir . $file;
			// bdump($path, "path");
			if (is_file($path)) {
				return $path;
			}
		}

		return null;
	}

	public function getFormTemplate($form = null): ?string
	{
		return $this->getFormTemplatePath($form);
	}

	public function getForm($name = null): Form
	{
		$name = $name ? $name : $this->action;

		return $this->getComponent($name);
	}

	public function flashMessage($message, string $type = "success", $stay = null): \stdClass
	{
		$id = $this->getParameterId('flash');
		$messages = $this->getPresenter()->getFlashSession()->$id;
		if (strpos($type, "alert-") !== false) {
			$type = explode("-", $type);
			$type = $type[1];
		}

		$messages[] = $flash = (object) [
			'message' => $message,
			'type' => $type,
			"stay" => $stay
		];
		$this->getTemplate()->flashes = $messages;
		$this->getPresenter()->getFlashSession()->$id = $messages;

		$this->redrawControl("flashes");
		
		return $flash;
	}

	public function modal($settings): void
	{
		if ($settings == "hide") {
			$this->payload->modal = "hide";
			$this->redrawControl("modal");
			return;
		}

		$this->payload->modal = $settings;
		bdump($this->payload, "payload");
		
		if (!empty($setting["class"])) $this->template->_modalClass = $setting["class"];

		if ($this->isAjax()) {
			$this->redrawControl("modal");
			// $this->payload->modal = "hide";
		} else {
			$this->template->modal = json_encode($settings);
		}
	}

	public function bigMessage($text, $fixed = false): void
	{
		bdump("bigMessage");
		$sess = $this->getSession("bigMessage");
		$template = $this->template;

		$data = [
			"text" => $text,
			"fixed" => $fixed
		];

		bdump($data, "data");
		$sess->data = $data;
		$template->bigMessage = $data;

		// if ($this->isAjax()) {
		// 	$this->redrawControl("bigMessage");
		// } else {
		// 	$this->template->bigMessage = json_encode($settings);
		// }
	}

	public function getBigMessage(): void
	{
		$sess = $this->getSession("bigMessage");
		// bdump($sess, "bigMsg sess");

		$data = $sess->data;
		// bdump($data, "bigMsg data");

		if ($data) {
			// bdump("jo bigMsg");
			$this->template->bigMessage = $data;
			$this->redrawControl("bigMessage");

			unset($sess->data);
		} else {
			// bdump("ne bigMsg");
		}
	}

	public function defineResources(): void
	{
		$Auth = $this->getUser()->getAuthorizator();
	}

	public function checkPermissions() {
		$user = $this->getUser();

		if ($this->name != "Core:Front:Users" && !in_array($this->action, ["login", "loginForm", "relogin", "logout"])) {
			// bdump("storing backlink");
			$this->storeBacklinkRequest();
		}
		if (!$user->isAllowed($this->getName(), $this->getAction())) {
        	#throw new Nette\Application\ForbiddenRequestException;
        	$this->flashMessage($this->notAllowedMsg, "alert-warning");

        	$this->redirect(":Core:Front:Users:loginForm");
    	}
	}

	public function storeBacklinkRequest(): void
	{
		$session = $this->getSession("login");

		$session->backlink = $this->storeRequest();
	}

	public function getBacklink(): ?string
	{
		$session = $this->getSession("login");

		if (isset($session->backlink_custom)) {
			return $session->backlink_custom;
		} elseif (isset($session->backlink)) {
			return $session->backlink;
		} else {
			return null;
		}
	}

	public function messagePage($heading, $message, $actions = []): void
	{ // action [title => link]
		$session = $this->getSession("message_page");

		$session->heading = $heading;
		$session->message = $message;
		$session->actions = $actions;
		$session->template = null;

		$this->redirect(":Front:Custom:messagePage");
	}

	public function setMainNavigation(): void
	{
		$mainNav = $this["mainNav"];

		$navigation = $nav = $this->NavigationsManager->getNavigation("main");

		$mainNav->addClass($nav->short);
		$items = $this->NavigationsManager->getNavigationTree("main", null, true);
		// bdump($items, "items");
		$mainNav->addItems($items);

		// $mainNav->addBrand($this->link($this->homeRoute))->setIcon("fad fa-home");
		if ($this->User->isLoggedIn()) {
			$mainNav->addItem(null, $this->link(":Core:Front:User:profile"))->setIcon("fad fa-user")->setPosition("right");
			$mainNav->addItem(null, $this->link(":Core:Admin:Dashboard:"))->setIcon("fad fa-cogs")->setPosition("right");
		} else {
			$modalId = $this["loginFormModal"]->getId();
			$loginBtn = $mainNav->addItem(null, null)
				->addAttributes([
					"data-toggle" => "modal",
					"data-target" => "#" . $modalId
				])
				->setIcon("fad fa-lock-alt")->setPosition("right");	
			if ($this->action == "loginForm") {
				$loginBtn->class[] = "collapse";
			}
		}

		if ($navigation->brand_image) {
			// bdump($nav, "nav");
			$brand = $mainNav->addBrand($this->getHomeRoute());
			// bdump($brand, "brand");
			$brand->setImage($navigation->brand_image);
		}
		
		if ($icon = $this->SettingsManager->getSetting("nav_breadcrumb_seperator_icon")) {
			$mainNav->setBreadcrumbIcon($icon);
		}
	}

	public function setBodyClass(): void
	{
		$template = $this->template;

		$bodyClass = $this->bodyClass;
		// bdump($this->name, "name");
		$routeClass = explode(":", Strings::lower($this->name));
		array_pop($routeClass);
		$bodyClass = $bodyClass + $routeClass;
		$bodyClass[] = $this->view;
		$bodyClass[] = Helper::camelToDash($this->view);
		// bdump($bodyClass, "bodyClass");

		$bodyClass = array_values(array_unique($bodyClass));
		// bdump($bodyClass, "bodyClass");

		$this->bodyClass = $bodyClass;
	}

	public function translate($str): ?string
	{
		return $this->Translator->translate($str);
	}

	public function getHomeRoute(): string
	{
		$navHome = $this->NavigationsManager->getNavigationsItems()->where("home", true)->fetch();

		if ($navHome) {
			$route = $navHome->route;
		} else {
			$route = $this->BaseManager->homeRoute;
		}

		// bdump($route, "route");
		return $route;
	}

	// public function link(string $destination, $args = []): string
	// {
	// 	// bdump($destination, "linkGen destination");
	// 	// bdump($args, "args");

	// 	try {
	// 		$args = func_num_args() < 3 && is_array($args)
	// 			? $args
	// 			: array_slice(func_get_args(), 1);			return $this->getPresenter()->createRequest($this, $destination, $args, 'link');

	// 	} catch (InvalidLinkException $e) {
	// 		return $this->getPresenter()->handleInvalidLink($e);
	// 	}
	// }

	public function formatTemplateFiles(): array
	{
		// bdump("formatTemplateFiles...");
		[, $presenter] = Nette\Application\Helpers::splitName($this->getName());
		// bdump($presenter, "presenter");
		$dir = dirname(static::getReflection()->getFileName());
		// bdump($dir, "dir");
		$dir = is_dir("$dir/templates") ? $dir : dirname($dir);
		// bdump($dir, "dir");
		// $projectDir = $dir;
		// $projectDir = str_replace("app", "projects/$this->projectAlias/www", $projectDir);
		return [
			// "$projectDir/templates/$presenter/$this->view.latte",
			// "$projectDir/templates/$presenter.$this->view.latte",
			"$dir/templates/$presenter/$this->view.latte",
			"$dir/templates/$presenter.$this->view.latte",
		];
	}

	public function setTemplatePars(): void
	{
		$template = $this->template;

		$template->_loader = false;
		$template->_layout = self::TEMPLATE_ROOT_LAYOUT;
		$this->setTemplateLayouts();
		$template->_coreAdminLayout = self::TEMPLATE_ADMIN_LAYOUT;
		$template->_coreFrontLayout = self::TEMPLATE_FRONT_LAYOUT;
		$template->_adminLayout = $this->_adminLayout;
		$template->_frontLayout = $this->_frontLayout;
		$template->_homeRoute = $this->homeRoute;
		$template->_customFormTemplate = FormsFormsFactory::CUSTOM_FORM_TEMPLATE;
	}

	public function getNavigationItems(): array
	{
		return $this->NavigationsManager->getNavigationTree("main", null, true);
	}

	public function setHtmlMeta()
	{
		$template = $this->template;

		$template->metaDesc = $this->SettingsManager->getSetting("meta_desc") ? $this->SettingsManager->getSetting("meta_desc") : null;
		$template->metaKeys = $this->SettingsManager->getSetting("meta_keys") ? $this->SettingsManager->getSetting("meta_keys") : null;
		$template->ogImage = $this->SettingsManager->getSetting("og_image") ? $this->SettingsManager->getSetting("og_image") : "images/bee_big.jpg";
	}
	
	public function isInHomepage(): bool
	{
		return $this->isLinkCurrent($this->getHomeRoute());
	}

	public function getSetting($atr): ?string
	{
		return $this->BaseManager->getSetting($atr);
	}

	public function setLangs(): void
	{
		$template = $this->template;

		// $pars = $this->getParameters();
		// bdump($pars, "pars");
		// bdump($this->lang, "lang");

		$langs = $template->langs = $this->BaseManager->getLangs(true);

		if (!$this->lang) {
			$lang = $this->BaseManager->getDefaultLang();
		} else {
			$lang = $this->BaseManager->getLang($this->lang);
		}

		if (count($langs) > 1) {
			$this->lang = $lang->code;
		}

		$template->lang = $lang;
	}

	public function getComponentsScripts(): array
	{
		$coms = $this->getComponents();

		$arr = [];
		foreach ($coms as $com) {
			bdump($com, "com");
			bdump(method_exists($com, "renderScripts"), "renderScripts exists");

			if (method_exists($com, "renderScripts")) {
				$arr[] = $com;
			}
		}

		return $arr;
	}

	public function getCurrentNavItem(): ?ActiveRow
	{
		if ($navItem = $this["mainNav"]->getActiveItem()) {
			return $this->NavigationsManager->getNavigationItem($navItem);
		} else {
			return null;
		}
	}

	public function setTemplateLayouts(): void
	{
		foreach (["admin", "front"] as $module) {
			$dirs = [
				self::APP_ROOT . ucfirst($module) . "Module/" . "templates/",
				self::APP_ROOT . "CoreModule/" . ucfirst($module) . "Module/" . "templates/",
			];
	
			foreach ($dirs as $dir) {
				$path = $dir . "@layout.latte";
				if (file_exists($path)) {
					$varName = "_" . $module . "Layout";
					$this->$varName = $path;
				}
	
				break;
			}
		}
	}

}
