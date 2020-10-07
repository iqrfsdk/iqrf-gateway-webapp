import store from '../../store';

export class StandardBinaryOutput {

	/**
	 * Index of the binary output
	 */
	public index: number;

	/**
	 * State of the binary output
	 */
	public state: boolean;

	/**
	 * Constructor
	 * @param index Index of the binary output
	 * @param state State of the binary output
	 */
	public constructor(index: number, state: boolean) {
		this.index = index;
		this.state = state;
	}
}

/**
 * IQRF Standard binary output service
 */
class StandardBinaryOutputService {

	/**
	 * Performs Binary Output enumeration on device specified by address.
	 * @param address Node address
	 */
	enumerate(address: number): Promise<any> {
		return store.dispatch('sendRequest', {
			'mType': 'iqrfBinaryoutput_Enumerate',
			'data': {
				'req': {
					'nAdr': address,
					'param': {},
				},
				'returnVerbose': true,
			},
		});
	}

	/**
	 * Retrieves states of binary outputs.
	 * @param address Node address
	 */
	getOutputs(address: number): Promise<any> {
		return this.setOutputs(address, []);
	}

	/**
	 * Sets binary outputs to a specified state.
	 * If no output settings are specified, only previous states of binary outputs are retrieved.
	 * @param address Node address
	 * @param outputs New output setting
	 */
	setOutputs(address: number, outputs: StandardBinaryOutput[] = []): Promise<any> {
		return store.dispatch('sendRequest', {
			'mType': 'iqrfBinaryoutput_SetOutput',
			'data': {
				'req': {
					'nAdr': address,
					'param': {
						'binOuts': outputs,
					},
				},
				'returnVerbose': true,
			},
		});
	}
}

export default new StandardBinaryOutputService();