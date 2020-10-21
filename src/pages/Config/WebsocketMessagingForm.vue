<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/websocket/add-messaging"'>
			{{ $t('config.websocket.messaging.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.websocket.messaging.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveInstance'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.websocket.form.messages.messagingInstance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.websocket.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.acceptAsyncMsg'
							:label='$t("config.websocket.form.acceptAsyncMsg")'
						/>
						<h4>{{ $t('config.websocket.form.requiredInterfaces') }}</h4>
						<div
							v-for='(iface, i) of configuration.RequiredInterfaces'
							:key='i'
							class='form-group'
						>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required'
								:custom-messages='{required: "config.websocket.form.messages.interfaceName"}'
							>
								<CSelect
									:value.sync='iface.name'
									:label='$t("config.websocket.form.requiredInterface.name")'
									:placeholder='$t("config.websocket.form.messages.interfaceName")'
									:options='[
										{value: "shape::IWebsocketService", label: "shape::IWebsocketService"}
									]'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required'
								:custom-messages='{required: "config.websocket.form.messages.interfaceName"}'
							>
								<CSelect
									:value.sync='iface.instance'
									:label='$t("config.websocket.form.requiredInterface.instance")'
									:placeholder='$t("config.websocket.form.messages.interfaceInstance")'
									:options='instances.service'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								v-if='configuration.RequiredInterfaces.length > 1'
								color='danger'
								@click='removeInterface(i-1)'
							>
								{{ $t('config.websocket.form.requiredInterface.remove') }}
							</CButton>
							<CButton
								v-if='i === (configuration.RequiredInterfaces.length - 1)'
								color='success'
								:disabled='!iface.name || !iface.instance'
								@click='addInterface'
							>
								{{ $t('config.websocket.form.requiredInterface.add') }}
							</CButton>
						</div>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ submitButton }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {required} from 'vee-validate/dist/rules';
import { ModalInstance, WsService } from '../../interfaces/messagingInterfaces';
import { AxiosError, AxiosResponse } from 'axios';
import { MetaInfo } from 'vue-meta';
import { RequiredInterface } from '../../interfaces/requiredInterfaces';

interface ServiceInstance {
	label: string
	value: string
}

interface ServiceInstances {
	service: Array<ServiceInstance>
}

interface LocalRequiredInterface {
	instance: string
	name: string
}

interface LocalWsMessaging {
	component: string
	instance: string
	acceptAsyncMsg: boolean
	RequiredInterfaces: Array<LocalRequiredInterface>
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as WebsocketMessagingForm).pageTitle
		};
	}
})

export default class WebsocketMessagingForm extends Vue {
	private componentNames: ModalInstance = {
		messaging: 'iqrf::WebsocketMessaging',
		service: 'shape::WebsocketCppService',
	}
	private configuration: LocalWsMessaging = {
		component: '',
		instance: '',
		acceptAsyncMsg: false,
		RequiredInterfaces: [{instance: '', name: ''}],
	}
	private instances: ServiceInstances = {
		service: []
	}

	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/daemon/websocket/add-messaging' ?
			this.$t('config.websocket.messaging.add').toString() : this.$t('config.websocket.messaging.edit').toString();
	}

	get submitButton(): string {
		return this.$route.path === '/config/daemon/websocket/add-messaging' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
	}

	mounted(): void {
		extend('required', required);
		if (this.instance) {
			this.getConfig();
		} else {
			this.getServices();
		}
	}

	private addInterface(): void  {
		this.configuration.RequiredInterfaces.push({instance: '', name: ''});
	}

	private removeInterface(index: number) {
		this.configuration.RequiredInterfaces.splice(index, 1);
	}

	private getConfig() {
		this.$store.commit('spinner/SHOW');
		return Promise.all([
			DaemonConfigurationService.getInstance(this.componentNames.messaging, this.instance),
			DaemonConfigurationService.getComponent(this.componentNames.service),
		])
			.then((responses: Array<AxiosResponse>) => {
				this.$store.commit('spinner/HIDE');
				let interfaces: Array<LocalRequiredInterface> = [];
				let configuration = responses[0].data;
				configuration.RequiredInterfaces.forEach((item: RequiredInterface) => {
					interfaces.push({name: item.name, instance: item.target.instance});
				});
				configuration.RequiredInterfaces = interfaces;
				this.configuration = configuration;
				responses[1].data.instances.forEach((item: WsService) => {
					this.instances.service.push({value: item.instance, label: item.instance});
				});
			});
	}

	private getServices(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.componentNames.service)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				response.data.instances.forEach((item: WsService) => {
					this.instances.service.push({value: item.instance, label: item.instance});
				});
			});
	}

	private saveInstance(): void {
		let instance = {
			component: this.configuration.component,
			instance: this.configuration.instance,
			acceptAsyncMsg: this.configuration.acceptAsyncMsg,
			RequiredInterfaces: []
		};
		let RequiredInterfaces: Array<RequiredInterface> = [];
		this.configuration.RequiredInterfaces.forEach((item: LocalRequiredInterface) => {
			RequiredInterfaces.push({name: item.name, target: {instance: item.instance}});
		});
		Object.assign(instance.RequiredInterfaces, RequiredInterfaces);
		this.$store.commit('spinner/SHOW');
		if (this.instance !== null) {
			DaemonConfigurationService.updateInstance(this.componentNames.messaging, this.instance, instance)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createInstance(this.componentNames.messaging, instance)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	private successfulSave(): void {
		this.$router.push('/config/daemon/messagings/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/websocket/add-messaging') {
			this.$toast.success(
				this.$t('config.websocket.messaging.messages.addSuccess', {messaging: this.configuration.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.websocket.messaging.messages.editSuccess', {messaging: this.instance})
					.toString()
			);
		}
	}
}
</script>
