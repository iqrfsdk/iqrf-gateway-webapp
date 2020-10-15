import store from '../store';
import {WebSocketOptions} from '../store/modules/webSocketClient.module';

export enum DaemonModeEnum {
	getMode = '',
	forwarding = 'forwarding',
	operational = 'operational',
	service = 'service',
	unknown = 'unknown',
}

/**
 * IQRF Gateway Daemon operational mode service
 */
class DaemonModeService {

	/**
	 * Retrieve the current operational mode
	 * @return Message ID
	 */
	get(timeout: number, message: string|null = null, callback: CallableFunction = () => {return;}): Promise<string> {
		return this.set(DaemonModeEnum.getMode, timeout, message, callback);
	}

	/**
	 * Sets a new mode operational mode
	 * @param newMode New operational mode
	 * @param timeout Timeout in milliseconds
	 * @param message Timeout message
	 * @param callback Timeout callback
	 * @return Message ID
	 */
	set(newMode: DaemonModeEnum, timeout: number, message: string|null = null, callback: CallableFunction = () => {return;}): Promise<string> {
		const request = {
			'mType': 'mngDaemon_Mode',
			'data': {
				'req': {
					'operMode': newMode,
				},
				'returnVerbose': true,
			},
		};
		const options = new WebSocketOptions(request, timeout, message, callback);
		return store.dispatch('sendRequest', options);
	}

	/**
	 * Parses Daemon mode response
	 * @param response Response from IQRF Gateway Daemon
	 * @return Daemon mode
	 */
	parse(response: any): DaemonModeEnum {
		try {
			return response.data.rsp.operMode as DaemonModeEnum;
		} catch (e) {
			return DaemonModeEnum.unknown;
		}
	}
}

export default new DaemonModeService();
