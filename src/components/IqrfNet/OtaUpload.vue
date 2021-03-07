<template>
	<CCard class='border-0 card-margin-bottom'>
		<CCardBody>
			<ValidationObserver v-slot='{invalid}'>
				<CForm>
					<CSelect
						:value.sync='fileType'
						:options='fileTypeOptions'
						:label='$t("iqrfnet.networkManager.otaUpload.form.fileType")'
					/>
					<div class='form-group'>
						<CInputFile
							ref='fileInput'
							accept='.hex'
							:label='$t("iqrfnet.networkManager.otaUpload.form.file")'
							@input='fileInputEmpty'
							@click='fileInputEmpty'
						/>
						<p v-if='fileEmpty && fileTouched' style='color: red;'>
							{{ $t('iqrfnet.networkManager.otaUpload.errors.file') }}
						</p>
					</div><hr>
					<CSelect
						:value.sync='target'
						:options='targetOptions'
						:label='$t("iqrfnet.networkManager.otaUpload.form.target")'
					/>
					<ValidationProvider
						v-if='target === targetEnum.NODE'
						v-slot='{errors, touched, valid}'
						rules='required|integer|between:1,239'
						:custom-messages='{
							required: "iqrfnet.networkManager.otaUpload.errors.nodeAddress",
							integer: "forms.errors.integer",
							between: "iqrfnet.networkManager.otaUpload.errors.nodeAddress"
						}'
					>
						<CInput
							v-model.number='address'
							type='number'
							min='1'
							max='239'
							:label='$t("iqrfnet.networkManager.otaUpload.form.nodeAddress")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<div 
						v-if='target === targetEnum.NETWORK'
						class='form-group'
					>
						<ValidationProvider
							v-slot='{errors, touched, valid}'
							rules='required|integer|between:0,65535'
							:custom-messages='{
								required: "iqrfnet.networkManager.otaUpload.errors.hwpid",
								integer: "forms.errors.integer",
								between: "iqrfnet.networkManager.otaUpload.errors.hwpid"
							}'
						>
							<CInput
								v-model.number='hwpid'
								type='number'
								min='0'
								max='65535'
								:label='$t("iqrfnet.networkManager.otaUpload.form.hwpidFilter")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<p>
							<i>
								{{ $t('iqrfnet.networkManager.otaUpload.messages.hwpid') }}
							</i>
						</p>
					</div>
					<ValidationProvider
						v-slot='{errors, touched, valid}'
						rules='required|integer|between:0,16383'
						:custom-messages='{
							required: "iqrfnet.networkManager.otaUpload.errors.eeepromAddress",
							integer: "forms.errors.integer",
							between: "iqrfnet.networkManager.otaUpload.errors.eeepromAddress"
						}'
					>
						<CInput
							v-model.number='eeepromAddress'
							type='number'
							min='0'
							max='16383'
							:label='$t("iqrfnet.networkManager.otaUpload.form.eeepromAddress")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider><hr>
					<h4>{{ $t('iqrfnet.networkManager.otaUpload.form.manualUpload') }}</h4>
					<div class='form-group'>
						<div>
							<b>
								{{ $t('iqrfnet.networkManager.otaUpload.form.uploadSteps.uploadEeeprom') }}
							</b>
						</div><br>
						<CButton
							color='primary'
							:disabled='invalid || fileEmpty'
							@click='uploadFile(false)'
						>
							{{ $t('forms.upload') }}
						</CButton>
					</div>
					<div class='form-group'>
						<div>
							<b>
								{{ $t('iqrfnet.networkManager.otaUpload.form.uploadSteps.verifyEeeprom') }}
							</b>
						</div><br>
						<CButton
							color='primary'
							:disabled='invalid || fileEmpty'
							@click='verifyStep'
						>
							{{ $t('forms.verify') }}
						</CButton>
					</div>
					<div class='form-group'>
						<div>
							<b>
								{{ $t('iqrfnet.networkManager.otaUpload.form.uploadSteps.loadFlash') }}
							</b>
						</div><br>
						<CButton
							color='primary'
							:disabled='invalid || fileEmpty'
							@click='flashLoadStep'
						>
							{{ $t('forms.load') }}
						</CButton>
					</div><hr>
					<h4>{{ $t('iqrfnet.networkManager.otaUpload.form.automaticUpload') }}</h4>
					<CButton
						color='primary'
						:disabled='invalid || fileEmpty'
						@click='uploadFile(true)'
					>
						{{ $t('forms.allSteps') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputFile, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';

import IqrfNetService from '../../services/IqrfNetService';
import NativeUploadService from '../../services/NativeUploadService';

import {FileFormat} from '../../iqrfNet/fileFormat';
import {NetworkTarget} from '../../iqrfNet/networkTarget';
import {OtaUploadAction} from '../../iqrfNet/otaUploadAction';

import {IOption} from '../../interfaces/coreui';
import {MutationPayload} from 'vuex';
import {AxiosResponse} from 'axios';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		CInputFile,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	}
})

