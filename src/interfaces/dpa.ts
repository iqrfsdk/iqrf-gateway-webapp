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