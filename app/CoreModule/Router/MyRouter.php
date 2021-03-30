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
		// \Tracy\Debugger::barDump($this->contentTypes, "MyRouer contentTypes");
	}

	public function match(HttpRequest $httpReq): ?array
	{
		// \Tracy\Debugger::barDump("router match");
		// \Tracy\Debugger::barDump($httpReq, "httpRequest");
		$url = $httpReq->getUrl();
		// \Tracy\Debugger::barDump($url, "url");
		$path = $url->getPath();
		// \Tracy\Debugger::barDump($path, "path");
		$queryPars = $url->getQueryParameters();
		// \Tracy\Debugger::barDump($queryPars, "query parameters");

		$navItem = $this->NavigationsManager->getItemBySlugTrace($path);
		// \Tracy\Debugger::barDump($item, "end item");
		$pathArr = explode("/", $path);
		// \Tracy\Debugger::barDump($pathArr, "pathArr");
		$itemId = end($pathArr);
		$item = $this->ContentsManager->getContent($itemId);
		// \Tracy\Debugger::barDump($item, "item by url id");

		$basePath = $url->getBasePath();
		$path =	str_replace($basePath, "", $path);
		$parts = trim($path, "/");
		$parts = explode("/", $parts);
		// \Tracy\Debugger::barDump($parts, "parts");

		// \Tracy\Debugger::barDump($this->langs, "langs");
		// \Tracy\Debugger::barDump($parts[0], "part 0");
		if (isset($this->langs[$parts[0]])) {
			$queryPars["lang"] = $parts[0];
		}

		// \Tracy\Debugger::barDump($queryPars, "queryPars");

		if ($navItem) {
			$item = $navItem;
			// \Tracy\Debugger::barDump($item, "item");
			$pars = $queryPars;
			$pars["nav_item_id"] = $item->id;

			if (!empty($item->route)) {
				// \Tracy\Debugger::barDump($item->route, "route");
				$routePars = Helper::explodeRoute($item->route);
				// \Tracy\Debugger::barDump($pars, "pars route");
				$pars = $pars + $routePars;
			}
			if (!empty($item->params)) {
				// \Tracy\Debugger::barDump($item->params, "params");
				$itemPars = Json::decode($item->params, Json::FORCE_ARRAY);
				$pars = $pars + $itemPars;
			}

			if ($navItem->route == ":Core:Front:Custom:default") {
				$pars["template"] = $navItem->template;
			}

			// \Tracy\Debugger::barDump($pars, "match pars");

			return $pars ? $pars : null;
		} else if ($item) {
			$type = $item->ref("type");
			$parentNavItem = $this->NavigationsManager->getParentItemByRoute(":Core:Front:ContentsList:contentsList", ["type" => $type->short]);

			$pars = $queryPars;
			$pars["presenter"] = "Core:Front:Contents";
			$pars["action"] = "contentDetail";
			$pars["id"] = $item->id;
			// \Tracy\Debugger::barDump($type, "type");
			if ($parentNavItem) {
				$pars["parent_nav_item_id"] = $parentNavItem->id;
			}

			return $pars;
		} else {
			// \Tracy\Debugger::barDump($queryPars, "match pars 2");
			// return $queryPars ? $queryPars : null;
			return null;
		}
	}

	public function constructUrl(array $params, UrlScript $refUrl): ?string
	{
		// \Tracy\Debugger::barDump($refUrl, "refUrl");
		$url = $refUrl->getHostUrl() . $refUrl->getPath();
		$queryPars = $refUrl->getQueryParameters();
		// \Tracy\Debugger::barDump($queryPars, "queryPars");
			
		// \Tracy\Debugger::barDump("router construct");
		// \Tracy\Debugger::barDump($params, "params");
		// \Tracy\Debugger::barDump($url, "url");

		$pars = ArrayHash::from($params);
		// \Tracy\Debugger::barDump($pars, "pars");

		$url .= !empty($pars["lang"]) ? $pars["lang"] . "/" : null;

		if (!empty($pars["nav_item_id"])) {
			// \Tracy\Debugger::barDump(1);
			// \Tracy\Debugger::barDump($pars["nav_item_id"], "pars nav_item_id");
			$slug = $this->NavigationsManager->getItemSlugTrace($pars["nav_item_id"]);

			// \Tracy\Debugger::barDump($slug, "slug");
			$url .= $slug;

			// $queryPars = [
			// 	"lang" => !empty($params["lang"]) ? $params["lang"] : null
			// ];

			// return $url;
		} elseif ($pars->presenter == "Core:Front:Contents" && $pars->action == "contentDetail") {
			// \Tracy\Debugger::barDump(2);
			// $alias = $this->AliasesManager->getAliasByItem("contents", $id);
			// $alias = $this->AliasesManager->getItemByAlias($id);
			$item = $this->ContentsManager->getContent($pars->id);

			if (!$item) return null;
			
			$type = $item->ref("type");
			// \Tracy\Debugger::barDump($type, "type");
			// $url .= Strings::webalize($this->Translator->translate("global.content.types." . $type->short, 1)) . "/";

			$parentItem = $this->NavigationsManager->getParentItemByRoute(":Core:Front:ContentsList:contentsList", ["type" => $type->short]);
			if ($parentItem) {
				// \Tracy\Debugger::barDump($parentItem, "parentItem");
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
			// \Tracy\Debugger::barDump(3);
			// $url .= $pars->presenter . "/" . $pars->action;
			// $pres = str_replace(":", replace, subject)
			// $url
			return null;
		}

		unset($queryPars["lang"]);

		if (!empty($pars->do)) {
			$queryPars["do"] = $pars->do;
		}

		// \Tracy\Debugger::barDump($queryPars, "queryPars");
		// $url .= $queryPars ? "?" . implode("&", $queryPars) : null;
		if ($queryPars) {
			$i = 1;
			foreach ($queryPars as $par => $val) {
				// \Tracy\Debugger::barDump($url, "url");
				$url .= $i == 1 ? "?" : "&";
				$url .= $par . "=" . $val;
				$i++;
			}
		}

		// \Tracy\Debugger::barDump($url, "constructUrl url");
		return $url;
	}
}