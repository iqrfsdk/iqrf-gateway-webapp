/**
 * Daemon api raw message raw data interface
 */
export interface RawMessageRData {
	rData: string
}

/**
 * Daemon api raw message data interface
 */
export interface RawMessageData {
	req: RawMessageRData
	returnVerbose: boolean
	timeout?: number
}

/**
 * Daemon api raw message interface
 */
export interface RawMessage {
	mType: string
	data: RawMessageData
}

/**
 * Os information flags interface
 */
export interface OsInfoFlags {
	dpaHandlerDetected: boolean
	dpaHandlerNotDetectedButEnabled: boolean
	insufficientOsBuild: false
	interfaceType: string
	iqrfOsChanged: boolean
	noInterfaceSupported: boolean
	value: number
}

/**
 * Os information slotlimits interface
 */
export interface SlotLimits {
	shortestTimeslot: string
	longestTimeslot: string
	value: number
}

/**
 * Transciever MCU interface
 */
export interface TrMcu {
	fccCertified: boolean
	mcuType: string
	trType: string
	value: number
}

/**
 * Os information interface
 */
export interface OsInfo {
	flags: OsInfoFlags
	mid: string
	osBuild: string
	osVersion: string
	rssi: string
	slotLimits: SlotLimits
	supplyVoltage: string
	trMcuType: TrMcu
}

/**
 * Peripheral enumeration flags interface
 */
export interface PeripheralEnumerationFlags {
	rfModeLp: boolean
	rfModeStd: boolean
	stdAndLpNetwork: boolean
	value: number
}

/**
 * Peripheral enumeration interface
 */
export interface PeripheralEnumeration {
	dpaVer: string
	embPers: Array<number>
	flags: PeripheralEnumerationFlags
	hwpId: number
	hwpIdVer: number
	perNr: number
	userPers: Array<unknown>
}

export interface PeripheralInfo {
	par1: number
	part: number
	perT: number
	perTe: number
}

export interface Discovery {
	discovered: boolean
	parent: number
	vrn: number
	zone: number
}

export interface IEmbedPers {
	coordinator: boolean
	eeeprom: boolean
	eeprom: boolean
	io: boolean
	ledg: boolean
	ledr: boolean
	node: boolean
	os: boolean
	pwm: boolean
	ram: boolean
	spi: boolean
	thermometer: boolean
	uart: boolean
}

export interface IEmbedPersEnabled {
	enabled: boolean
	name: string
}

export interface ITrConfiguration {
	customDpaHandler: boolean
	dpaAutoexec: boolean
	dpaPeerToPeer: boolean
	embPers: IEmbedPers
	ioSetup: boolean
	lpRxTimeout: number
	neverSleep: boolean
	peerToPeer: boolean
	rfAltDsmChannel: number
	rfBand: string
	rfChannelA: number
	rfChannelB: number
	rfPgmDualChannel: boolean
	rfPgmEnableAfterReset: boolean
	rfPgmIncorrectUpload: boolean
	rfPgmLpMode: boolean
	rfPgmTerminateAfter1Min: boolean
	rfPgmTerminateMcuPin: boolean
	routingOff: boolean
	rxFilter: number
	stdAndLpNetwork: boolean
	txPower: number
	uartBaudrate: number
}

/**
 * Device enumeration interface
 */
export interface IDeviceEnumeration {
	deviceAddr: number
	discovery: Discovery
	manufacturer: string
	morePeripheralsInfo: PeripheralInfo
	osRead: OsInfo
	peripheralEnumeration: PeripheralEnumeration
	product: string
	standards: Array<string>
	trConfiguration: ITrConfiguration
}