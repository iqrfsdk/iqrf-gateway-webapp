<template>
	<CCard>
		<CCardHeader>{{ $t("config.iqmesh.otaUpload.title") }}</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveInstance'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.iqmesh.otaUpload.form.messages.instance"}'
					>					
						<CInput
							v-model='configuration.instance'
							:label='$t("config.iqmesh.otaUpload.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInput
						v-model='configuration.uploadPath'
						:label='$t("config.iqmesh.otaUpload.form.uploadPath")'
					/>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';

interface OtaUploadConfig {
	instance: string|null
	uploadPath: string|null
	uploadPathSuffix: string|null
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo: {
		title: 'config.iqmesh.otaUpload.title'
	}
})

/**
 * OtaUpload card for IqmeshServices component
 */
export default class OtaUpload extends Vue {
	/**
	 * @constant {string} componentName name of daemon component
	 */
	private componentName = 'iqrf::OtaUploadService'

	/**
	 * @var {string} instance name of daemon componenent instance
	 */
	private instance: string|null = null

	/**
	 * @var {OtaUploadConfig} configuration OtaUpload instance configuration
	 */
	private configuration: OtaUploadConfig = {
		instance: null,
		uploadPath: null,
		uploadPathSuffix: null,
	}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('required', required);
		this.getInstance();
	}

	/**
	 * Retrieves instance of OtaUpload daemon component
	 */
	private getInstance(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				if (response.data.instances.length > 0) {
					this.configuration = response.data.instances[0];
					this.instance = this.configuration.instance;
				}
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Updates configuration of OtaUpload instance and creates one if it does not exist
	 */
	private saveInstance(): void {
		this.$store.commit('spinner/SHOW');
		if (this.instance !== null) {
			DaemonConfigurationService.updateInstance(this.componentName, this.instance, this.configuration)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createInstance(this.componentName, this.configuration)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	/**
	 * Handles REST API success
	 */
	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}

}
</script>
