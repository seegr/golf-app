<?php

namespace App\CoreModule\Model;

use Nette;
use Nette\Utils\Random;
use Nette\Utils\ArrayHash;
use Nette\Utils\Image;
use SplFileInfo;
use Nette\Http\Url;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Monty\FileSystem as MontyFileSystem;
use Monty\Helper;


class FilesManager extends BaseManager {

	const
		TABLE_FILES = "files",
		TABLE_FILES_TEMP = "files_temp",
		FILES_DIR = "files/",
		// FILES_FOLDER = __DIR__ . "/../../www/" . self::FILES_DIR,
		// FILES_TEMP = __DIR__ . "/../../www/files_temp/",
		// FONTS_FOLDER = __DIR__ . "/../../www/fonts/",
		// FOLDER_FILES = self::FILES_FOLDER,
		// FOLDER_FONTS = self::FONTS_FOLDER,
		// FOLDER_CACHE = __DIR__ . "/../../temp/cache/",
		IMAGE_SIZE = 1920,
		THUMB_SIZE = 700,
		IMAGE_QUALITY = 65,
		THUMB_QUALITY = 60,
		IMAGES_EXT = ["jpg", "jpeg", "png", "gif"];

	protected $aws, $baseUrl;
	// protected $filesFolder, $filesTemp, $


	// public function setFilesFolder($folder): self
	// {
	// 	$this->filesFolder = $folder . "/";

	// 	return $this;
	// }

	// public function setFolders()
	// {

	// }

	// public function __construct()
	// {
	// 	bdump("FilesManager construct");
	// 	bdump(FILES_DIR, "filesDir");
	// }

	public function setAws($aws) {
		$this->aws = $aws;

		return $this;
	}

	public function setBaseUrl($url) {
		$this->baseUrl = $url;

		return $this;
	}

	public function saveFile($file, $user, $key = null, $protected = false) {
		$ext = MontyFileSystem::getFileExt($file);

		// $user = $user ? $user : $this->User->getUser()->id;

		if ($file instanceof FileUpload) {
			$filePath = $file->getTemporaryFile();
			$fileName = $file->getName();
		} else {
			$fileInfo = pathinfo($file);
			$fileName = $fileInfo["basename"];
			$filePath = $file;
		}

		return $this->storeFile($filePath, $user, $fileName, $key, $protected = false);
	}

	public function saveImageFile($image, $name, $thumb = false) {
		if (!$image instanceof Nette\Utils\Image) {
			$image = Image::fromFile($image);
		}

		FileSystem::createDir($this->aws ? FILES_TEMP_DIR : FILES_DIR);
		$key = md5($name . uniqid());
		$dir = $this->aws ? FILES_TEMP_DIR : FILES_DIR;
		$path = $dir . $key . ".jpg";

		$image->resize($thumb ? self::THUMB_SIZE : self::IMAGE_SIZE, null, Image::SHRINK_ONLY);

		$image->save($path, $thumb ? self::THUMB_QUALITY : self::IMAGE_QUALITY);

		return $path;
	}

	public function uploadFile($file, $user, $key = null, $protected = false) {
		if ($file instanceof FileUpload) {
			$filePath = $file->getTemporaryFile();
		} else {
			$filePath = $file;
		}

		if (Helper::isImage($filePath)) {
			return $this->uploadImage($file, $user, $key, $protected);
		} else {
			return $this->saveFile($file, $user, $key, $protected);
		}
	}

	public function uploadImage($file, $user, $key = null, $protected = false) {
		// bdump("uploadImage");
		if ($file instanceof FileUpload) {
			$image = $file->toImage();
			$name = $file->getName();
		} else {
			$image = Image::fromFile($file);
			$name = pathinfo($file)["basename"];
		}

		$imagePath = $this->saveImageFile($image, $name); // original
		$thumbPath = $this->saveImageFile($image, $name, true); // thumb

		$saveToFiles = $this->aws ? false : true;

		$imageFile = $this->storeFile($imagePath, $user, $name, $key, $protected, $saveToFiles);
		$thumbFile = $this->storeFile($thumbPath, $user, $name, "thumb_" . $imageFile->key, $protected, $saveToFiles, true);

		// if ($this->aws) {
		// 	FileSystem::delete($imagePath);
		// 	FileSystem::delete($thumbPath);
		// }

		// FileSystem::delete(FILES_TEMP_DIR);

		$this->getFile($imageFile->id)->update(["thumb" => $thumbFile->id]);

		return $imageFile;
	}

