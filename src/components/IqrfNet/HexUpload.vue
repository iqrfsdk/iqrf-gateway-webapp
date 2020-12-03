<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.trUpload.hexUpload.title') }}</CCardHeader>
		<CCardBody>
			<CForm @submit.prevent='submitUpload'>
				<div class='form-group'>
					<CInputFile
						ref='fileUpload'
						accept='.hex'
						:label='$t("iqrfnet.trUpload.hexUpload.form.file")'
						@input='isEmpty'
						@click='isEmpty'
					/>
					<p v-if='fileEmpty && !fileUntouched' style='color:red'>
						{{ $t('iqrfnet.trUpload.hexUpload.errors.file') }}
					</p>
				</div>
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
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInputFile, CSelect} from '@coreui/vue/src';
import {FileFormat} from '../../iqrfNet/fileFormat';
import {AxiosResponse, AxiosError} from 'axios';
import {FileUpload} from '../../interfaces/trUpload';
import NativeUploadService from '../../services/NativeUploadService';
import IqrfService from '../../services/IqrfService';
import FormErrorHandler from '../../helpers/FormErrorHandler';


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
 * Custom DPA handler upload card for TrUpload component
 */
export default class HexUpload extends Vue {
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
		formData.append('format', FileFormat.HEX);
		const files = this.getFiles();
		if (!files[0].name.endsWith('.hex')) {
			this.$toast.error(
				this.$t('iqrfnet.trUpload.hexUpload.messages.invalidFormat').toString()
			);
			return;
		}
		formData.append('file', this.getFiles()[0]);
		this.$store.commit('spinner/SHOW');
		NativeUploadService.uploadREST(formData)
			.then((response: AxiosResponse) => this.uploadFile(response.data))
			.catch(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.error(
					this.$t('iqrfnet.trUpload.messages.failure').toString()
				);
			});
	}

	/**
	 * Sends IQRF Upload Utility REST API request to upload Custom DPA handler
	 * @param {FileUpload} response REST API response containing uploaded file metadata
	 */
	private uploadFile(response: FileUpload): void {
		IqrfService.utilUpload([{name: response.fileName, type: 'HEX'}])
			.then(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.success(this.$t('iqrfnet.trUpload.hexUpload.messages.uploadSuccess').toString());
			})
			.catch((error: AxiosError) => FormErrorHandler.uploadUtilError(error));
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