/**
 * OTA upload component for IQRF network manager
 */
export default class OtaUpload extends Vue {

	/**
	 * @var {number} address Target device address for node target option
	 */
	private address = 1

	/**
	 * @var {boolean} autoUpload Execute all steps automatically
	 */
	private autoUpload = false

	/**
	 * @var {number} eeepromAddress External EEPROM start address for data
	 */
	private eeepromAddress = 0

	/**
	 * @var {boolean} fileEmpty Indicates whether file input is empty or not
	 */
	private fileEmpty = true

	/**
	 * @var {string} fileName Name of file uploaded to gateway
	 */
	private fileName = ''

	/**
	 * @var {boolean} fileTouched Indicates whether file has been touched
	 */
	private fileTouched = false

	/**
	 * @var {FileFormat} fileType Type of IQRF file to upload
	 */
	private fileType: FileFormat = FileFormat.HEX

	/**
	 * @constant {Array<IOption>} fileTypeOptions Array of CoreUI select options for file input
	 */
	private fileTypeOptions: Array<IOption> = [
		{
			label: this.$t('iqrfnet.networkManager.otaUpload.form.fileTypes.hex'),
			value: FileFormat.HEX
		}
	]

	/**
	 * @var {string} hwpidList HWPID to filter nodes by
	 */
	private hwpid = 65535

	/**
	 * 
	 */
	private msgId: string|null = null

	/**
	 * @var {IqrfNetworkTarget} target IQRF network upload target
	 */
	private target: NetworkTarget = NetworkTarget.COORDINATOR

	/**
	 * @constant {enum} targetEnum IQRF network target enum for template
	 */
	private targetEnum = NetworkTarget

