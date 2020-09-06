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

namespace App\ApiModule\Version0\Models;

use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use Contributte\Middlewares\Security\IAuthenticator;
use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Utils\Strings;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class JwtAuthenticator implements IAuthenticator {

	/**
	 * @var EntityManager Entity manager
	 */
	private $entityManager;

	/**
	 * @var JwtConfigurator JWT configurator
	 */
	private $configurator;

	/**
	 * Constructor
	 * @param JwtConfigurator $configurator JWT configurator
	 * @param EntityManager $entityManager Entity manager
	 */
	public function __construct(JwtConfigurator $configurator, EntityManager $entityManager) {
		$this->configurator = $configurator;
		$this->entityManager = $entityManager;
	}

	/**
	 * @inheritDoc
	 */
	public function authenticate(ServerRequestInterface $request): ?IIdentity {
		$header = $request->getHeader('Authorization')[0] ?? '';
		$jwt = $this->parseAuthorizationHeader($header);
		if ($jwt === null) {
			return null;
		}
		$configuration = $this->configurator->create();
		/** @var Plain $token */
		$token = $configuration->getParser()->parse($jwt);
		$validator = $configuration->getValidator();
		$now = new DateTimeImmutable();
		$hostname = gethostname();
		$signedWith = new SignedWith($configuration->getSigner(), $configuration->getVerificationKey());
		if (!$validator->validate($token, $signedWith) ||
			$token->isExpired($now) ||
			!$token->claims()->has('uid') ||
			!$token->hasBeenIssuedBefore($now) ||
			!($hostname !== false && $token->hasBeenIssuedBy($hostname) &&
				$token->isIdentifiedBy($hostname))) {
			return null;
		}
		try {
			$repository = $this->entityManager->getUserRepository();
			$id = $token->claims()->get('uid');
			$user = $repository->find($id);
			if (!($user instanceof User)) {
				return null;
			}
			$data = [
				'username' => $user->getUserName(),
				'language' => $user->getLanguage(),
			];
			return new Identity($user->getId(), $user->getRole(), $data);
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * Parses the authorization header
	 * @param string $header Authorization header
	 * @return string|null JWT
	 */
	protected function parseAuthorizationHeader(string $header): ?string {
		if (strpos($header, 'Bearer') !== 0) {
			return null;
		}
		$str = Strings::substring($header, 7);
		if ($str === '') {
			return null;
		}
		return $str;
	}

}