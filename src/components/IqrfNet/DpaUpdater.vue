<template>
	<div>
		<CCard>
			<CCardHeader>{{ $t('iqrfnet.trUpload.dpaUpload.title') }}</CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='compareUploadedVersion'>
						<ValidationProvider
							v-slot='{ valid, touched, errors }'
							rules='required'
							:custom-messages='{
								required: "iqrfnet.trUpload.dpaUpload.errors.version",
							}'
						>
							<CSelect
								:value.sync='version'
								:label='$t("iqrfnet.trUpload.dpaUpload.form.version")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:placeholder='$t("iqrfnet.trUpload.dpaUpload.errors.version")'
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
		<CModal
			color='warning'
			:show.sync='showModal'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('iqrfnet.trUpload.dpaUpload.messages.modalTitle') }}
				</h5>
			</template>
			{{ $t('iqrfnet.trUpload.dpaUpload.messages.modalPrompt', {version: prettyVersion(currentDpa)}) }}
			<template #footer>
				<CButton
					color='secondary'
					@click='showModal = false'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='warning'
					@click='{{showModal = false; upload()}}'
				>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CModal, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import {FileFormat} from '../../iqrfNet/fileFormat';
import DpaService, { RFMode } from '../../services/IqrfRepository/DpaService';
import OsService from '../../services/DaemonApi/OsService';
import IqrfNetService from '../../services/IqrfNetService';
import NativeUploadService from '../../services/NativeUploadService';
import {MutationPayload} from 'vuex';
import {AxiosError, AxiosResponse} from 'axios';
import IqrfService from '../../services/IqrfService';

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
		CModal,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	}
})

/**
 * Dpa updater card for TrUpload component
 */
export default class DpaUpdater extends Vue {
	/**
	 * @constant {number} address Network address of device
	 */
	private address = 0

	/**
	 * @constant {Array<string>} allowedMTypes Array of allowed daemon api messages
	 */
	private allowedMTypes: Array<string> = [
		'iqrfEmbedOs_Read',
		'mngDaemon_Upload'
	]

	/**
	 * @var {string|null} currentDpa Current version of DPA
	 */
	private currentDpa: string|null = null

	/**
	 * @var {string|null} interfaceType Active IQRF communication interface
	 */
	private interfaceType: string|null = null

	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {string|null} osBuild IQRF OS build
	 */
	private osBuild: string|null = null

	/**
	 * @var {number|null} trType Transciever type identifier
	 */
	private trType: number|null = null

	/**
	 * @var {boolean} showModal Controls whether DPA upload modal is shown
	 */
	private showModal = false

	/**
	 * @var {string|null} version Currently selected version of DPA
	 */
	private version: string|null = null

