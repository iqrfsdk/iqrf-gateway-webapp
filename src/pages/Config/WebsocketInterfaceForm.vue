<template>
	<div>
		<h1 v-if='$route.path === "/config/websocket/add"'>
			{{ $t('config.websocket.interface.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.websocket.interface.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mq.form.messages.instance"}'
					>
						<CInput
							v-model='messaging.instance'
							:label='$t("config.websocket.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required'
						:custom-messages='{
							required: "config.websocket.form.messages.WebsocketPort",
							integer: "forms.messages.integer"
						}'
					>
						<CInput
							v-model.number='service.WebsocketPort'
							type='number'
							:label='$t("config.websocket.form.WebsocketPort")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='service.acceptOnlyLocalhost'
						:label='$t("config.websocket.form.acceptOnlyLocalhost")'
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
import {integer, required} from 'vee-validate/dist/rules';
import { WsMessaging, ModalInstance, WsService } from '../../interfaces/messagingInterfaces';
import { MetaInfo } from 'vue-meta';
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
			title: (this as unknown as MonitorForm).pageTitle
		};
	}
})

export default class MonitorForm extends Vue {
	private componentNames: ModalInstance = {
		messaging: 'iqrf::WebsocketMessaging',
		service: 'shape::WebsocketCppService',
	}
	private instances: ModalInstance = {
		messaging: '',
		service: '',
	}
	private messaging: WsMessaging = {
		component: '',
		instance: '',
		acceptAsyncMsg: false,
		RequiredInterfaces: [],
	}
	private service: WsService = {
		component: '',
		instance: '',
		WebsocketPort: 1338,
		acceptOnlyLocalhost: false,
	}
	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/websocket/add' ?
			this.$t('config.websocket.interface.add').toString() : this.$t('config.websocket.interface.edit').toString();
	}
	
	get submitButton(): string {
		return this.$route.path === '/config/mq/add' ?
			this.$t('forms.add').toString() : this.$t('forms.save').toString();
	}

	mounted(): void {
		extend('integer', integer);
		extend('required', required);
		if (this.instance !== null) {
			this.getConfig();
		}
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentNames.messaging, this.instance)
			.then((response: AxiosResponse) => {
				this.messaging = response.data;
				this.instances.messaging = this.instance;
				this.instances.service = this.messaging.RequiredInterfaces[0].target.instance;
				DaemonConfigurationService.getInstance(this.componentNames.service, this.instances.service)
					.then((response: AxiosResponse) => {
						this.service = response.data;
						this.$store.commit('spinner/HIDE');
					});
			})
			.catch((error: AxiosError) => {
				this.$router.push('/config/daemon/');
				FormErrorHandler.configError(error);
			});
	}
	
	private saveConfig(): void {
		this.$store.commit('spinner/SHOW');
		this.service.instance = this.messaging.instance;
		if (this.messaging.RequiredInterfaces.length === 0) {
			this.messaging.RequiredInterfaces[0] = {
				name: 'shape::IWebsocketService',
				target: {
					instance: this.service.instance,
				},
			};
		} else {
			this.messaging.RequiredInterfaces[0].target.instance = this.messaging.instance;
		}
		if (this.instance === null) {
			Promise.all([
				DaemonConfigurationService.createInstance(this.componentNames.service, this.service),
				DaemonConfigurationService.createInstance(this.componentNames.messaging, this.messaging),
			])
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			Promise.all([
				DaemonConfigurationService.updateInstance(this.componentNames.service, this.instances.service, this.service),
				DaemonConfigurationService.updateInstance(this.componentNames.messaging, this.instances.messaging, this.messaging),
			])
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}
	private successfulSave(): void {
		this.$router.push('/config/daemon/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/websocket/add') {
			this.$toast.success(
				this.$t('config.websocket.messages.add.success', {instance: this.messaging.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.websocket.messages.edit.success', {instance: this.messaging.instance})
					.toString()
			);
		}
	}
}
</script>
