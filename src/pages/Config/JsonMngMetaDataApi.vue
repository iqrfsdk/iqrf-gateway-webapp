<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.jsonMngMetaDataApi.title') }}</h3></CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveConfig'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.jsonMngMetaDataApi.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.jsonMngMetaDataApi.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.metaDataToMessages'
							:label='$t("config.jsonMngMetaDataApi.form.metaDataToMessages")'
						/>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';

interface JsonMngMetaDataApiConfig {
	instance: string|null
	metaDataToMessages: boolean
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider
	},
	metaInfo: {
		title: 'config.jsonMngMetaDataApi.title',
	},
})

export default class JsonMngMetaDataApi extends Vue {
	private componentName = 'iqrf::JsonMngMetaDataApi'
	private instance: string|null = null
	private configuration: JsonMngMetaDataApiConfig = {
		instance: null,
		metaDataToMessages: false,
	}

	mounted(): void {
		extend('required', required);
		this.getConfig();
	}

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
	
	private saveConfig(): void {
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

	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}
}
</script>
