<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.dpaUpload.title') }}</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='upload'>
					<ValidationProvider
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "iqrfnet.dpaUpload.missing.version",
						}'
					>
						<CSelect
							:value.sync='version'
							:label='$t("iqrfnet.dpaUpload.version")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:placeholder='$t("iqrfnet.dpaUpload.missing.version")'
							:options='versions'
						/>
					</ValidationProvider>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ $t('forms.upload') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import {FileFormat} from '../../iqrfNet/fileFormat';
import DpaService, { RFMode } from '../../services/IqrfRepository/DpaService';
import OsService from '../../services/DaemonApi/OsService';
import NativeUploadService from '../../services/NativeUploadService';
import {MutationPayload} from 'vuex';
import { WebSocketClientState } from '../../store/modules/webSocketClient.module';

interface DpaVersions {
	label: string
	value: string
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	}
})

export default class DpaUpdater extends Vue {
	private address = 0
	private allowedMTypes: Array<string> = [
		'iqrfEmbedOs_Read',
		'mngDaemon_Upload'
	]
	private msgId: string|null = null
	private osBuild: string|null = null
	private trType: number|null = null
	private version: string|null = null
	private versions: Array<DpaVersions> = []
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

	created(): void {
		extend('required', required);
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
				if (mutation.payload.data.status === 0) {
					this.handleResponse(mutation.payload);
				} else {
					this.$toast.error(
						this.$t('iqrfnet.trUpload.messages.osInfoFail').toString()
					);
				}
			} else if (mutation.payload.mType === 'mngDaemon_Upload') {
				if (mutation.payload.data.status === 0) {
					this.$toast.success(
						this.$t('iqrfnet.trUpload.messages.success').toString()
					);
				} else {
					this.$toast.error(
						this.$t('iqrfnet.trUpload.messages.failure').toString()
					);
				}
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

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	convertVersion(version: number): string {
		return version.toString(16).padStart(4, '0').toUpperCase();
	}

	private getOsInfo(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		OsService.sendRead(this.address, 30000, 'iqrfnet.trUpload.messages.osInfoFail', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	private handleResponse(response: any): void {
		const result = response.data.rsp.result;
		this.osBuild = this.convertVersion(result.osBuild);
		this.trType = result.trMcuType;
		this.version = this.convertVersion(result.dpaVer);
		DpaService.getVersions(this.osBuild)
			.then((versions) => {
				for (const version of versions) {
					const dpaVer = Number.parseInt(version.getVersion(false));
					if (dpaVer < 400) {
						this.versions.push({
							value: version.getVersion(false) + '-' + RFMode.LP,
							label: version.getVersion(true) + ', ' + RFMode.LP + ' RF mode'
						});
						this.versions.push({
							value: version.getVersion(false) + '-' + RFMode.STD,
							label: version.getVersion(true) + ', ' + RFMode.STD + ' RF mode'
						});
					} else {
						this.versions.push({
							value: version.getVersion(false),
							label: version.getVersion(true),
						});
					}
				}
				this.versions.sort().reverse();
			})
			.catch(() => {
				this.$toast.error(
					this.$t('iqrfnet.trUpload.messages.osBuildFail').toString()
				);
			});
	}

	private upload(): void {
		if (this.version === null) {
			return;
		}
		const request = {
			'osBuild': this.osBuild,
			'trSeries': this.trType,
		};
		if (this.version.endsWith('-STD')) {
			Object.assign(request, {'dpa': this.version.split('-')[0]});
			Object.assign(request, {'rfMode': RFMode.STD});
		} else if (this.version.endsWith('-LP')) {
			Object.assign(request, {'dpa': this.version.split('-')[0]});
			Object.assign(request, {'rfMode': RFMode.LP});
		} else {
			Object.assign(request, {'dpa': this.version});
		}
		DpaService.getDpaFile(request)
			.then((response) => {
				this.$store.dispatch('spinner/show', {timeout: 30000});
				NativeUploadService.upload(response.data.fileName, FileFormat.IQRF, 30000, 'iqrfnet.trUpload.messagess.genericError', () => this.msgId = null)
					.then((msgId) => this.msgId = msgId);
			})
			.catch((error) => {
				this.$store.commit('spinner/HIDE');
				if (error.response !== null) {
					switch (error.response.status) {
						case 400:
							this.$toast.error(
								this.$t('iqrfnet.trUpload.messagess.badRequest').toString()
							);
							break;
						case 404:
							this.$toast.error(
								this.$t('iqrfnet.trUpload.messages.notFound').toString()
							);
							break;
						case 500: {
							const msg = error.response.data.message;
							if (msg === 'Filesystem failure') {
								this.$toast.error(
									this.$t('iqrfnet.trUpload.messagess.moveFailure').toString()
								);
							} else if (msg === 'Download failure') {
								this.$toast.error(
									this.$t('iqrfnet.trUpload.messagess.downloadFailure').toString()
								);
							} else {
								this.$toast.error(
									this.$t('iqrfnet.trUpload.messagess.genericError').toString()
								);
							}
							break;
						}	
					}
				} else {
					this.$toast.error(
						this.$t('iqrfnet.trUpload.messagess.genericError').toString()
					);
				}
			});
	}

}
</script>

