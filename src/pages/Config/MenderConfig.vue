<template>
	<div>
		<h1>{{ $t('config.mender.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-if='config !== null' v-slot='{ invalid }'>
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
							v-model='config.ServerURL'
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
							v-model='config.TenantToken'
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
							v-model.number='config.InventoryPollIntervalSeconds'
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
							v-model.number='config.RetryPollIntervalSeconds'
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
							v-model.number='config.UpdatePollIntervalSeconds'
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
import Vue from 'vue';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CForm, CInput} from '@coreui/vue/src';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import FeatureConfigService from '../../services/FeatureConfigService';

export default Vue.extend({
	name: 'MenderConfig',
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider
	},
	data(): any {
		return {
			name: 'mender',
			config: null,
		};
	},
	created() {
		extend('integer', integer);
		extend('required', required);
		extend('min', min_value);
		extend('addr', (addr) => {
			const regex = RegExp('(http|https):\\/\\/.*');
			return regex.test(addr);
		});
		this.getConfig();
	},
	methods: {
		getConfig() {
			this.$store.commit('spinner/SHOW');
			FeatureConfigService.getConfig(this.name)
				.then((response: AxiosResponse) => {
					this.$store.commit('spinner/HIDE');
					this.config = response.data;
				})
				.catch((error: AxiosError) => {
					FormErrorHandler.configError(error);
				});
		},
		processSubmit() {
			this.$store.commit('spinner/SHOW');
			FeatureConfigService.saveConfig(this.name, this.config)
				.then(() => {
					this.$store.commit('spinner/HIDE');
					this.$toast.success(this.$t('forms.messages.saveSuccess').toString());
				})
				.catch((error: AxiosError) => {
					FormErrorHandler.configError(error);
				});
		},
	},
	beforeRouteEnter(to, from, next) {
		next(vm => {
			if (!vm.$store.getters['features/isEnabled']('pixla')) {
				vm.$toast.error(
					vm.$t('config.mender.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	metaInfo: {
		title: 'config.mender.title',
	},
});
</script>