	/**
	 * @var {Array<IOption>} targetOptions Array of CoreUI select options for upload target
	 */
	private targetOptions: Array<IOption> = [
		{
			label: this.$t('iqrfnet.networkManager.otaUpload.form.targets.coordinator'),
			value: NetworkTarget.COORDINATOR,
		},
		{
			label: this.$t('iqrfnet.networkManager.otaUpload.form.targets.node'),
			value: NetworkTarget.NODE,
		},
		{
			label: this.$t('iqrfnet.networkManager.otaUpload.form.targets.network'),
			value: NetworkTarget.NETWORK,
		},
	]

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Initializes validation rules
	 */
	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type !== 'SOCKET_ONMESSAGE') {
				return;
			}
			if (mutation.payload.data.msgId !== this.msgId) {
				return;
			}
			this.$store.dispatch('removeMessage', this.msgId);
			if (mutation.payload.mType === 'iqmeshNetwork_OtaUpload') {
				this.handleOtaUploadResponse(mutation.payload.data);
			} else if (mutation.payload.mType === 'messageError') {
				this.$store.commit('spinner/HIDE');
				this.$toast.error(
					this.$t('iqrfnet.networkManager.otaUpload.messages.genericError').toString()
				);
			}
		});
	}

	/**
	 * Extracts file from file input
	 * @returns {FileList} List of uploaded files
	 */
	private getFiles(): FileList {
		const input = ((this.$refs.fileInput as CInputFile).$el.children[1] as HTMLInputElement);
		return (input.files as FileList);
	}

	/**
	 * Checks if file input is empty
	 */
	private fileInputEmpty(): void {
		if (!this.fileTouched) {
			this.fileTouched = true;
		}
		this.fileEmpty = this.getFiles().length === 0;
	}

	/**
	 * Uploads file from file input to gateway filesystem
	 * @param {boolean} autoUpload Run all steps automatically
	 */
	private uploadFile(autoUpload: boolean): void {
		this.autoUpload = autoUpload;
		this.fileName = '';
		const formData = new FormData();
		formData.append('format', FileFormat.HEX);
		formData.append('file', this.getFiles()[0]);

		this.$store.commit('spinner/SHOW');
		NativeUploadService.uploadREST(formData)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.fileName = response.data.fileName;
				this.uploadStep();
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.error(
					this.$t('iqrfnet.networkManager.otaUpload.messages.gwUploadFail').toString()
				);
			});
	}

	/**
	 * Retrieves device address depending on the upload target
	 * @returns {number} Device address
	 */
	private getAddress(): number {
		if (this.target === NetworkTarget.COORDINATOR) {
			return 0;
		} else if (this.target === NetworkTarget.NODE) {
			return this.address;
		} else {
			return 255;
		}
	}

	/**
	 * Sends Daemon API request to execute the upload step
	 */
	private uploadStep(): void {
		this.executeStep(OtaUploadAction.UPLOAD, 'iqrfnet.networkManager.otaUpload.messages.uploadStepFail');
	}

	/**
	 * Sends Daemon API request to execute the verification step
	 */
	private verifyStep(): void {
		this.executeStep(OtaUploadAction.VERIFY, 'iqrfnet.networkManager.otaUpload.messages.verifyStepFail');
	}

	/**
	 * Sends Daemon API request to execute the flash load step
	 */
	private flashLoadStep(): void {
		this.executeStep(OtaUploadAction.LOAD, 'iqrfnet.networkManager.otaUpload.messages.loadStepFail');
	}

	/**
	 * Sends Daemon API rquest to execute specified step
	 */
	private executeStep(action: OtaUploadAction, message: string): void {
		this.$store.commit('spinner/SHOW');
		this.$store.commit('spinner/UPDATE_TEXT', this.$t('iqrfnet.networkManager.otaUpload.messages.' + action.toLowerCase() + 'Running').toString());
		IqrfNetService.otaUpload(
			this.getAddress(),
			this.hwpid,
			this.fileName,
			this.eeepromAddress,
			action,
			new WebSocketOptions(null, null, message, () => this.msgId = null)
		).then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Handles Daemon API OTA upload responses
	 * @param response Daemon API response
	 */
	private handleOtaUploadResponse(response: any): void {
		if (this.target === NetworkTarget.COORDINATOR ||
			this.target === NetworkTarget.NODE) {
			this.deviceResponse(response.rsp.deviceAddr, response.status, response.statusStr, response.rsp.loadingAction);
			return;
		}
		if (response.status === 1002) {
			this.$store.commit('spinner/HIDE');
			this.$toast.error(
				this.$t('iqrfnet.networkManager.otaUpload.messages.invalidFile').toString()
			);
			return;
		}
		if (response.status === 1003) {
			this.$store.commit('spinner/HIDE');
			this.$toast.error(
				this.$t('iqrfnet.networkManager.otaUpload.messages.invalidContent').toString()
			);
			return;
		}
		const action = response.rsp.loadingAction;
		if (action === OtaUploadAction.UPLOAD) {
			if (this.autoUpload) {
				this.verifyStep();
			} else {
				this.$store.commit('spinner/HIDE');
				this.$toast.info(
					this.$t('iqrfnet.networkManager.otaUpload.messages.network.uploadStepSuccess').toString()
				);
			}
		} else if (action === OtaUploadAction.VERIFY) {
			if (this.autoUpload) {
				this.flashLoadStep();
			} else {
				this.$store.commit('spinner/HIDE');
				const devices = response.rsp.verifyResult.map((item) => {
					if (!item.result) {
						return item.address;
					}
				}).join(', ');
				this.$toast.info(
					this.$t(
						'iqrfnet.networkManager.otaUpload.messages.network.verifyStepSuccess',
						{devices: devices.trim() === ',' ? 'None' : devices}
					).toString()
				);
			}
		} else {
			let devices = response.rsp.loadResult.map((item) => {
				if (!item.result) {
					return item.address;
				}
			}).join(', ');
			this.$store.commit('spinner/HIDE');
			if (this.autoUpload) {
				this.$toast.info(
					this.$t(
						'iqrfnet.networkManager.otaUpload.messages.network.runAllSuccess',
						{devices: devices.trim() === ',' ? 'None' : devices}
					).toString()
				);
			} else {
				this.$toast.info(
					this.$t(
						'iqrfnet.networkManager.otaUpload.messages.network.loadStepSuccess',
						{devices: devices.trim() === ',' ? 'None' : devices}
					).toString()
				);
			}
		}
	}

	/**
	 * Handles OTa upload response for coordinator and node devices
	 * @param {number} address Device address
	 * @param {number} status OTA upload status code
	 * @param {string} statusMessage OTA upload status message
	 * @param {OtaUploadAction} action OTA upload step
	 */
	private deviceResponse(address: number, status: number, statusMessage: string, action: OtaUploadAction): void {
		if (status === 0) {
			if (action === OtaUploadAction.UPLOAD) {
				if (this.autoUpload) {
					this.verifyStep();
				} else {
					this.$store.commit('spinner/HIDE');
					this.$toast.success(
						this.$t(address === 0 ?
							'iqrfnet.networkManager.otaUpload.messages.coordinator.uploadStepSuccess':
							'iqrfnet.networkManager.otaUpload.messages.node.uploadStepSuccess', {address: address}
						).toString()
					);
				}
			} else if (action === OtaUploadAction.VERIFY) {
				if (this.autoUpload) {
					this.flashLoadStep();
				} else {
					this.$store.commit('spinner/HIDE');
					this.$toast.success(
						this.$t(address === 0 ?
							'iqrfnet.networkManager.otaUpload.messages.coordinator.verifyStepSuccess':
							'iqrfnet.networkManager.otaUpload.messages.node.verifyStepSuccess', {address: address}
						).toString()
					);
				}
			} else {
				this.$store.commit('spinner/HIDE');
				if (this.autoUpload) {
					this.$toast.success(
						this.$t(address === 0 ?
							'iqrfnet.networkManager.otaUpload.messages.coordinator.runAllSuccess':
							'iqrfnet.networkManager.otaUpload.messages.node.runAllSuccess', {address: address}
						).toString()
					);
				} else {
					this.$toast.success(
						this.$t(address === 0 ?
							'iqrfnet.networkManager.otaUpload.messages.coordinator.loadStepSuccess':
							'iqrfnet.networkManager.otaUpload.messages.node.loadStepSuccess', {address: address} 
						).toString()
					);
				}
			}
			return;
		}
		this.$store.commit('spinner/HIDE');
		// invalid uploaded file
		if (status === 1002) {
			this.$toast.error(
				this.$t('iqrfnet.networkManager.otaUpload.messages.invalidFile').toString()
			);
			return;
		}
		// invalid file content
		if (status === 1003) {
			this.$toast.error(
				this.$t('iqrfnet.networkManager.otaUpload.messages.invalidContent').toString()
			);
			return;
		}
		if (status === 1005) {
			// device offline
			if (statusMessage.includes('Transaction error')) {
				this.$toast.error(
					this.$t(address === 0 ?
						'forms.messages.coordinatorOffline':
						'forms.messages.deviceOffline', {address: address}
					).toString()
				);
			} else if (statusMessage.includes('Dpa error')) {
				this.$toast.error(
					this.$t('forms.messages.noDevice', {address: address}).toString()
				);
			} else {
				this.$toast.error(
					this.$t('iqrfnet.networkManager.otaUpload.messages.genericError').toString()
				);
			}
			return;
		}
		this.$toast.error(
			this.$t('iqrfnet.networkManager.otaUpload.messages.genericError').toString()
		);
	}
}
</script>
