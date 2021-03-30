<?php

namespace Monty;

use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Monty\FileSystem;


class FileSystem {

	const mimeHeaders = [
		"pdf" => "application/pdf",
		"ppt" => "application/vnd.ms-powerpoint",
		"pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
		"mov" => "video/quicktime",
		"avi" => "video/x-msvideo",
		"mp4" => "video/mp4",
		"doc" => "application/msword",
		"docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
		"rtx" => "application/rtf,text/richtext",
		"jpg" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"png" => "image/png"
	];

	public static function getMime($fileExt) {
		if (is_array($fileExt)) {
			$mimes = [];
			foreach ($fileExt as $ext) {
				$mimes[] = self::mimeHeaders[$ext];
			}
			return implode(",", $mimes);
		} else if (strpos($fileExt, ".") !== false) {
			$ext = strtolower(self::getFileExt($fileExt));
			return self::mimeHeaders[$ext];
		} else {
			return self::mimeHeaders[$fileExt];
		}
	}

	public static function getInputMime(array $fileExts) {
		$string = "";
		foreach ($fileExts as $ext) {
			$string .= "." . $ext;
			if (!end($fileExts) != $ext) {
				$string .= ",";
			}
		}

		return $string;
	}

	public static function getFileExt($fileName) {
		$parts = explode(".", $fileName);

		return strtolower(end($parts));
	}	

	public static function makePath(array $steps) {
		$folder = "";

		foreach ($steps as $step) {
			if ($step && $step != "") {
				$folder .= $step;
				if (substr($step, -1) != "/") {
					$folder .= "/";
				}
			}
		}

		return $folder;
	}

	public static function getFileInfo($file) {
		if ($file instanceof FileUpload) {
			$fileName = $file->getName();
		} else {
			\Tracy\Debugger::barDump(pathinfo($file), "pathInfo");
			$fileName = pathinfo($file)["basename"];
		}

		$parts = explode(".", $fileName);
		unset($parts[count($parts) - 1]);
		$name = implode(".", $parts);

		$ext = FileSystem::getFileExt($fileName);

		$data = [
			"name" => $fileName,
			"ext" => $ext,
			"webalize" => Strings::webalize($name),
			"uniqueFileName" => Strings::webalize($name) . "_" . uniqid() . "." . $ext
		];

		return ArrayHash::from($data);
	}

}