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

namespace App\CoreModule\Models;

use Nette\SmartObject;
use Nette\Utils\FileSystem;

/**
 * Tool for reading and writing text files
 */
class FileManager {

	use SmartObject;

	/**
	 * @var string Directory with files
	 */
	private $directory;

	/**
	 * Constructor
	 * @param string $directory Directory with files
	 */
	public function __construct(string $directory) {
		$this->directory = $directory;
	}

	/**
	 * Return the directory with files
	 * @return string Directory with files
	 */
	public function getDirectory(): string {
		return $this->directory;
	}

	/**
	 * Delete the file
	 * @param string $fileName File name
	 */
	public function delete(string $fileName): void {
		FileSystem::delete($this->directory . '/' . $fileName);
	}

	/**
	 * Check if the file exists
	 * @param string $fileName File name
	 * @return bool Is file exists?
	 */
	public function exists(string $fileName): bool {
		return file_exists($this->directory . '/' . $fileName);
	}

	/**
	 * Read the file
	 * @param string $fileName File name
	 * @return mixed File content
	 */
	public function read(string $fileName) {
		return FileSystem::read($this->directory . '/' . $fileName);
	}

	/**
	 * Write to the file
	 * @param string $fileName File name
	 * @param mixed $content File content
	 */
	public function write(string $fileName, $content): void {
		$fileName = 'nette.safe://' . $this->directory . '/' . $fileName;
		FileSystem::write($fileName, $content, null);
	}

}