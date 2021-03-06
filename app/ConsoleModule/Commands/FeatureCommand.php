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

namespace App\ConsoleModule\Commands;

use App\ConsoleModule\Models\FeatureManager;
use Symfony\Component\Console\Command\Command;

/**
 * Feature base command
 */
abstract class FeatureCommand extends Command {

	/**
	 * @var FeatureManager Webapp's optional features manager
	 */
	protected $manager;

	/**
	 * Constructor
	 * @param FeatureManager $manager Webapp's optional features manager
	 */
	public function __construct(FeatureManager $manager) {
		$this->manager = $manager;
		parent::__construct();
	}

}
