<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.networkManager.devicesInfo.title') }}</CCardHeader>
		<CCardBody>
			<table class='table text-center'>
				<tbody>
					<tr>
						<td class='table-toprow'>
							<CIcon class='text-info' :content='icons.coordinator' />
							{{ $t('forms.fields.coordinator') }}
						</td>
						<td class='table-toprow'>
							<CIcon class='text-danger' :content='icons.unbonded' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.unbonded') }}
						</td>
					</tr>
					<tr>
						<td>
							<CIcon class='text-info' :content='icons.bonded' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.bonded') }}
						</td>
						<td>
							<CIcon class='text-info' :content='icons.discovered' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.discovered') }}
						</td>
					</tr>
					<tr>
						<td>
							<CIcon class='text-success' :content='icons.bonded' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.bondedOnline') }}
						</td>
						<td>
							<CIcon class='text-success' :content='icons.discovered' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.discoveredOnline') }}
						</td>
					</tr>
				</tbody>
			</table>
			<CButton color='primary' class='w-100' @click='frcPing'>
				{{ $t('forms.pingNodes') }}
			</CButton>
			<div v-if='devices.length !== 0' class='table-responsive'>
				<table class='table table-striped device-info card-margin-bottom'>
					<tbody>
						<tr>
							<th />
							<th v-for='num of Array(10).keys()' :key='num'>
								{{ num }}
							</th>
						</tr>
						<tr v-for='row of Array(24).keys()' :key='row'>
							<th>{{ row }}0</th>
							<td v-for='col of Array(10).keys()' :key='col'>
								<DeviceIcon :device='devices[getAddress(row, col)]' />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<CAlert v-else color='danger'>
				{{ $t('iqrfnet.networkManager.devicesInfo.messages.empty') }}
			</CAlert>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {cilHome, cilX, cilCheckAlt, cilSignalCellular4} from '@coreui/icons';
import {CAlert, CButton, CCard, CCardBody, CCardHeader, CIcon} from '@coreui/vue/src';
import Device from '../../helpers/Device';
import DeviceIcon from './DeviceIcon.vue';
import IqrfNetService from '../../services/IqrfNetService';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';
import { Dictionary } from 'vue-router/types/router';
import { MutationPayload } from 'vuex';
import { ToastOptions } from 'vue-toast-notification';

@Component({
	components: {
		CAlert,
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CIcon,
		DeviceIcon,
	}
})

/**
 * Card of devices in network for Network Manager
 */
export default class DevicesInfo extends Vue {
	/**
	 * @constant {Array<string>} allowedMTypes Array of allowed Daemon api messages
	 */
	private allowedMTypes: Array<string> = [
		'iqrfEmbedCoordinator_BondedDevices',
		'iqrfEmbedCoordinator_DiscoveredDevices',
		'iqrfEmbedFrc_Send',
	]

	/**
	 * @var {Array<Device>} devices Array of devices in network
	 */
	private devices: Array<Device> = []

	/**
	 * @constant {Dictionary<Array<string>>} icons Dictionary of CoreUI icons
	 */
	private icons: Dictionary<Array<string>> = {
		coordinator: cilHome,
		bonded: cilCheckAlt,
		discovered: cilSignalCellular4,
		unbonded: cilX
	}

