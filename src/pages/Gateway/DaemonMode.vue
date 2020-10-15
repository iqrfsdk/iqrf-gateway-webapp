<template>
	<div>
		<h1>{{ $t('gateway.mode.title') }}</h1>
		<CCard body-wrapper>
			<table class='table table-striped'>
				<tr>
					<th>{{ $t('gateway.info.gwMode') }}</th>
					<td>{{ $t('gateway.mode.modes.' + mode) }}</td>
				</tr>
			</table>
			<div v-if='mode !== "unknown"'>
				<CButton
					color='primary'
					@click='setMode("operational")'
				>
					{{ $t('gateway.mode.modes.operational') }}
				</CButton> <CButton
					color='primary'
					@click='setMode("service")'
				>
					{{ $t('gateway.mode.modes.service') }}
				</CButton> <CButton
					color='primary'
					@click='setMode("forwarding")'
				>
					{{ $t('gateway.mode.modes.forwarding') }}
				</CButton>
			</div>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard} from '@coreui/vue/src';
import DaemonModeService, {DaemonModeEnum} from '../../services/DaemonModeService';
import { WebSocketClientState } from '../../store/modules/webSocketClient.module';

@Component({
	components: {
		CButton,
		CCard,
	},
	metaInfo: {
		title: 'gateway.mode.title',
	},
})

export default class DaemonMode extends Vue {
	private allowedMTypes: Array<string> = [
		'mngDaemon_Mode',
		'messageError'
	]
	private loaded = false
	private mode: DaemonModeEnum = DaemonModeEnum.unknown
	private msgId: string|null = null
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

	created(): void {
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				if (mutation.payload.data.msgId === this.msgId) {
					this.$store.dispatch('removeMessage', this.msgId);
					this.handleResponse(mutation.payload);
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

	private handleResponse(response: any): void {
		this.mode = DaemonModeService.parse(response);
		this.$store.commit('spinner/HIDE');
		if (this.mode === DaemonModeEnum.unknown) {
			this.$toast.error(
				this.$t('gateway.mode.messages.' + this.loaded ? 'set' : 'get')
					.toString()
			);
		} else if (this.loaded) {
			this.$toast.success(
				this.$t('gateway.mode.messages.' + this.mode).toString()
			);
		} else {
			this.loaded = true;
		}
	}

	private getMode(): void {
		DaemonModeService.get(5000, 'gateway.mode.messages.failures.get', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	private setMode(newMode: DaemonModeEnum|string): void {
		this.$store.dispatch('spinner/hide');
		DaemonModeService.set(newMode as DaemonModeEnum, 5000, 'gateway.mode.messages.failures.set', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
