<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use Nette;
use Nette\Utils\Json;
use Nette\Utils\ArrayHash;


class ContentsPresenter extends FrontPresenter
{

	public function renderContentDetail($id): void
	{
		// bdump($this->getParameters(), "renderContentDetail pars");
		$template = $this->template;
		$config = $this->SettingsManager;
		$content = $template->content = $this->ContentsManager->getContent($id);

		if (!$content) $this->error("Stránka nenalezena", 404);

		// bdump($content, "content");

		$type = $content->ref("type");

		$template->bodyClass[] = $type->short;

		$this->setContentMeta($content);

		$template->content = $content;
		$template->attachments = $this->ContentsManager->getContentAttachments($id)->order("order");
		$template->type = $type->short;

		$user = $this->getUser();
		// bdump($user, "user");
		// bdump($user->id, "user id");
		$template->iamContentEditor = !$this->iamContentEditor($id, $user) ? false : true;

		$gal = $this["contentGallery"];
		$images = $this->ContentsManager->getContentImages($content->id);
		$imagesOrder = $content->images_order ? $content->images_order : "ASC";
		$images->order("order " . $imagesOrder);

		foreach ($images as $image) {
			#bdump($image, "image");
			$file = $image->ref("file");
			$gal->addImage($file->url, $this->getThumb($file->id))
				->setTitle($image->title)
				->setDesc($image->desc);
		}

		if ($type->short == "photos") $this["contentGallery"]->setRowHeight(200);

		// bdump($content, "content");

		// header image
		$headerImgConfig = $template->headerImgConfig = $config->getSetting("content_header_image");
		switch ($config->getSetting("content_header_image")) {
			case "custom":
			case "cropper":
				// bdump($content->header_image, "headerimage");
				if ($content->header_image) {
					$template->headerImage = $content->ref("header_image")->url;
				} else if ($defaultImg = $config->getSetting("content_header_image_default")) {
					$template->headerImage = $defaultImg;
				} else {
					$template->headerImage = null;
				}
				break;

			case "0":
			case false:
				$template->headerImage = null;
				break;
		}

		// if ($customFields = $content->custom_fields) {
		// 	$fields = $this->ContentsManager->getContentCustomFields($type->short);
		// 	// bdump($fields, "fields");
		// 	$template->customFields = ArrayHash::from($fields);
		// 	$template->customData = $customData = Json::decode($customFields);
		// 	// bdump($customData, "customData");
		// }

		$template->customData = $this->getContentCustomData($id);

		if ($type->short == "event" && $date = $this->getParameter("date"))	{
			$date = $this->ContentsManager->getEventDate($date);

			$template->date = $date ? $date : null;
		}
	}

	public function createComponentContentGallery() {
		$gal = new \Monty\Gallery;

		$gal->setRowHeight(200);

		return $gal;
	}

	public function setContentMeta($content) {
		$template = $this->template;
		$template->metaKeys = $content->meta_keys;
		if ($content->meta_desc) {
			$metaDesc = $content->meta_desc;
		} else if ($content->short_text) {
			$metaDesc = $content->short_text;
		} else {
			$metaDesc = $content->text;
		}
		$template->metaDesc = $metaDesc;
		// $template->ogTitle = $content->title;
		$template->ogDesc = $template->metaDesc;
		$template->ogImage = $content->image ? $content->ref("image")->url : $template->ogImage;
	}

}
