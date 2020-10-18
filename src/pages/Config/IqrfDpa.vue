<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.iqrfDpa.title') }}</h3></CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveConfig'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfDpa.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.iqrfDpa.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required|min:0'
							:custom-messages='{
								integer: "forms.messages.integer",
								min: "config.iqrfDpa.form.messages.DpaHandlerTimeout",
								required: "config.iqrfDpa.form.messages.DpaHandlerTimeout"
							}'
						>
							<CInput
								v-model.number='configuration.DpaHandlerTimeout'
								type='number'
								min='0'
								:label='$t("config.iqrfDpa.form.DpaHandlerTimeout")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
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
import {CButton, CCard, CCardBody, CForm, CInput} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';

interface IqrfDpaConfig {
	instance: string|null
	DpaHandlerTimeout: number
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider
	},
	metaInfo: {
		title: 'config.iqrfDpa.title',
	},
})

export default class IqrfDpa extends Vue {
	private componentName = 'iqrf::IqrfDpa'
	private configuration: IqrfDpaConfig = {
		instance: null,
		DpaHandlerTimeout: 500,
	}
	private instance: string|null = null

	mounted(): void {
		extend('integer', integer);
		extend('min', min_value);
		extend('required', required);
		this.getConfig();
	}

	private getConfig() {
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