	public function storeFile($filePath, $user, $name = null, $key = null, $protected = false, $saveToFiles = true) {
		bdump("storeFile");
		// bdump($name, "name");

		if (!file_exists($filePath)) {
			throw new \Exception("FilesManager: File $filePath not found");
		}

		$fileInfo = pathinfo($filePath);

		$name = $name ? $name : $fileInfo["basename"];
		$ext = MontyFileSystem::getFileExt($name);
		$key = $key ? $key : md5($name . uniqid()) . "." . $ext;

		if ($this->aws) {
			$result = $this->aws->s3UploadObject($key, $filePath, $protected);
			bdump($result, "result");
			$url = $result->get("ObjectURL");
		} else {
			#$httpReq = new \Nette\Http\Request;
			#bdump($httpReq->getUrl(), "url");
			// if (!$this->baseUrl) throw new \Exception("You have to set baseUrl");

			if ($saveToFiles) {
				bdump("save to files");
				bdump($name, "name");
				FileSystem::createDir(FILES_DIR);
				bdump($filePath, "filePath");
				$newPath = FILES_DIR . "/" . $key;
				if (strrpos($filePath, "tmp/") !== false) {
					bdump("uploaded file");
					move_uploaded_file($filePath, $newPath);
				} else {
					rename($filePath, $newPath);
				}
				// chmod($newPath, 777);
			}
			
			// $url = $this->baseUrl . "/" . self::FILES_DIR . "/" . $name;
			$url = self::FILES_DIR . $key;

			// throw new \Exception("neni AWS...kam s tim? :)");
		}

		return $this->getFiles()->insert([
			"key" => $key,
			"name" => $name,
			"ext" => $ext,
			"user" => $user,
			"url" => $url
		]);
	}

	/*public function awsUpload($key, $filePath, $protected = false) {
		$result = $this->aws->s3UploadObject($key, $filePath, $protected);

		return $result->get("ObjectURL");
	}*/

	public function getTempFiles($id = null) {
		$sel = $this->db->table(self::TABLE_FILES_TEMP);

		if ($id) {
			return $sel->where("temp_id", $id);
		} else {
			return $sel;
		}
	}

	public function saveTempFile($file, $tempId) {
		return $this->getTempFiles()->insert([
			"file" => $file,
			"temp_id" => $tempId
		]);
	}

	public function getTempFile($id) {
		$file = $this->getTempFiles()->get($id);

		if (!$file) {
			$file = $this->getTempFiles()->where("temp_id", $id)->order("id DESC")->fetch();
		}

		return $file;
	}

	public function getTempFilesById($tempId) {
		return $this->getTempFiles()->where("temp_id", $tempId);
	}

	public function getFiles($ids = null) {
		$sel = $this->db->table(self::TABLE_FILES);
		
		if ($ids) $sel->where("id", $ids);

		return $sel;
	}

	public function getFile($id) {
		$file = $this->getFiles()->whereOr([
			"id" => is_numeric($id) ? $id : null,
			"key" => $id
		])->fetch();

		return $file;
	}

	public function getFileSrc($id, $base = null) {
		$file = $this->getFile($id);

		$src = $base . "/";
		$src .= $file->relative_path;

		return $src;
	}

	public function getImageSrc($id, $base = null) {
		return $this->getFileSrc($id, $base);
	}

	/*public function getImagesTags() {
		return $this->db->table(self::TABLE_FILES_TAGS);
	}*/

	/*public function getImageTags($id) {
		return $this->getImagesTags()->where("file", $id);
	}*/

