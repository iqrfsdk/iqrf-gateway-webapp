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

/**
 * IQRF OS upgrade files interface
 */
export interface IqrfOsUpgradeFiles {
	/**
	 * DPA file name
	 */
	dpa: string

	/**
	 * Array of IQRF OS patch file names
	 */
	os: Array<string>
}

/**
 * IQRF OS upgrade file interface
 */
export interface UploadUtilFile {
	/**
	 * File name
	 */
	name: string
	
	/**
	 * File type
	 */
	type: string
}

/**
 * File upload response interface
 */
export interface FileUpload {
	/**
	 * File name
	 */
	fileName: string

	/**
	 * File format
	 */
	format: string
}
