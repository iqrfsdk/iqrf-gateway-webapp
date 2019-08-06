<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2019 IQRF Tech s.r.o.
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

namespace App\CoreModule\Datagrids;

use App\CoreModule\Presenters\BasePresenter;
use Kdyby\Translation\Translator;
use Nette\SmartObject;
use Ublaboo\DataGrid\DataGrid;

/**
 * Generic data grid factory
 */
class DataGridFactory {

	use SmartObject;

	/**
	 * @var Translator Translator
	 */
	private $translator;

	/**
	 * Constructor
	 * @param Translator $translator Translator
	 */
	public function __construct(Translator $translator) {
		$this->translator = $translator;
	}

	/**
	 * Creates a data grid and set the translator
	 * @param BasePresenter $presenter Base presenter
	 * @param string $name Data grid's component name
	 * @return DataGrid Data grid
	 */
	public function create(BasePresenter $presenter, string $name): DataGrid {
		DataGrid::$iconPrefix = 'glyphicon glyphicon-';
		$grid = new DataGrid($presenter, $name);
		$grid->setTranslator($this->translator);
		$grid->setPagination(false);
		return $grid;
	}

}