	public function fileDelete($id) {
		bdump("fileDelete");
		bdump($id, "id");
		$file = $this->getFile($id);

		if ($file) {
			if ($this->aws) {
				$this->aws->deleteS3Object($file->key);
			}

			if ($file->thumb) $this->fileDelete($file->thumb);

			FileSystem::delete(FILES_DIR . $file->key);
			$file->delete();
		} else {
			FileSystem::delete(FILES_DIR . $id);
		}
	}

	public function deleteFile($id) {
		$this->fileDelete($id);
	}

	public function filesDelete($ids) {
		#bdump($ids, "ids");
		foreach ($ids as $id) {
			$this->fileDelete($id);
		}
	}

	public function getImages($includeThumbs = true) {
		$sel = $this->getFiles()->where("ext", self::IMAGES_EXT);

		if (!$includeThumbs) $sel->where("thumb IS NULL");

		return $sel;
	}

	/*public function getImage($id) {
		#bdump($id, "getimage id");
		return $this->getImages()->get($id);
	}*/

	/*public function imageSave($vals) {
		$vals = ArrayHash::from($vals);

		$data = [
			"file" => $vals->file,
			"item_id" => $vals->item_id,
			"label" => isset($vals->label) ? $vals->label : null,
			"desc" => isset($vals->desc) ? $vals->desc : null
		];

		if (isset($vals->id)) {
			$id = $vals->id;

			$this->getImage($id)->update($data);
		} else {
			$id = $this->getImages()->insert($data);
		}

		if (isset($vals->tags)) {
			$this->imageTagsSave($id, $vals->tags);
		}

		return $id;
	}*/

	/*public function imageTagsSave($image, $tags) {
		$this->saveReferences(self::TABLE_FILES_IMAGES_TAGS, "image", $image, "tag", $tags);
	}*/

	public function imageDelete($id) {
		$image = $this->getImage($id);
		$fileId = $image->file;

		$image->delete();

		/*if ($this->getImages()->where("file", $fileId)->count("*") == 0) {
			$this->fileDelete($fileId);
		}*/
	}

	public function getImageThumbSrc($id, $width = null) {
		// bdump("getImageThumbSrc");
		$file = $this->getFile($id);

		if (!$file) return;

		$name = $file->name;
		$user = $file->user;

		// $src = $base . "/";
		// $src .= $file->folder . "thumbs/" . $width . "/" . $file->filename;

		// bdump(FILES_DIR . $file->key, "path");
		if (!file_exists(FILES_DIR . $file->key) || !$this->isImage($id)) {
			// bdump("no image");
			return "dist/images/item-no-image.jpg";
		}

		if ($file->thumb) {
			$url = $file->ref("thumb")->url;
		} else {
			// bdump("thumb gen");
			$thumbPath = $this->saveImageFile($file->url, $name, true);

			if ($this->aws) {
				$saveToFiles = false;
			} else {
				$saveToFiles = true;
			}

			$thumbFile = $this->storeFile($thumbPath, $user, $name, "thumb_" . $file->key, false, $saveToFiles);
			// FileSystem::delete($thumbPath);
			$thumbFileId = $thumbFile->id;
			$file->update(["thumb" => $thumbFileId]);
			$url = $thumbFile->url;
		}

		return $url;
	}

	public function clearCache() {
		FileSystem::delete(self::FOLDER_CACHE);
	}

	public function isImage($id) {
		$file = $this->getFile($id);
		// bdump($file, "file");

		return Helper::isImage(FILES_DIR . $file->key);
	}

	public function isFileUsed($id) {
		$file = $this->getFile($id);

		return $file ? true : false;
	}

	public function cleanUpFilesDir()
	{
		$files = Helper::getFilesArray(__DIR__ . "/../../../www/files");
		bdump($files, "files");

		foreach ($files as $file) {
			bdump($file, "file");
			$used = $this->isFileUsed($file);
			bdump($used, "used");

			if (!$used) $this->fileDelete($file);
		}
	}

}