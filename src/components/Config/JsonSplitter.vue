<template>
	<CCard class='border-0 card-margin-bottom'>
		<CCardHeader>
			{{ $t('config.daemon.misc.jsonSplitter.title') }}
		</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.daemon.misc.jsonSplitter.errors.instance"}'
					>
						<CInput
							v-model='configuration.instance'
							:label='$t("forms.fields.instanceName")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{errors, touched, valid}'
						rules='required'
						:custom-messages='{
							required: "config.daemon.misc.jsonSplitter.errors.insId"
						}'
					>
						<CInput
							v-model='configuration.insId'
							:label='$t("config.daemon.misc.jsonSplitter.form.insId")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='configuration.validateJsonResponse'
						:label='$t("config.daemon.misc.jsonSplitter.form.validateJsonResponse")'
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
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {IJsonSplitter} from '../../interfaces/jsonApi';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider,
	}
})

/**
 * JSON Splitter component configuration
 */
export default class JsonSplitter extends Vue {
	/**
	 * @constant {string} componentName JSON Splitter component name
	 */
	private componentName = 'iqrf::JsonSplitter'

	/**
	 * @var {string} instance JSON Splitter component instance name
	 */
	private instance = ''

	/**
	 * @var {IJsonSplitter} configuration JSON Splitter component instance configuration
	 */
	private configuration: IJsonSplitter = {
		instance: '',
		validateJsonResponse: false,
		insId: '',
	}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('required', required);
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		this.getConfig();
	}
	
	/**
	 * Retrieves configuration of JSON splitter component
	 */
	private getConfig(): void {
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
	 * Saves new or updates existing configuration of JSON Splitter component instance
	 */
	private saveConfig(): void {
		this.$store.commit('spinner/SHOW');
		if (this.instance !== '') {
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
	 * Handles successful REST API response
	 */
	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}
}
</script>
