<template>
	<span v-if='requestRunning && mode === "unknown"'>
		<CSpinner color='info' class='cinfo-spinner' />
	</span>
	<span v-else>{{ $t('gateway.mode.modes.' + mode) }}</span>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import DaemonModeService, {DaemonMode} from '../../services/DaemonModeService';
import {WebSocketClientState} from '../../store/modules/webSocketClient.module';

@Component({})

export default class DaemonModeInfo extends Vue {
	private allowedMTypes: Array<string> = [
		'mngDaemon_Mode',
		'messageError'
	]
	private mode: DaemonMode = DaemonMode.unknown
	private msgId: string|null = null
	private requestRunning = false
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

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

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	private getMode(): void {
		DaemonModeService.get(5000, 'gateway.mode.modes.unknown', () => this.timedOut())
			.then((msgId: string) => this.msgId = msgId);
		this.requestRunning = true;
	}

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
