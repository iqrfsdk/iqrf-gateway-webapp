<template>
	<div>
		<h1>{{ $t('iqrfnet.trUpload.title') }}</h1>
		<FileUpload />
		<DpaUpdater ref='dpaUpdater' />
		<OsUpdater ref='osUpdater' />
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {NavigationGuardNext, Route} from 'vue-router';
import {MutationPayload} from 'vuex';
import {WebSocketClientState} from '../../store/modules/webSocketClient.module';
import OsService from '../../services/DaemonApi/OsService';
import DpaUpdater from '../../components/IqrfNet/DpaUpdater.vue';
import FileUpload from '../../components/IqrfNet/FileUpload.vue';
import OsUpdater from '../../components/IqrfNet/OsUpdater.vue';

@Component({
	components: {
		DpaUpdater,
		FileUpload,
		OsUpdater,
	},
	beforeRouteEnter(to: Route, from: Route, next: NavigationGuardNext): void {
		next((vm: Vue) => {
			if (!vm.$store.getters['features/isEnabled']('trUpload')) {
				vm.$toast.error(
					vm.$t('iqrfnet.trUpload.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	metaInfo: {
		title: 'iqrfnet.trUpload.title'
	}
})

/**
 * Coordinator upload page component
 */
export default class TrUpload extends Vue {
	/**
	 * @constant {number} address IQRF Network address of coordinator
	 */
	private address = 0

	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

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
	 * Initializes validation rules and websocket callbacks
	 */
	created(): void {
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type !== 'SOCKET_ONMESSAGE') {
				return;
			}
			if (mutation.payload.data.msgId !== this.msgId) {
				return;
			}
			this.$store.dispatch('spinner/hide');
			this.$store.dispatch('removeMessage', this.msgId);
			if (mutation.payload.mType === 'iqrfEmbedOs_Read') {
				this.handleOsInfoResponse(mutation.payload);
			}
		});

		if (this.$store.getters.isSocketConnected) {
			this.getOsInfo();
		} else {
			this.unwatch = this.$store.watch(
				(state: WebSocketClientState, getter: any) => getter.isSocketConnected,
				(newVal: boolean, oldVal: boolean) => {
					if (!oldVal && newVal) {
						this.getOsInfo();
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
	 * Sends a Daemon API request to retrieve OS information
	 */
	private getOsInfo(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		OsService.sendRead(this.address, 30000, 'iqrfnet.trUpload.messages.osInfoFail', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Handles Daemon API OS response
	 * @param response Daemon API response
	 */
	private handleOsInfoResponse(response): void {
		if (response.data.status === 0) {
			(this.$refs.dpaUpdater as DpaUpdater).handleOsInfoResponse(response);
			(this.$refs.osUpdater as OsUpdater).handleOsInfoResponse(response);
		} else {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.messages.osInfoFail').toString()
			);
			console.error(response);
		}
	}
}
</script>
