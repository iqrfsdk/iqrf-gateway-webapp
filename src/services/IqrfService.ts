import axios, {AxiosResponse} from 'axios';
import { Dictionary } from 'vue-router/types/router';
import {authorizationHeader} from '../helpers/authorizationHeader';

/**
 * IQRF networks service
 */
class IqrfService {

	/**
	 * Retrieves IQRF interface ports
	 */
	getInterfacePorts(interfaceType: string): Promise<Array<string>> {
		return axios.get('iqrf/interfaces/', {headers: authorizationHeader()})
			.then((response: AxiosResponse) => {
				return response.data[interfaceType] as Array<string>;
			});
	}

	/**
	 * Retrieves IQRF IDE Macros
	 */
	getMacros(): Promise<AxiosResponse> {
		return axios.get('iqrf/macros/', {headers: authorizationHeader()});
	}

	/**
	 * Retrieves IQRF OS patches
	 * @param {Dictionary<number|string>} data API request body
	 */
	getUpgrades(data: Dictionary<number|string>): Promise<AxiosResponse> {
		return axios.post('iqrf/osUpgrades', data, {headers: authorizationHeader()});
	}

	/**
	 * Retrieves IQRF OS and DPA upgrade file names
	 * @param {Dictionary<number|string>} data API request body
	 */
	getUpgradeFiles(data: Dictionary<number|string>): Promise<AxiosResponse> {
		return axios.post('iqrf/osUpgradeFiles', data, {headers: authorizationHeader()});
	}

	/**
	 * Executes upload via IQRF Upload Utility
	 * @param {Dictionary<string>} data API request body
	 */
	utilUpload(data: Dictionary<string>): Promise<AxiosResponse> {
		return axios.post('iqrf/utilUpload', data, {headers: authorizationHeader()});
	}

}

export default new IqrfService();
