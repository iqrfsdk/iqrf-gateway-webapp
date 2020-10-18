<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.iqrfRepository.title') }}</h3></CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveConfig'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfRepository.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.iqrfRepository.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfRepository.form.messages.urlRepo"}'
						>
							<CInput
								v-model='configuration.urlRepo'
								:label='$t("config.iqrfRepository.form.urlRepo")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required|min:0'
							:custom-messages='{
								integer: "forms.messages.integer",
								required: "config.iqrfRepository.form.messages.checkPeriod",
								min: "config.iqrfRepository.form.messages.checkPeriod"
							}'
						>
							<CInput
								v-model.number='configuration.checkPeriodInMinutes'
								type='number'
								min='0'
								:label='$t("config.iqrfRepository.form.checkPeriod")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.downloadIfRepoCacheEmpty'
							:label='$t("config.iqrfRepository.form.downloadIfEmpty")'
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
import {integer, min_value, required} from 'vee-validate/dist/rules';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService	from '../../services/DaemonConfigurationService';

interface IqrfRepositoryConfig {
	instance: string|null
	urlRepo: string|null
	checkPeriodInMinutes: number
	downloadIfRepoCacheEmpty: boolean
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
		ValidationProvider,
	},
	metaInfo: {
		title: 'config.iqrfRepository.title',
	},
})

export default class IqrfRepository extends Vue {
	private componentName = 'iqrf::JsCache'
	private instance: string|null = null
	private configuration: IqrfRepositoryConfig = {
		instance: null,
		urlRepo: null,
		checkPeriodInMinutes: 0,
		downloadIfRepoCacheEmpty: true,
	}

	mounted(): void {
		extend('integer', integer);
		extend('min', min_value);
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
