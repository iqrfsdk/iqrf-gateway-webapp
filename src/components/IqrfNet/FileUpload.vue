<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.trUpload.title') }}</CCardHeader>
		<CCardBody>
			<CForm @submit.prevent='submitUpload'>
				<div class='form-group'>
					<CInputFile
						ref='fileUpload'
						accept='.hex,.iqrf,.trcnfg'
						:label='$t("iqrfnet.trUpload.file")'
						@input='isEmpty'
						@click='isEmpty'
					/>
					<p v-if='fileEmpty && !fileUntouched' style='color:red'>
						{{ $t('iqrfnet.trUpload.messages.file') }}
					</p>
				</div>
				<CSelect 
					v-if='$store.getters["user/getRole"] === "power"'
					:value.sync='format'
					:label='$t("iqrfnet.trUpload.fileFormat")'
					:options='selectOptions'
					:placeholder='$t("iqrfnet.trUpload.messages.fileFormat")'
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
import {IOption} from '../../interfaces/IOption';

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

export default class FileUpload extends Vue {
	private failed = false
	private fileEmpty = true
	private fileUntouched = true
	private format: FileFormat|null = null
	private msgId: string|null = null
	private selectOptions: Array<IOption> = [
		{value: null, label: this.$t('iqrfnet.trUpload.messages.fileFormat')},
		{value: FileFormat.HEX, label: this.$t('iqrfnet.trUpload.fileFormats.hex')},
		{value: FileFormat.IQRF, label: this.$t('iqrfnet.trUpload.fileFormats.iqrf')},
		{value: FileFormat.TRCNFG, label: this.$t('iqrfnet.trUpload.fileFormats.trcnfg')}
	]
	private unsubscribe: CallableFunction = () => {return;}

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

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private getFiles(): FileList {
		const input = (this.$refs.fileUpload as CInputFile).$el.children[1] as HTMLInputElement;
		return (input.files as FileList);
	}

	private submitUpload(): void {
		this.$store.commit('spinner/SHOW');
		const formData = new FormData();
		if (this.$store.getters['user/getRole'] === 'power' && this.format !== null) {
			formData.append('format', this.format);
		}
		const files = this.getFiles();
		if (files == null || files.length === 0) {
			this.$toast.error(this.$t('iqrfnet.trUpload.messages.file').toString());
			return;
		}
		formData.append('file', this.getFiles()[0]);
		NativeUploadService.uploadREST(formData)
			.then((response) => {
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

	private isEmpty(): void {
		if (this.fileUntouched) {
			this.fileUntouched = false;
		}
		const files = this.getFiles();
		this.fileEmpty = files === null || files.length === 0;
	}
}
</script>
