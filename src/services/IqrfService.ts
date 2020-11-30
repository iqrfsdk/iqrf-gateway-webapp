import axios, {AxiosResponse} from 'axios';
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
	 */
	getPatches(): Promise<AxiosResponse> {
		return axios.get('iqrf/osPatches', {headers: authorizationHeader()});
	}

	getUpgrades(data: any): Promise<AxiosResponse> {
		return axios.post('iqrf/osUpgrades', data, {headers: authorizationHeader()});
	}
}

export default new IqrfService();
