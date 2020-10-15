/**
 * Controller configuration autonetwork api call stop conditions interface
 */
export interface ControllerAutoNetworkStopConditions {
	abortOnTooManyNodesFound: boolean
	emptyWaves: number
	waves: number
}

/**
 * Controller configuration autonetwork api call base interface
 */
export interface ControllerAutoNetwork {
	actionRetries: number
	discoveryBeforeStart: boolean
	discoveryTxPower: number
	returnVerbose: boolean
	skipDiscoveryEachWave: boolean
	stopConditions: ControllerAutoNetworkStopConditions
}

/**
 * Controller configuration discovery api call interface
 */
export interface ControllerDiscovery {
	maxAddr: number
	returnVerbose: boolean
	txPower: number
}

/**
 * Controller configuration daemon api interface
 */
export interface ControllerDaemonApi {
	autoNetwork: ControllerAutoNetwork
	discovery: ControllerDiscovery
}

/**
 * Controller configuration factory reset interface
 */
export interface ControllerFactoryReset {
	coordinator: boolean
	daemon: boolean
	network: boolean
	webapp: boolean
}

/**
 * Controller configuration logger interface
 */
export interface ControllerLogger {
	filePath: string
	severity: string
}

/**
 * Controller configuration reset button interface
 */
export interface ControllerResetButton {
	api: string
	button: number
}

/**
 * Controller configuration status led interface
 */
export interface ControllerStatusLed {
	greenLed: number
	redLed: number
}

/**
 * Controller configuration websocket servers interface
 */
export interface ControllerWsServers {
	api: string
	monitor: string
}

/**
 * Controller configuration base interface
 */
export interface ControllerBase {
	daemonApi: ControllerDaemonApi
	factoryReset: ControllerFactoryReset
	logger: ControllerLogger
	resetButton: ControllerResetButton
	statusLed: ControllerStatusLed
	wsServers: ControllerWsServers
}