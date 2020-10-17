<template>
	<span v-if='requestRunning && mode === "unknown"'>
		<CSpinner color='info' class='cinfo-spinner' />
	</span>
	<span v-else>{{ $t('gateway.mode.modes.' + mode) }}</span>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import DaemonModeService, {DaemonModeEnum} from '../../services/DaemonModeService';
import {WebSocketClientState} from '../../store/modules/webSocketClient.module';

@Component({})

/**
 * Daemon mode information component for gateway information
 */
export default class DaemonModeInfo extends Vue {
	/**
	 * @constant {Array<string>} allowedMTypes Array of allowed daemon api messages
	 */
	private allowedMTypes: Array<string> = [
		'mngDaemon_Mode',
		'messageError'
	]

	/**
	 * @var {DaemonModeEnum} mode Current daemon mode
	 */
	private mode: DaemonModeEnum = DaemonModeEnum.unknown

	/**
	 * @var {string|null} msgId daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {boolean} requestRunning indicates whether a daemon api request has been completed
	 */
	private requestRunning = false

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
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				this.requestRunning = false;
				if (mutation.payload.data.msgId === this.msgId) {
					this.$store.dispatch('removeMessage', this.msgId);
					this.mode = DaemonModeService.parse(mutation.payload);
					this.$emit('notify-cinfo');
				}
			}
		});
		if (this.$store.getters.isSocketConnected) {
			this.getMode();
		} else {
			this.unwatch = this.$store.watch(
				(state: WebSocketClientState, getter: any) => getter.isSocketConnected,
				(newVal: boolean, oldVal: boolean) => {
					if (!oldVal && newVal) {
						this.getMode();
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
	 * Retrieves current Daemon mode
	 */
	private getMode(): void {
		DaemonModeService.get(5000, 'gateway.mode.modes.unknown', () => this.timedOut())
			.then((msgId: string) => this.msgId = msgId);
		this.requestRunning = true;
	}

	/**
	 * Daemon api request timeout handler
	 */
	private timedOut(): void {
		this.requestRunning = false;
		this.msgId = null;
		this.$emit('notify-cinfo');
	}
}
</script>

<style scoped>
.cinfo-spinner {
	width: 2rem;
	height: 2rem;
}
</style>
