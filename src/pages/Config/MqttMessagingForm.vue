<template>
	<div>
		<h1 v-if='$route.path === "/config/mqtt/add"'>
			{{ $t('config.mqtt.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.mqtt.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mqtt.form.messages.instance"}'
					>
						<CInput
							v-model='configuration.instance'
							:label='$t("config.mqtt.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mqtt.form.messages.BrokerAddr"}'
					>
						<CInput
							v-model='configuration.BrokerAddr'
							:label='$t("config.mqtt.form.BrokerAddr")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mqtt.form.messages.ClientId"}'
					>
						<CInput
							v-model='configuration.ClientId'
							:label='$t("config.mqtt.form.ClientId")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required'
						:custom-messages='{
							integer: "config.mqtt.form.messages.Persistence",
							required: "config.mqtt.form.messages.Persistence",
						}'
					>
						<CInput
							v-model.number='configuration.Persistence'
							:label='$t("config.mqtt.form.Persistence")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "config.mqtt.form.messages.QoS",
						}'
					>
						<CSelect
							:value.sync='configuration.Qos'
							:label='$t("config.mqtt.form.QoS")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:placeholder='$t("config.mqtt.form.messages.QoS")'
							:options='qosOptions'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mqtt.form.messages.TopicRequest"}'
					>
						<CInput
							v-model='configuration.TopicRequest'
							:label='$t("config.mqtt.form.TopicRequest")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mqtt.form.messages.TopicResponse"}'
					>
						<CInput
							v-model='configuration.TopicResponse'
							:label='$t("config.mqtt.form.TopicResponse")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInput
						v-model='configuration.User'
						:label='$t("config.mqtt.form.User")'
					/>
					<CInput
						v-model='configuration.Password'
						:label='$t("config.mqtt.form.Password")'
					/>
					<CInputCheckbox
						:checked.sync='configuration.EnabledSSL'
						:label='$t("config.mqtt.form.EnabledSSL")'
					/>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|min:0'
						:custom-messages='{
							integer: "config.mqtt.form.messages.KeepAliveInterval",
							min: "config.mqtt.form.messages.KeepAliveInterval",
						}'
					>
						<CInput
							v-model.number='configuration.KeepAliveInterval'
							:label='$t("config.mqtt.form.KeepAliveInterval")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							min='0'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|min:0'
						:custom-messages='{
							integer: "config.mqtt.form.messages.ConnectTimeout",
							min: "config.mqtt.form.messages.ConnectTimeout",
						}'
					>
						<CInput
							v-model.number='configuration.ConnectTimeout'
							:label='$t("config.mqtt.form.ConnectTimeout")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							min='0'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						:rules='"integer|between:0," + configuration.MaxReconnect'
						:custom-messages='{
							between: "config.mqtt.form.messages.MinReconnect",
							integer: "config.mqtt.form.messages.MinReconnect",
						}'
					>
						<CInput
							v-model.number='configuration.MinReconnect'
							:label='$t("config.mqtt.form.MinReconnect")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							:max='configuration.MaxReconnect'
							min='0'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						:rules='"integer|min:" + configuration.MinReconnect'
						:custom-messages='{
							integer: "config.mqtt.form.messages.MaxReconnect",
							min: "config.mqtt.form.messages.MaxReconnect",
						}'
					>
						<CInput
							v-model.number='configuration.MaxReconnect'
							:label='$t("config.mqtt.form.MaxReconnect")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='number'
							:min='configuration.MinReconnect'
						/>
					</ValidationProvider>
					<CInput
						v-model='configuration.TrustStore'
						:label='$t("config.mqtt.form.TrustStore")'
					/>
					<CInput
						v-model='configuration.KeyStore'
						:label='$t("config.mqtt.form.KeyStore")'
					/>
					<CInput
						v-model='configuration.PrivateKey'
						:label='$t("config.mqtt.form.PrivateKey")'
					/>
					<CInput
						v-model='configuration.PrivateKeyPassword'
						:label='$t("config.mqtt.form.PrivateKeyPassword")'
					/>
					<CInput
						v-model='configuration.EnabledCipherSuites'
						:label='$t("config.mqtt.form.EnabledCipherSuites")'
					/>
					<CInputCheckbox
						:checked.sync='configuration.EnableServerCertAuth'
						:label='$t("config.mqtt.form.EnableServerCertAuth")'
					/>
					<CInputCheckbox
						:checked.sync='configuration.acceptAsyncMsg'
						:label='$t("config.mqtt.form.acceptAsyncMsg")'
					/>
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
import {CButton, CCard, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {between, integer, min_value, required} from 'vee-validate/dist/rules';
import { MqttInstance } from '../../interfaces/messagingInterfaces';
import { MetaInfo } from 'vue-meta';
import { IOption } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as MqttMessagingForm).pageTitle
		};
	}
})

export default class MqttMessagingForm extends Vue {
	private componentName = 'iqrf::MqttMessaging'
	private configuration: MqttInstance = {
		component: '',
		instance: '',
		BrokerAddr: '',
		ClientId: '',
		Persistence: 1,
		Qos: 1,
		TopicRequest: '',
		TopicResponse: '',
		User: '',
		Password: '',
		EnabledSSL: false,
		KeepAliveInterval: 20,
		ConnectTimeout: 5,
		MinReconnect: 1,
		MaxReconnect: 64,
		TrustStore: '',
		KeyStore: '',
		PrivateKey: '',
		PrivateKeyPassword: '',
		EnabledCipherSuites: '',
		EnableServerCertAuth: false,
		acceptAsyncMsg: false,
	}
	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/mqtt/add' ?
			this.$t('config.mqtt.add').toString() : this.$t('config.mqtt.edit').toString();
	}

	get qosOptions(): Array<IOption> {
		const options = [0, 1, 2];
		return options.map((option) => {
			return {
				value: option,
				label: this.$t('config.mqtt.form.QoSes.' + option).toString(),
			};
		});
	}
	
	get submitButton(): string {
		return this.$route.path === '/config/mqtt/add' ?
			this.$t('forms.add').toString() : this.$t('forms.save').toString();
	}

	mounted(): void {
		extend('between', between);
		extend('integer', integer);
		extend('min', min_value);
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
				this.$store.commit('spinner/HIDE');
				this.$router.push('/config/mqtt/');
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
		this.$router.push('/config/mqtt/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/mqtt/add') {
			this.$toast.success(
				this.$t('config.mqtt.messages.add.success', {instance: this.configuration.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.mqtt.messages.edit.success', {instance: this.configuration.instance})
					.toString()
			);
		}
	}
}
</script>
