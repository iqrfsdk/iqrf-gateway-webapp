<template>
	<div>
		<h1 v-if='$route.path === "/config/udp/add"'>
			{{ $t('config.udp.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.udp.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.udp.form.messages.instance"}'
					>
						<CInput
							v-model='configuration.instance'
							:label='$t("config.udp.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required|between:1,65535'
						:custom-messages='{
							between: "config.udp.form.messages.RemotePort",
							required: "config.udp.form.messages.RemotePort",
						}'
					>
						<CInput
							v-model.number='configuration.RemotePort'
							:label='$t("config.udp.form.RemotePort")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							min='1'
							max='65535'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required|between:1,65535'
						:custom-messages='{
							between: "config.udp.form.messages.LocalPort",
							required: "config.udp.form.messages.LocalPort",
						}'
					>
						<CInput
							v-model.number='configuration.LocalPort'
							:label='$t("config.udp.form.LocalPort")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							min='1'
							max='65535'
						/>
					</ValidationProvider>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ submitButton }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CForm, CInput} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {between, required} from 'vee-validate/dist/rules';
import { UdpInstance } from '../../interfaces/messagingInterfaces';
import { MetaInfo } from 'vue-meta';
import { AxiosError, AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as UdpMessagingForm).pageTitle
		};
	}
})

export default class UdpMessagingForm extends Vue {
	private componentName = 'iqrf::UdpMessaging'
	private configuration: UdpInstance = {
		component: '',
		instance: '',
		RemotePort: 55000,
		LocalPort: 55300
	}

	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/udp/add' ?
			this.$t('config.udp.add').toString() : this.$t('config.udp.edit').toString();
	}

	get submitButton(): string {
		return this.$route.path === '/config/udp/add' ?
			this.$t('forms.add').toString() : this.$t('forms.save').toString();
	}

	mounted(): void {
		extend('between', between);
		extend('required', required);
		if (this.instance !== null) {
			this.getConfig();
		}
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentName, this.instance)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data;
			})
			.catch((error: AxiosError) => {
				this.$router.push('/config/udp/');
				FormErrorHandler.configError(error);
			});
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
		this.$router.push('/config/udp/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/udp/add') {
			this.$toast.success(
				this.$t('config.udp.messages.add.success', {instance: this.configuration.instance})
					.toString());
		} else {
			this.$toast.success(
				this.$t('config.udp.messages.edit.success', {instance: this.configuration.instance})
					.toString()
			);
		}
	}
}
</script>
