<template>
	<span v-if='requestRunning'>
		<CSpinner color='info' class='cinfo-spinner' />
	</span>
	<span v-else>
		<span v-if='hasData'>
			<strong>{{ $t('gateway.info.tr.moduleType') }}: </strong>
			{{ trMcuType.trType }}<br>
			<strong>{{ $t('gateway.info.tr.mcuType') }}: </strong>
			{{ trMcuType.mcuType }}<br>
			<strong>{{ $t('gateway.info.tr.moduleId') }}: </strong> {{ osInfo.mid }}<br>
			<strong>{{ $t('gateway.info.tr.os') }}: </strong>
			{{ osInfo.osVersion }} ({{ osInfo.osBuild }})<br>
			<strong>{{ $t('gateway.info.tr.dpa') }}: </strong>
			{{ enumeration.dpaVer }}<br>
			<span v-if='enumeration.flags.rfMode'>
				<strong>{{ $t('gateway.info.tr.rfMode') }}</strong>
				{{ enumeration.flags.rfMode }}
			</span>
			<span v-else-if='enumeration.flags.networkType'>
				<strong>{{ $t('gateway.info.tr.networkType') }}</strong>
				{{ enumeration.flags.networkType }}
			</span>
			<strong>{{ $t('gateway.info.tr.voltage') }}: </strong>
			{{ osInfo.supplyVoltage }}<br>
		</span>
		<span v-else>
			{{ $t('gateway.info.tr.error') }}
		</span>
	</span>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import IqrfNetService from '../../services/IqrfNetService';
import {CSpinner} from '@coreui/vue/src';
import {WebSocketClientState} from '../../store/modules/webSocketClient.module';
import {PeripheralEnumeration, OsInfo, TrMcu} from '../../interfaces/dpa';

@Component({
	components: {
		CSpinner,
	},
})

/**
 * Coordinator information block component for gateway information
 */
export default class CoordinatorInfo extends Vue {
	/**
	 * @constant {Array<string>} allowedMTypes Array of allowed daemon api messages
	 */
	private allowedMTypes: Array<string> = [
		'iqmeshNetwork_EnumerateDevice',
		'messageError'
	]

	/**
	 * @var {PeripheralEnumeration|null} enumeration Peripheral enumeration of a device
	 */
	private enumeration: PeripheralEnumeration|null = null

	/**
	 * @var {boolean} hasData Indicates whether data has been fetched successfully
	 */
	private hasData = false

	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {OsInfo|null} osInfo Information about OS of a device
	 */
	private osInfo: OsInfo|null = null

	/**
	 * @var {boolean} requestRunning Indicates whether a daemon api request has been completed
	 */
	private requestRunning = false

	/**
	 * @var {TrMcu|null} trMcuType Information about transciever type
	 */
	private trMcuType: TrMcu|null = null

	/**
	 * Component unwatch function
	 */
	private unwatch: CallableFunction = () => {return;}

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				this.requestRunning = false;
				if (mutation.payload.data.msgId === this.msgId) {
					this.$store.dispatch('removeMessage', this.msgId);
					try {
						const data = mutation.payload.data.rsp;
						this.enumeration = data.peripheralEnumeration;
						this.osInfo = data.osRead;
						this.hasData = true;
						if (this.osInfo === null) {
							return;
						}
						this.trMcuType = this.osInfo.trMcuType;
					} catch (e) {
						this.hasData = false;
					}
				}
			}
			
		});
		if (this.$store.getters.isSocketConnected) {
			this.enumerate();
		} else {
			this.unwatch = this.$store.watch(
				(state: WebSocketClientState, getter: any) => getter.isSocketConnected,
				(newVal: boolean, oldVal: boolean) => {
					if (!oldVal && newVal) {
						this.enumerate();
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
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	/**
	 * Performs enumeration of the coordinator device
	 */
	private enumerate(): void {
		IqrfNetService.enumerateDevice(0, 5000, 'iqrfnet.enumeration.messages.failure', () => this.timedOut())
			.then((msgId: string) => this.msgId = msgId);
		this.requestRunning = true;
	}

	/**
	 * Daemon api request timeout handler
	 */
	private timedOut(): void {
		this.requestRunning = false;
		this.msgId = null;
	}
}
</script>

<style scoped>
.cinfo-spinner {
	width: 2rem;
	height: 2rem;
}
</style>
