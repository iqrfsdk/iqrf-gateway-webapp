/**
 * IQRF OS patch metadata interface
 */
export interface IqrfOsPatch {
	/**
	 * Patch ID
	 */
	id: number

	/**
	 * Type of module the patch is for
	 */
	moduleType: string

	/**
	 * OS version to upgrade from
	 */
	fromOsVersion: number

	/**
	 * OS build to upgrade from
	 */
	fromOsBuild: number

	/**
	 * OS version to upgrade to
	 */
	toOsVersion: number

	/**
	 * OS build to upgrade to
	 */
	toOsBuild: number

	/**
	 * Patch part number
	 */
	partNumber: number

	/**
	 * Number of patch parts
	 */
	parts: number

	/**
	 * Patch file name
	 */
	fileName: string
}
