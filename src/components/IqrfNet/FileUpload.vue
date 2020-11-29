<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.trUpload.fileUpload.title') }}</CCardHeader>
		<CCardBody>
			<CForm @submit.prevent='submitUpload'>
				<div class='form-group'>
					<CInputFile
						ref='fileUpload'
						accept='.hex'
						:label='$t("iqrfnet.trUpload.fileUpload.form.file")'
						@input='isEmpty'
						@click='isEmpty'
					/>
					<p v-if='fileEmpty && !fileUntouched' style='color:red'>
						{{ $t('iqrfnet.trUpload.fileUpload.errors.file') }}
					</p>
				</div>
				<CSelect 
					v-if='$store.getters["user/getRole"] === "power"'
					:value.sync='format'
					:label='$t("iqrfnet.trUpload.fileUpload.form.fileFormat")'
					:options='selectOptions'
					:placeholder='$t("iqrfnet.trUpload.fileUpload.errors.fileFormat")'
				/>
				<CButton
					type='submit'
					color='primary'
					:disabled='fileEmpty'
				>
					{{ $t('forms.upload') }}
				</CButton>
			</CForm>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInputFile, CSelect} from '@coreui/vue/src';
import {FileFormat} from '../../iqrfNet/fileFormat';
import NativeUploadService from '../../services/NativeUploadService';
import {IOption} from '../../interfaces/coreui';
import { AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInputFile,
		CSelect
	}
})

/**
 * FileUpload card for TrUpload component
 */
export default class FileUpload extends Vue {
	/**
	 * @var {boolean} failed Indicates that upload request has failed
	 */
	private failed = false

	/**
	 * @var {boolean} fileEmpty Indicates that the form file input is empty
	 */
	private fileEmpty = true

	/**
	 * @var {boolean} fileUntouched Indicates whether the form file input has been interacted with
	 */
	private fileUntouched = true

	/**
	 * @var {FileFormat|null} format Currently selected format of uploaded file
	 */
	private format: FileFormat|null = null

	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @constant {Array<IOption>} selectOptions Array of file format select options
	 */
	private selectOptions: Array<IOption> = [
		{value: FileFormat.HEX, label: this.$t('iqrfnet.trUpload.fileUpload.form.fileFormats.hex')},
		/*{value: FileFormat.IQRF, label: this.$t('iqrfnet.trUpload.fileUpload.form.fileFormats.iqrf')},
		{value: FileFormat.TRCNFG, label: this.$t('iqrfnet.trUpload.fileUpload.form.fileFormats.trcnfg')}*/
	]

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
				if (this.failed) {
					return;
				}
				if (mutation.payload.data.msgId === this.msgId) {
					this.$store.dispatch('spinner/hide');
					this.$store.dispatch('removeMessage', this.msgId);
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
	 * Extracts uploaded files from form file input
	 * @returns {Filelist} list of uploaded files
	 */
	private getFiles(): FileList {
		const input = (this.$refs.fileUpload as CInputFile).$el.children[1] as HTMLInputElement;
		return (input.files as FileList);
	}

	/**
	 * Attempts to upload provided file to the gateway file storage
	 */
	private submitUpload(): void {
		const formData = new FormData();
		if (this.$store.getters['user/getRole'] === 'power' && this.format !== null) {
			formData.append('format', this.format);
		}
		const files = this.getFiles();
		if (files.length === 0) {
			this.$toast.error(this.$t('iqrfnet.trUpload.messages.file').toString());
			return;
		}
		if (!files[0].name.endsWith('.hex')) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.messages.invalidFormat').toString()
			);
			return;
		}
		formData.append('file', this.getFiles()[0]);
		this.$store.commit('spinner/SHOW');
		NativeUploadService.uploadREST(formData)
			.then((response: AxiosResponse) => {
				this.$store.dispatch('spinner/show', {timeout: 30000});
				NativeUploadService.upload(response.data.fileName, response.data.format, 30000, 'iqrfnet.trUpload.messages.timeout', () => this.msgId = null)
					.then((msgId: string) => this.msgId = msgId);
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.error(
					this.$t('iqrfnet.trUpload.messages.failure').toString()
				);
			});
	}

	/**
	 * Checks if form file input is empty
	 */
	private isEmpty(): void {
		if (this.fileUntouched) {
			this.fileUntouched = false;
		}
		this.fileEmpty = this.getFiles().length === 0;
	}
}
</script>
