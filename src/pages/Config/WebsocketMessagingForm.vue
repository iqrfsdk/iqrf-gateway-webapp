<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/messagings/websocket/add-messaging"'>
			{{ $t('config.daemon.messagings.websocket.messaging.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.daemon.messagings.websocket.messaging.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{invalid}'>
					<CForm @submit.prevent='saveInstance'>
						<ValidationProvider
							v-slot='{errors, touched, valid}'
							rules='required'
							:custom-messages='{
								required: "config.daemon.messagings.websocket.errors.messagingInstance"
							}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("forms.fields.instanceName")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.acceptAsyncMsg'
							:label='$t("config.daemon.messagings.acceptAsyncMsg")'
						/>
						<h4>{{ $t('config.daemon.messagings.websocket.form.requiredInterfaces') }}</h4>
						<div
							v-for='(iface, i) of configuration.RequiredInterfaces'
							:key='i'
							class='form-group'
						>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{
									required: "config.daemon.messagings.websocket.errors.interfaceName"
								}'
							>
								<CSelect
									:value.sync='iface.name'
									:label='$t("config.daemon.messagings.websocket.form.requiredInterface.name")'
									:placeholder='$t("config.daemon.messagings.websocket.errors.interfaceName")'
									:options='[
										{value: "shape::IWebsocketService", label: "shape::IWebsocketService"}
									]'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{
									required: "config.daemon.messagings.websocket.errors.interfaceName"
								}'
							>
								<CSelect
									:value.sync='iface.instance'
									:label='$t("config.daemon.messagings.websocket.form.requiredInterface.instance")'
									:placeholder='$t("config.daemon.messagings.websocket.errors.interfaceInstance")'
									:options='services'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								v-if='configuration.RequiredInterfaces.length > 1'
								color='danger'
								@click='removeInterface(i-1)'
							>
								{{ $t('config.daemon.messagings.websocket.form.requiredInterface.remove') }}
							</CButton>
							<CButton
								v-if='i === (configuration.RequiredInterfaces.length - 1)'
								color='success'
								:disabled='!iface.name || !iface.instance'
								@click='addInterface'
							>
								{{ $t('config.daemon.messagings.websocket.form.requiredInterface.add') }}
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
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox, CSelect, CSwitch} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {required} from 'vee-validate/dist/rules';
import { ModalInstance, IWsService } from '../../interfaces/messagingInterfaces';
import { AxiosError, AxiosResponse } from 'axios';
import { MetaInfo } from 'vue-meta';
import { RequiredInterface } from '../../interfaces/requiredInterfaces';

interface ServiceInstance {
	label: string
	value: string
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
		CCardHeader,
		CForm,
		CInput,
		CInputCheckbox,
		CSelect,
		CSwitch,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as WebsocketMessagingForm).pageTitle
		};
	}
})

/**
 * Daemon WebSocket messaging component configuration form
 */
export default class WebsocketMessagingForm extends Vue {
	/**
	 * @constant {ModalInstance} componentNames Names of WebSocket messaging and service components
	 */
	private componentNames: ModalInstance = {
		messaging: 'iqrf::WebsocketMessaging',
		service: 'shape::WebsocketCppService',
	}

	/**
	 * @var {LocalWsMessaging} configuration WebSocket messaging component configuration
	 */
	private configuration: LocalWsMessaging = {
		component: '',
		instance: '',
		acceptAsyncMsg: false,
		RequiredInterfaces: [{instance: '', name: ''}],
	}

	/**
	 * @var {Array<ServiceInstance>} services Array of WebSocket service component instances
	 */
	private services: Array<ServiceInstance> = []

	/**
	 * @property {string} instance Name of WebSocket messaging component instance
	 */
	@Prop({required: false, default: ''}) instance!: string

	/**
	 * Computes page title depending on the action (add, edit)
	 * @returns {string} Page title
	 */
	get pageTitle(): string {
		return this.$route.path === '/config/daemon/messagings/websocket/add-messaging' ?
			this.$t('config.daemon.messagings.websocket.messaging.add').toString() : this.$t('config.daemon.messagings.websocket.messaging.edit').toString();
	}

	/**
	 * Computes the text of form submit button depending on the action (add, edit)
	 * @returns {string} Button text
	 */
	get submitButton(): string {
		return this.$route.path === '/config/daemon/messagings/websocket/add-messaging' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
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
		if (this.instance) {
			this.getConfig();
		} else {
			this.getServices();
		}
	}

	/**
	 * Adds a new RequiredInterface object to array of required interfaces
	 */
	private addInterface(): void  {
		this.configuration.RequiredInterfaces.push({instance: '', name: ''});
	}

	/**
	 * Removes a RequiredInterface instance specified by index
	 * @param {number} index Index of RequiredInterface instance
	 */
	private removeInterface(index: number) {
		this.configuration.RequiredInterfaces.splice(index, 1);
	}

	/**
	 * Retrieves configuration of WebSocket messaging component instance and service component
	 */
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
				responses[1].data.instances.forEach((item: IWsService) => {
					this.services.push({value: item.instance, label: item.instance});
				});
			});
	}

	/**
	 * Retrieves instances of WebSocket service component
	 */
	private getServices(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.componentNames.service)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				response.data.instances.forEach((item: IWsService) => {
					this.services.push({value: item.instance, label: item.instance});
				});
			});
	}

	/**
	 * Saves new or updates existing configuration of WebSocket messaging component instance
	 */
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
		if (this.instance !== '') {
			DaemonConfigurationService.updateInstance(this.componentNames.messaging, this.instance, instance)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createInstance(this.componentNames.messaging, instance)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	/**
	 * Handles successful REST API response
	 */
	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/messagings/websocket/add-messaging') {
			this.$toast.success(
				this.$t('config.daemon.messagings.websocket.messaging.messages.addSuccess', {messaging: this.configuration.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.daemon.messagings.websocket.messaging.messages.editSuccess', {messaging: this.instance})
					.toString()
			);
		}
		this.$router.push('/config/daemon/messagings/websocket');
	}
}
</script>
