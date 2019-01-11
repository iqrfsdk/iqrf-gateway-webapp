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

namespace App\IqrfNetModule\Models;

use App\IqrfNetModule\Exceptions as IqrfException;
use Nette\SmartObject;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

/**
 * Tool for managing IQMESH network
 */
class IqrfNetManager {

	use SmartObject;

	/**
	 * Alternative RF channel A
	 */
	public const ALTERNATIVE_RF_CHANNEL_A = '06';

	/**
	 * Alternative RF channel B
	 */
	public const ALTERNATIVE_RF_CHANNEL_B = '07';

	/**
	 * Main RF channel A
	 */
	public const MAIN_RF_CHANNEL_A = '11';

	/**
	 * Main RF channel B
	 */
	public const MAIN_RF_CHANNEL_B = '12';

	/**
	 * ASCII data format
	 */
	public const DATA_FORMAT_ASCII = 'ASCII';

	/**
	 * HEX data format
	 */
	public const DATA_FORMAT_HEX = 'HEX';

	/**
	 * IQMESH Security Access password
	 */
	public const SECURITY_ACCESS_PASSWORD = 'accessPassword';

	/**
	 * IQMESH Security User key
	 */
	public const SECURITY_USER_KEY = 'userKey';

	/**
	 * @var DpaRawManager DPA Raw request and response manager
	 */
	private $manager;

	/**
	 * Constructor
	 * @param DpaRawManager $manager DPA raw request and response manager
	 */
	public function __construct(DpaRawManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * The command removes all nodes from the list of bonded nodes at coordinator memory.
	 * It actually destroys the network from the coordinator point of view.
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function clearAllBonds(): array {
		$packet = '00.00.00.03.ff.ff';
		return $this->manager->send($packet);
	}

	/**
	 * This command bonds a new node by the coordinator.
	 * There is a maximum approx. 12 s blocking delay when this function is called.
	 * @param string $address A requested address for the bonded node. The address must not be used (bonded) yet. If this parameter equals to 0, then the 1 free address is assigned to the node.
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function bondNode(string $address = '00'): array {
		$packet = '00.00.00.04.ff.ff.' . Strings::padLeft($address, 2, '0') . '.00';
		$timeout = 12000;
		return $this->manager->send($packet, $timeout);
	}

	/**
	 * Runs IQMESH discovery process.
	 * The time when the response is delivered depends highly on the number of network devices, the network topology, and RF mode, thus, it is not predictable. It can take from a few seconds to many minutes.
	 * @param int $txPower TX Power used for discovery.
	 * @param string $maxAddress Nonzero value specifies maximum node address to be part of the discovery process. This feature allows splitting all node devices into two parts: [1] devices having an address from 1 to MaxAddr will be part of the discovery process thus they become routers, [2] devices having an address from MaxAddr+1 to 239 will not be routers. See IQRF OS documentation for more information. The value of this parameter is ignored at demo version. A value 5 is always used instead.
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function discovery(int $txPower = 0, string $maxAddress = '00'): array {
		$power = Strings::padLeft($txPower, 2, '0');
		$packet = '00.00.00.07.ff.ff.' . $power . '.' . $maxAddress;
		$timeout = 0;
		return $this->manager->send($packet, $timeout);
	}

	/**
	 * Removes already bonded node from the list of bonded nodes at coordinator memory.
	 * @param string $address Address of the node to remove the bond to
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function removeNode(string $address): array {
		$packet = '00.00.00.05.ff.ff.' . Strings::padLeft($address, 2, '0');
		return $this->manager->send($packet);
	}

	/**
	 * Puts specified node back to the list of bonded nodes in the coordinator memory.
	 * @param string $address Number of bonded network nodes
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function rebondNode(string $address): array {
		$packet = '00.00.00.06.ff.ff.' . Strings::padLeft($address, 2, '0');
		return $this->manager->send($packet);
	}

	/**
	 * The command read HWP configuration
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\DpaErrorException
	 * @throws IqrfException\EmptyResponseException
	 * @throws IqrfException\UserErrorException
	 * @throws JsonException
	 */
	public function readHwpConfiguration(): array {
		$packet = '00.00.02.02.ff.ff.';
		$response = $this->manager->send($packet);
		return $this->manager->parseResponse($response);
	}

	/**
	 * Set RF channel
	 * @param int $channel RF channel ID
	 * @param string $type RF channel type
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws IqrfException\InvalidRfChannelTypeException
	 * @throws JsonException
	 */
	public function setRfChannel(int $channel, string $type): array {
		$types = ['11', '12', '06', '07'];
		if (!in_array($type, $types, true)) {
			throw new IqrfException\InvalidRfChannelTypeException();
		}
		$rfChannel = Strings::padLeft(dechex($channel), 2, '0');
		return $this->writeHwpConfigurationByte($type, $rfChannel);
	}

	/**
	 * Write HWP configuration byte
	 * @param string $address Address of the item at configuration memory block.
	 * @param string $value Value of the configuration item to write.
	 * @param string $mask Specifies bits of the configuration byte to be modified by the corresponding bits of the Value parameter. Only bits that are set at the Mask will be written to the configuration byte i.e. when Mask equals to 0xFF then the whole Value will be written to the configuration byte. For example, when Mask equals to 0x12 then only bit.1 and bit.4 from Value will be written to the configuration byte.
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws JsonException
	 */
	public function writeHwpConfigurationByte(string $address, string $value, string $mask = 'ff'): array {
		$packet = '00.00.02.09.ff.ff.' . $address . '.' . $value . '.' . $mask;
		return $this->manager->send($packet);
	}

	/**
	 * Set RF LP timeout
	 * @param int $timeout RF LP timeout
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws IqrfException\InvalidRfLpTimeoutException
	 * @throws JsonException
	 */
	public function setRfLpTimeout(int $timeout): array {
		if ($timeout < 1 || $timeout > 255) {
			throw new IqrfException\InvalidRfLpTimeoutException();
		}
		$rfTimeout = Strings::padLeft(dechex($timeout), 2, '0');
		return $this->writeHwpConfigurationByte('0a', $rfTimeout);
	}

	/**
	 * Set RF output power
	 * @param int $power RF output power
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws IqrfException\InvalidRfOutputPowerException
	 * @throws JsonException
	 */
	public function setRfOutputPower(int $power): array {
		if ($power < 0 || $power > 7) {
			throw new IqrfException\InvalidRfOutputPowerException();
		}
		$rfPower = Strings::padLeft($power, 2, '0');
		return $this->writeHwpConfigurationByte('08', $rfPower);
	}

	/**
	 * Set RF signal filter
	 * @param int $filter RF signal filter
	 * @return mixed[] DPA request and response
	 * @throws IqrfException\EmptyResponseException
	 * @throws IqrfException\InvalidRfSignalFilterException
	 * @throws JsonException
	 */
	public function setRfSignalFilter(int $filter): array {
		if ($filter < 0 || $filter > 64) {
			throw new IqrfException\InvalidRfSignalFilterException();
		}
		$rfFilter = Strings::padLeft(dechex($filter), 2, '0');
		return $this->writeHwpConfigurationByte('09', $rfFilter);
	}

}