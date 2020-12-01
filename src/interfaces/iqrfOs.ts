/**
 * IQRF OS upgrade metadata interface
 */
export interface IqrfOsUpgrade {
	/**
	 * IQRF OS build
	 */
	osBuild: string

	/**
	 * IQRF OS version
	 */
	osVersion: string

	/**
	 * DPA pretty version
	 */
	dpa: string

	/**
	 * DPA raw version
	 */
	dpaRaw: string

	/**
	 * IQRF OS notes
	 */
	notes: string

	/**
	 * Download path
	 */
	downloadPath: string
}