	/**
	 * @var {Array<DpaVersions>} versions Array of available DPA versions to update to
	 */
	private versions: Array<DpaVersions> = []

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Vue lifecycle hook created
	 */
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
			if (mutation.payload.mType === 'mngDaemon_Upload') {
				if (mutation.payload.data.status === 0) {
					this.$toast.success(
						this.$t('iqrfnet.trUpload.messages.success').toString()
					);
					this.updateVersions();
				} else {
					this.$toast.error(
						this.$t('iqrfnet.trUpload.messages.failure').toString()
					);
				}
			} else if (mutation.payload.mType === 'iqmeshNetwork_EnumerateDevice') {
				if (mutation.payload.data.status === 0) {
					this.interfaceType = mutation.payload.data.rsp.osRead.flags.interfaceType;
				} else {
					this.$toast.error(
						this.$t('iqrfnet.enumeration.messages.failure').toString()
					);
				}
			}
		});
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	/**
	 * Converts DPA version from integer representation to string
	 * @param {number} version DPA version
	 * @returns {string} String representation of DPA version
	 */
	convertVersion(version: number): string {
		return version.toString(16).padStart(4, '0').toUpperCase();
	}

	/**
	 * Converts DPA version string to pretty version
	 * @param {string} version DPA version string
	 * @returns {string} DPA version pretty string
	 */
	prettyVersion(version: string): string {
		if (version === null) {
			return 'unknown';
		}
		if (version.startsWith('0')) {
			return version.charAt(1) + '.' + version.substr(2, 2);
		}
		return version.substr(0, 2) + '.' + version.substr(2, 2);
	}

	/**
	 * Performs device enumeration
	 */
	private getDeviceEnumeration(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.enumerateDevice(this.address, 30000, 'iqrfnet.enumeration.messages.failure', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * EmbedOs info response handler
	 */
	public handleOsInfoResponse(response: any): void {
		const result = response.data.rsp.result;
		this.osBuild = this.convertVersion(result.osBuild);
		this.trType = result.trMcuType;
		this.currentDpa = this.convertVersion(result.dpaVer);
		DpaService.getVersions(this.osBuild)
			.then((versions) => {
				let fetchedVersions: Array<DpaVersions> = [];
				for (const version of versions) {
					const dpaVer = Number.parseInt(version.getVersion(false));
					if (dpaVer < 400) {
						fetchedVersions.push({
							value: version.getVersion(false) + '-' + RFMode.LP,
							label: version.getVersion(true) + ', ' + RFMode.LP + ' RF mode'
						});
						fetchedVersions.push({
							value: version.getVersion(false) + '-' + RFMode.STD,
							label: version.getVersion(true) + ', ' + RFMode.STD + ' RF mode'
						});
					} else {
						fetchedVersions.push({
							value: version.getVersion(false),
							label: version.getVersion(true),
						});
					}
				}
				fetchedVersions.forEach(item => {
					if (this.currentDpa === item.value) {
						Object.assign(item, {label: item.label + ' (Current version)'});
					}
				});
				this.versions = fetchedVersions.sort().reverse();
				this.getDeviceEnumeration();
			})
			.catch(() => {
				this.$toast.error(
					this.$t('iqrfnet.trUpload.messages.osBuildFail').toString()
				);
			});
	}

	/**
	 * Updates list of DPA version to reflect changes made by upload
	 */
	private updateVersions(): void {
		for (let item of this.versions) {
			if (item.value === this.version) {
				item.label += ' (Current version)';
				continue;
			}
			if (item.label.endsWith('(Current version)')) {
				item.label = item.label.slice(0, -18);
				continue;
			}
		}
	}

	private compareUploadedVersion(): void {
		if (this.version === null) {
			return;
		}
		if (this.currentDpa === this.version) {
			this.showModal = true;
		} else {
			this.upload();
		}
	}

	/**
	 * Attempts to fetch a DPA plugin file and store it.
	 * If the fetch is successful, the name of the file is returned in response and a NativeUpload daemon api call is executed.
	 */
	private upload(): void {
		if (this.version === null) {
			return;
		}
		const request = {
			'interfaceType': this.interfaceType,
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
			.then((response: AxiosResponse) => {
				IqrfService.utilUpload([{name: response.data.fileName, type: 'DPA'}])
					.then(() => this.$toast.success(this.$t('iqrfnet.trUpload.dpaUpload.messages.uploadSuccess').toString()))
					.catch((error: AxiosError) => this.handleUtilUploadError(error));
			})
			.catch((error: AxiosError) => this.handleDpaFileError(error));
	}

	private handleUtilUploadError(error: AxiosError): void {
		if (error.response === undefined) {
			console.error(error);
			return;
		}
		const errorMsg = error.response.data.message;
		if (error.response.status === 400) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.dpaUpload.messages.fileError', {error: errorMsg}).toString()
			);
		} else if (error.response.status === 500) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.dpaUpload.messages.uploadError', {error: errorMsg}).toString()
			);
		} else {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.messagess.genericError').toString()
			);
		}
	}

	private handleDpaFileError(error: AxiosError): void {
		this.$store.commit('spinner/HIDE');
		if (error.response === undefined) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.messagess.genericError').toString()
			);
			return;
		}
		if (error.response.status === 400) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.dpaUpload.messagess.badRequest').toString()
			);
		} else if (error.response.status === 404) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.dpaUpload.messages.notFound').toString()
			);
		} else if (error.response.status === 500) {
			const msg = error.response.data.message;
			if (msg === 'Filesystem failure') {
				this.$toast.error(
					this.$t('iqrfnet.trUpload.dpaUpload.messagess.moveFailure').toString()
				);
			} else if (msg === 'Download failure') {
				this.$toast.error(
					this.$t('iqrfnet.trUpload.dpaUpload.messages.downloadFailure').toString()
				);
			}
		} else {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.messagess.genericError').toString()
			);
		}
	}

}
</script>

