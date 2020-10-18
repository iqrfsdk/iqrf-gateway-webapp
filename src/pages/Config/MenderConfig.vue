<template>
	<div>
		<h1>{{ $t('config.mender.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-if='configuration !== null' v-slot='{ invalid }'>
				<CForm @submit.prevent='processSubmit'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='addr|required'
						:custom-messages='{
							addr: "config.mender.form.messages.invalid.server",
							required: "config.mender.form.messages.missing.server"
						}'
					>
						<CInput
							v-model='configuration.ServerURL'
							:label='$t("config.mender.form.server")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{
							required: "config.mender.form.messages.missing.tenantToken"
						}'
					>
						<CInput
							v-model='configuration.TenantToken'
							:label='$t("config.mender.form.tenantToken")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='min:0|required|integer'
						:custom-messages='{
							integer: "forms.messages.integer",
							min: "config.mender.form.messages.inventoryPollInterval",
							required: "config.mender.form.messages.inventoryPollInterval"
						}'
					>
						<CInput
							v-model.number='configuration.InventoryPollIntervalSeconds'
							type='number'
							min='0'
							:label='$t("config.mender.form.inventoryPollInterval")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='min:0|required|integer'
						:custom-messages='{
							integer: "forms.messages.integer",
							min: "config.mender.form.messages.retryPollInterval",
							required: "config.mender.form.messages.retryPollInterval"
						}'
					>
						<CInput
							v-model.number='configuration.RetryPollIntervalSeconds'
							type='number'
							min='0'
							:label='$t("config.mender.form.retryPollInterval")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid}'
						rules='min:0|required|integer'
						:custom-messages='{
							integer: "forms.messages.integer",
							min: "config.mender.form.messages.updatePollInterval",
							required: "config.mender.form.messages.updatePollInterval"
						}'
					>
						<CInput
							v-model.number='configuration.UpdatePollIntervalSeconds'
							type='number'
							min='0'
							:label='$t("config.mender.form.updatePollInterval")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CForm, CInput} from '@coreui/vue/src';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import FeatureConfigService from '../../services/FeatureConfigService';
import { NavigationGuardNext, Route } from 'vue-router';

interface IMenderConfig {
	InventoryPollIntervalSeconds: number|null
	RetryPollIntervalSeconds: number|null
	ServerURL: string|null
	TenantToken: string|null
	UpdatePollIntervalSeconds: number|null
}

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider
	},
	beforeRouteEnter(to: Route, from: Route, next: NavigationGuardNext): void {
		next((vm: Vue) => {
			if (!vm.$store.getters['features/isEnabled']('mender')) {
				vm.$toast.error(
					vm.$t('config.mender.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	metaInfo: {
		title: 'config.mender.description',
	},
})

export default class MenderConfig extends Vue {
	private configuration: IMenderConfig = {
		InventoryPollIntervalSeconds: null,
		RetryPollIntervalSeconds: null,
		ServerURL: null,
		TenantToken: null,
		UpdatePollIntervalSeconds: null,
	}
	private name = 'mender'

	mounted(): void {
		extend('integer', integer);
		extend('required', required);
		extend('min', min_value);
		extend('addr', (addr) => {
			const regex = RegExp('(http|https):\\/\\/.*');
			return regex.test(addr);
		});
		this.getConfig();
	}

	getConfig(): void {
		this.$store.commit('spinner/SHOW');
		FeatureConfigService.getConfig(this.name)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data;
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
			});
	}
	
	processSubmit(): void {
		this.$store.commit('spinner/SHOW');
		FeatureConfigService.saveConfig(this.name, this.configuration)
			.then(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.success(this.$t('forms.messages.saveSuccess').toString());
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
			});
	}
}
</script>
