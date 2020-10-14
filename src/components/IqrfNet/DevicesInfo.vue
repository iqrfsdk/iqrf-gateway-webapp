<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.networkManager.devicesInfo.title') }}</CCardHeader>
		<CCardBody>
			<table class='table text-center'>
				<tbody>
					<tr>
						<td class='table-toprow'>
							<CIcon class='text-info' :content='icons.coordinator' />
							{{ $t('iqrfnet.networkManager.devicesInfo.icons.coordinator') }}
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
			<div v-if='devices != []' class='table-responsive'>
				<table class='table table-striped device-info'>
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

export default class DevicesInfo extends Vue {
	private allowedMTypes: Array<string> = [
		'iqrfEmbedCoordinator_BondedDevices',
		'iqrfEmbedCoordinator_DiscoveredDevices',
		'iqrfEmbedFrc_Send',
	]
	private devices: Array<Device> = []
	private icons: Dictionary<string[]> = {
		coordinator: cilHome,
		bonded: cilCheckAlt,
		discovered: cilSignalCellular4,
		unbonded: cilX
	}
	private manual = false
	private msgId: string|null = null
	private notified = false
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

	created(): void {
		this.generateDevices();
		this.unsubscribe = this.$store.subscribe(mutation => {
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

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		} 
		this.unsubscribe();
	}

	private buildOptions(timeout, message): WebSocketOptions {
		return new WebSocketOptions(null, timeout, message, () => this.msgId = null);
	}

	private frcPing(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.ping(this.buildOptions(30000, 'iqrfnet.networkManager.devicesInfo.messages.bonded.failure'))
			.then((msgId) => this.msgId = msgId);
	}

	private generateDevices(): void {
		this.devices.push(new Device(0, true));
		for (let i = 1; i <= 239; i++) {
			this.devices.push(new Device(i, false));
		}
	}

	private getAddress(row: number, col: number): number {
		return row * 10 + col;
	}

	private getBondedDevices(): void {
		this.$store.dispatch('spinner/show', {timeout: 20000});
		IqrfNetService.getBonded(this.buildOptions(20000, 'iqrfnet.networkManager.devicesInfo.messages.bonded.failure'))
			.then((msgId) => this.msgId = msgId);
	}

	private getDiscoveredDevices(): void {
		this.$store.dispatch('spinner/show', {timeout: 20000});
		IqrfNetService.getDiscovered(this.buildOptions(20000, 'iqrfnet.networkManager.devicesInfo.messages.discovered.failure'))
			.then((msgId) => this.msgId = msgId);
	}

	private parseBondedDevices(response): void {
		switch (response.data.status) {
			case 0: {
				this.devices.forEach(item => {
					item.bonded = false;
				});
				const bonded = response.data.rsp.result.bondedDevices;
				bonded.forEach(item => {
					this.devices[item].bonded = true;
				});
				this.getDiscoveredDevices();
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.bonded.failure')
						.toString()
				);
				break;
		}
	}

	private parseDiscoveredDevices(response): void {
		switch (response.data.status) {
			case 0: {
				this.devices.forEach(item => {
					item.discovered = false;
				});
				const discovered = response.data.rsp.result.discoveredDevices;
				discovered.forEach(item => {
					this.devices[item].discovered = true;
				});
				this.frcPing();
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.discovered.failure')
						.toString()
				);
				break;
		}
	}

	private parseFrcPing(response): void {
		switch(response.data.status) {
			case 0: {
				const online = response.data.rsp.result.frcData.slice(0, 30);
				let k = 0;
				online.forEach(item => {
					for (let i = 0; i < 8; ++i) {
						const device = (item & (1 << i)) >> i;
						this.devices[k++].online = (device === 1);
					}
				});
				if (this.manual) {
					this.manual = false;
					this.$forceUpdate();
				}
				break;
			}
			default:
				this.$toast.error(
					this.$t('iqrfnet.networkManager.devicesInfo.messages.ping.failure')
						.toString()
				);
				break;
		}
		if (!this.notified) {
			this.notified = true;
			this.$emit('notify-autonetwork');
		}
	}

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
