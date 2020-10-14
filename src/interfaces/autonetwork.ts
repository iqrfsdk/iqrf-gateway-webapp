/**
 * AutoNetwork base interface
 */
export interface AutoNetworkBase {
	actionRetries: number
	discoveryBeforeStart: boolean
	discoveryTxPower: number
	skipDiscoveryEachWave: boolean
}

/**
 * AutoNetwork overlapping networks interface
 */
export interface AutoNetworkOverlappingNetworks {
	network: number
	networks: number
}

/**
 * AutoNetwork stop conditions interface
 */
export interface AutoNetworkStopConditions {
	abortOnTooManyNodesFound: boolean
	emptyWaves: number
	numberOfNewNodes: number
	numberOfTotalNodes: number
	waves: number
}

