<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2018 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
declare(strict_types = 1);

namespace App\CoreModule\Model;

use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Tool for creating a new zip archive
 */
class ZipArchiveManager {

	use Nette\SmartObject;

	/**
	 * @var \ZipArchive ZIP archive
	 */
	private $zip;

	/**
	 * Constructor
	 * @param string $path Path of a new zip archive
	 * @param int $flags The mode to use to open the archive
	 */
	public function __construct(string $path, int $flags = \ZipArchive::CREATE | \ZipArchive::OVERWRITE) {
		$this->zip = new \ZipArchive();
		$this->zip->open($path, $flags);
	}

	/**
	 * Add a file to a ZIP archive from the given path
	 * @param string $path The path to the file to add
	 * @param string $filename File name in the archive
	 */
	public function addFile(string $path, string $filename) {
		$this->zip->addFile($path, $filename);
	}

	/**
	 * Add a file to the ZIP archive using its contents
	 * @param string $filename File name
	 * @param string $content The content of text file
	 */
	public function addFileFromText(string $filename, string $content) {
		$this->zip->addFromString($filename, $content);
	}

	/**
	 * Add a folder to the ZIP archive from the given path
	 * @param string $path The path to the folder to add
	 * @param string $folderName Folder name in the archive
	 */
	public function addFolder(string $path, string $folderName) {
		$this->zip->addEmptyDir($folderName);
		$files = Finder::findFiles('*')->in($path);
		foreach ($files as $file => $fileObject) {
			$this->addFile($file, $folderName . '/' . basename($file));
		}
		$directories = Finder::findDirectories('*')->in($path);
		foreach ($directories as $directory => $directoryObject) {
			$this->addFolder($directory, $folderName . '/' . basename($directory));
		}
	}

	/**
	 * Add a JSON file to the ZIP archive using its contents
	 * @param string $filename File name
	 * @param array $jsonData JSON data in an array
	 */
	public function addJsonFromArray(string $filename, array $jsonData) {
		$json = Json::encode($jsonData, Json::PRETTY);
		$this->zip->addFromString($filename, $json);
	}

	/**
	 * Check if the file or the files exist in the archive
	 * @param string|array|ArrayHash $var File(s) to check
	 * @return boolean Is file exist
	 */
	public function exist($var): bool {
		if (is_string($var)) {
			return ($this->zip->locateName('/' . $var, \ZipArchive::FL_NOCASE)) !== false;
		} elseif (is_array($var) || (is_object($var) && $var instanceof ArrayHash)) {
			foreach ($var as $file) {
				$result = $this->zip->locateName('/' . $file, \ZipArchive::FL_NOCASE);
				if (!is_integer($result)) {
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * Extract the archive contents
	 * @param string $destinationPath Path to location where to extract the files
	 */
	public function extract(string $destinationPath) {
		$this->zip->extractTo($destinationPath);
	}

	/**
	 * List files in the archive
	 * @return array List of files in the archive
	 */
	public function listFiles(): array {
		$files = [];
		for ($i = 0; $i < $this->zip->numFiles; $i++) {
			$name = $this->zip->statIndex($i)['name'];
			if (!Strings::endsWith($name, '/')) {
				$files[] = Strings::trim($name, '/');
			}
		}
		sort($files);
		return $files;
	}

	/**
	 * Open file in the archive
	 * @param string $fileName File name
	 * @return mixed Content of file
	 */
	public function openFile(string $fileName) {
		return $this->zip->getFromName('/' . $fileName);
	}

	/**
	 * Close the active archive
	 */
	public function close() {
		$this->zip->close();
	}

}