	/**
	 * @var {boolean} manual Manual FRC ping request
	 */
	private manual = false
	
	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {ToastOptions|null} toastMessage Toast message from other components to show after grid is refreshed
	 */
	private toastMessage: ToastOptions|null = null

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Component unwatch function
	 */
	private unwatch: CallableFunction = () => {return;}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		this.generateDevices();
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				if (mutation.payload.data.msgId !== this.msgId) {
					return;
				}
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				if (mutation.payload.mType === 'iqrfEmbedCoordinator_BondedDevices') {
					this.parseBondedDevices(mutation.payload);
				} else if (mutation.payload.mType === 'iqrfEmbedCoordinator_DiscoveredDevices') {
					this.parseDiscoveredDevices(mutation.payload);
				} else if (mutation.payload.mType === 'iqrfEmbedFrc_Send') {
					this.parseFrcPing(mutation.payload);
				}
			}
		});
		if (this.$store.getters.isSocketConnected) {
			this.getBondedDevices();
		} else {
			this.unwatch = this.$store.watch(
				(state, getter) => getter.isSocketConnected,
				(newVal, oldVal) => {
					if (!oldVal && newVal) {
						this.getBondedDevices();
						this.unwatch();
					}
				}
			);
		}
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unwatch();
		this.unsubscribe();
	}

	/**
	 * Creates WebSocket request options object
	 * @param {number} timeout Request timeout in milliseconds
	 * @param {string} message Request timeout message
	 * @returns {WebSocketOptions} WebSocket request options
	 */
	private buildOptions(timeout: number, message: string): WebSocketOptions {
		return new WebSocketOptions(null, timeout, message, () => this.msgId = null);
	}

	/**
	 * Creates array of devices filled with default values
	 */
	private generateDevices(): void {
		this.devices.push(new Device(0, true));
		for (let i = 1; i <= 239; i++) {
			this.devices.push(new Device(i, false));
		}
	}

	/**
	 * Computes index of device based on row and column number
	 * @param {number} row Row number
	 * @param {number} col Column number
	 * @returns {number} Device array index
	 */
	private getAddress(row: number, col: number): number {
		return row * 10 + col;
	}

	/**
	 * Performs BondedDevices api call
	 * @param {ToastOptions|null} message Message from other components to show after grid is refreshed
	 */
	public getBondedDevices(message: ToastOptions|null = null): void {
		if (message !== null) {
			this.toastMessage = message;
		}
		this.$store.dispatch('spinner/show', {timeout: 30000});
		this.$store.commit('spinner/UPDATE_TEXT', this.$t('iqrfnet.networkManager.devicesInfo.messages.getBonded').toString());
		IqrfNetService.getBonded(this.buildOptions(30000, 'iqrfnet.networkManager.devicesInfo.messages.bondedFailed'))
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Performs DiscoveredDevices api call
	 */
	private getDiscoveredDevices(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		this.$store.commit('spinner/UPDATE_TEXT', this.$t('iqrfnet.networkManager.devicesInfo.messages.getDiscovered').toString());
		IqrfNetService.getDiscovered(this.buildOptions(30000, 'iqrfnet.networkManager.devicesInfo.messages.discoveredFailed'))
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Performs FRC ping
	 */
	private frcPing(): void {
		this.$store.dispatch('spinner/show', {timeout: 90000});
		this.$store.commit('spinner/UPDATE_TEXT', this.$t('iqrfnet.networkManager.devicesInfo.messages.getOnline').toString());
		IqrfNetService.ping(this.buildOptions(90000, 'iqrfnet.networkManager.devicesInfo.messages.pingFailed'))
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Handles BondedDevices api call response
	 * @param {any} response Daemon api response
	 */
	private parseBondedDevices(response: any): void {
		switch (response.data.status) {
			case 0: {
				this.devices.forEach((item: Device) => {
					item.bonded = false;
				});
				const bonded = response.data.rsp.result.bondedDevices;
				bonded.forEach((item: number) => {
					this.devices[item].bonded = true;
				});
				this.getDiscoveredDevices();
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.bondedFailed')
						.toString()
				);
				break;
		}
	}

	/**
	 * Handles DiscoveredDevices api call response
	 * @param {any} response Daemon api response
	 */
	private parseDiscoveredDevices(response: any): void {
		switch (response.data.status) {
			case 0: {
				this.devices.forEach((item: Device) => {
					item.discovered = false;
				});
				const discovered = response.data.rsp.result.discoveredDevices;
				discovered.forEach((item: number) => {
					this.devices[item].discovered = true;
				});
				this.frcPing();
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.discoveredFailed')
						.toString()
				);
				break;
		}
	}

	/**
	 * Handles FRC ping api call response
	 * @param {any} response Daemon api response
	 */
	private parseFrcPing(response: any): void {
		switch(response.data.status) {
			case 0: {
				const online = response.data.rsp.result.frcData.slice(0, 30);
				let k = 0;
				online.forEach((item: number) => {
					for (let i = 0; i < 8; ++i) {
						const device = (item & (1 << i)) >> i;
						this.devices[k++].online = (device === 1);
					}
				});
				if (this.manual) {
					this.manual = false;
					this.$forceUpdate();
				}
				if (this.toastMessage !== null) {
					this.$toast.open(this.toastMessage);
					this.toastMessage = null;
				}
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.pingFailed')
						.toString()
				);
				break;
		}
	}

	/**
	 * Performs FRC ping requested by user
	 */
	private submitFrcPing(): void {
		this.manual = true;
		this.frcPing();
	}
}
</script>

<style scoped lang='scss'>
@media (min-width: 440px) and (max-width: 1400px) {
	.device-info {
		td, th {
			padding: 0.5rem;
		}
	}
}

@media (max-width: 440px) {
	.device-info {
		td, th {
			padding: 0.25rem;
		}
	}
}

.table-toprow {
	border: none;
}

</style>
