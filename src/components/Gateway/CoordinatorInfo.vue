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

export default class CoordinatorInfo extends Vue {
	private allowedMTypes: Array<string> = [
		'iqmeshNetwork_EnumerateDevice',
		'messageError'
	]
	private enumeration: PeripheralEnumeration|null = null
	private hasData = false
	private msgId: string|null = null
	private osInfo: OsInfo|null = null
	private requestRunning = false
	private trMcuType: TrMcu|null = null 
	private unwatch: CallableFunction = () => {return;}
	private unsubscribe: CallableFunction = () => {return;}

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

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	private enumerate(): void {
		IqrfNetService.enumerateDevice(0, 5000, 'iqrfnet.enumeration.messages.failure', () => this.timedOut())
			.then((msgId: string) => this.msgId = msgId);
		this.requestRunning = true;
	}

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
