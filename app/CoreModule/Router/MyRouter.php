<?php

declare(strict_types=1);

namespace App\CoreModule\Router;

use App;
use Nette;
use Nette\Http\IRequest as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Monty\Helper;


class MyRouter implements \Nette\Routing\Router
{

	protected $ContentsManager, $NavigationsManager, $AliasesManager;
	protected $contentTypes, $langs;

	public function __construct(
		App\CoreModule\Model\ContentsManager $ContentsManager,
		App\CoreModule\Model\NavigationsManager $NavigationsManager,
		App\CoreModule\Model\AliasesManager $AliasesManager,
    	Nette\Localization\ITranslator $Translator
	)
	{
		$this->ContentsManager = $ContentsManager;
		$this->NavigationsManager = $NavigationsManager;
		$this->AliasesManager = $AliasesManager;
		$this->Translator = $Translator;

		$this->contentTypes = $this->ContentsManager->getContentTypes()->fetchAssoc("short->");
    	$this->langs = $this->NavigationsManager->getLangs(true)->fetchAssoc("code->");
		// bdump($this->contentTypes, "MyRouer contentTypes");
	}

	public function match(HttpRequest $httpReq): ?array
	{
		// bdump("router match");
		// bdump($httpReq, "httpRequest");
		$url = $httpReq->getUrl();
		// bdump($url, "url");
		$path = $url->getPath();
		// bdump($path, "path");
		$queryPars = $url->getQueryParameters();
		// bdump($queryPars, "query parameters");

		$navItem = $this->NavigationsManager->getItemBySlugTrace($path);
		// bdump($item, "end item");
		$pathArr = explode("/", $path);
		// bdump($pathArr, "pathArr");
		$itemId = end($pathArr);
		$item = $this->ContentsManager->getContent($itemId);
		// bdump($item, "item by url id");

		$basePath = $url->getBasePath();
		$path =	str_replace($basePath, "", $path);
		$parts = trim($path, "/");
		$parts = explode("/", $parts);
		// bdump($parts, "parts");

		// bdump($this->langs, "langs");
		// bdump($parts[0], "part 0");
		if (isset($this->langs[$parts[0]])) {
			$queryPars["lang"] = $parts[0];
		}

		// bdump($queryPars, "queryPars");

		if ($navItem) {
			$item = $navItem;
			// bdump($item, "item");
			$pars = $queryPars;
			$pars["nav_item_id"] = $item->id;

			if (!empty($item->route)) {
				// bdump($item->route, "route");
				$routePars = Helper::explodeRoute($item->route);
				// bdump($pars, "pars route");
				$pars = $pars + $routePars;
			}
			if (!empty($item->params)) {
				// bdump($item->params, "params");
				$itemPars = Json::decode($item->params, Json::FORCE_ARRAY);
				$pars = $pars + $itemPars;
			}

			if ($navItem->route == ":Core:Front:Custom:default") {
				$pars["template"] = $navItem->template;
			}

			// bdump($pars, "match pars");

			return $pars ? $pars : null;
		} else if ($item) {
			$type = $item->ref("type");
			$parentNavItem = $this->NavigationsManager->getParentItemByRoute(":Core:Front:ContentsList:contentsList", ["type" => $type->short]);

			$pars = $queryPars;
			$pars["presenter"] = "Core:Front:Contents";
			$pars["action"] = "contentDetail";
			$pars["id"] = $item->id;
			// bdump($type, "type");
			if ($parentNavItem) {
				$pars["parent_nav_item_id"] = $parentNavItem->id;
			}

			return $pars;
		} else {
			// bdump($queryPars, "match pars 2");
			// return $queryPars ? $queryPars : null;
			return null;
		}
	}

	public function constructUrl(array $params, UrlScript $refUrl): ?string
	{
		// bdump($refUrl, "refUrl");
		$url = $refUrl->getHostUrl() . $refUrl->getPath();
		$queryPars = $refUrl->getQueryParameters();
		// bdump($queryPars, "queryPars");
			
		// bdump("router construct");
		// bdump($params, "params");
		// bdump($url, "url");

		$pars = ArrayHash::from($params);
		// bdump($pars, "pars");

		$url .= !empty($pars["lang"]) ? $pars["lang"] . "/" : null;

		if (!empty($pars["nav_item_id"])) {
			// bdump(1);
			// bdump($pars["nav_item_id"], "pars nav_item_id");
			$slug = $this->NavigationsManager->getItemSlugTrace($pars["nav_item_id"]);

			// bdump($slug, "slug");
			$url .= $slug;

			// $queryPars = [
			// 	"lang" => !empty($params["lang"]) ? $params["lang"] : null
			// ];

			// return $url;
		} elseif ($pars->presenter == "Core:Front:Contents" && $pars->action == "contentDetail") {
			// bdump(2);
			// $alias = $this->AliasesManager->getAliasByItem("contents", $id);
			// $alias = $this->AliasesManager->getItemByAlias($id);
			$item = $this->ContentsManager->getContent($pars->id);

			if (!$item) return null;
			
			$type = $item->ref("type");
			// bdump($type, "type");
			// $url .= Strings::webalize($this->Translator->translate("global.content.types." . $type->short, 1)) . "/";

			$parentItem = $this->NavigationsManager->getParentItemByRoute(":Core:Front:ContentsList:contentsList", ["type" => $type->short]);
			if ($parentItem) {
				// bdump($parentItem, "parentItem");
				$url .= $this->NavigationsManager->getItemSlugTrace($parentItem->id) . "/";
			}

			$alias = $this->AliasesManager->getAliasByItem("contents", $item->id);
			if ($alias) {
				$url .= $alias->alias;
			} else {
				$url .= $item->id;
			}

			unset($pars["id"], $pars["presenter"], $pars["action"], $pars["parent_nav_item_id"]);
			$queryPars = $pars;

			// return $url;
		} else {
			// bdump(3);
			// $url .= $pars->presenter . "/" . $pars->action;
			// $pres = str_replace(":", replace, subject)
			// $url
			return null;
		}

		unset($queryPars["lang"]);

		if (!empty($pars->do)) {
			$queryPars["do"] = $pars->do;
		}

		// bdump($queryPars, "queryPars");
		// $url .= $queryPars ? "?" . implode("&", $queryPars) : null;
		if ($queryPars) {
			$i = 1;
			foreach ($queryPars as $par => $val) {
				// bdump($url, "url");
				$url .= $i == 1 ? "?" : "&";
				$url .= $par . "=" . $val;
				$i++;
			}
		}

		// bdump($url, "constructUrl url");
		return $url;
	}
}