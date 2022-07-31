<?php
namespace App\Models;
class Util {

	public static function isAjaxRequest() {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return true;
		}
		return false;
	}

	public static function setJsonHeader() {
		header('Content-Type: application/json');
	}

	public static function getRequestAddress() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			return $_SERVER["REMOTE_ADDR"];
		}
	}

	public static function getBaseAddress() {
		return $_SERVER['SERVER_NAME'];
	}
	
	public static function getBaseDirectory() {
		return dirname(dirname(__DIR__));
	}

	public static function directoryExists($dir) {
		return file_exists($dir);
	}
	
	public static function createDirectory($dir) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
			return true;
		}
		return false;
	}

	public static function createFile($dir, $filename, $ext, $data) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		$file = fopen($dir . $filename . '.' . $ext, 'w');
		fwrite($file, $data);
		fclose($file);
	}

	public static function isNullOrEmptyString($string) {
		return (!isset($string) || trim($string)==='');
	}

	public static function truncateText($text, $chars = 25) {
		$text = $text . " ";
		$text = substr($text, 0, $chars);
		$text = substr($text, 0, strrpos($text, ' '));
		return $text . "...";
	}

	public static function uploadCodeToMessage($code) { 
		switch ($code) { 
			case UPLOAD_ERR_INI_SIZE: 
				$message = "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
				break; 
			case UPLOAD_ERR_FORM_SIZE: 
				$message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
				break; 
			case UPLOAD_ERR_PARTIAL: 
				$message = "The uploaded file was only partially uploaded"; 
				break; 
			case UPLOAD_ERR_NO_FILE: 
				$message = "No file was uploaded"; 
				break; 
			case UPLOAD_ERR_NO_TMP_DIR: 
				$message = "Missing a temporary folder"; 
				break; 
			case UPLOAD_ERR_CANT_WRITE: 
				$message = "Failed to write file to disk"; 
				break; 
			case UPLOAD_ERR_EXTENSION: 
				$message = "File upload stopped by extension"; 
				break; 

			default: 
				$message = "Unknown upload error"; 
				break; 
		} 
		return $message; 
	} 
}