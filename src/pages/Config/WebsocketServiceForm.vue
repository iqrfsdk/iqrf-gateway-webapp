<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/messagings/websocket/add-service"'>
			{{ $t('config.daemon.messagings.websocket.service.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.daemon.messagings.websocket.service.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{invalid}'>
					<CForm @submit.prevent='saveInstance'>
						<CRow>
							<CCol md='6'>
								<legend>{{ $t('config.daemon.messagings.websocket.service.legend') }}</legend>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='required'
									:custom-messages='{required: "config.daemon.messagings.websocket.errors.serviceInstance"}'
								>
									<CInput
										v-model='componentInstance'
										:label='$t("forms.fields.instanceName")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='integer|between:1,65535|required'
									:custom-messages='{
										between: "config.daemon.messagings.websocket.errors.WebsocketPortRange",
										required: "config.daemon.messagings.websocket.errors.WebsocketPort",
										integer: "forms.errors.integer",
									}'
								>
									<CInput
										v-model.number='WebsocketPort'
										type='number'
										:label='$t("config.daemon.messagings.websocket.form.WebsocketPort")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<CInputCheckbox
									:checked.sync='acceptOnlyLocalhost'
									:label='$t("config.daemon.messagings.websocket.form.acceptOnlyLocalhost")'
								/>
							</CCol>
							<CCol md='6'>
								<div v-if='daemon230'>
									<label style='font-size: 1.5rem'>
										{{ $t('config.daemon.messagings.websocket.form.tlsEnabled') }}
									</label>
									<CSwitch
										color='primary'
										size='lg'
										shape='pill'
										label-on='ON'
										label-off='OFF'
										:checked.sync='tlsEnabled'
										style='float: right;'
									/>
									<ValidationProvider
										v-if='tlsEnabled'
										v-slot='{errors, touched, valid}'
										rules='required'
										:custom-messages='{
											required: "config.daemon.messagings.websocket.errors.tlsMode",
										}'
									>
										<CSelect
											:value.sync='tlsMode'
											:label='$t("config.daemon.messagings.websocket.form.tlsMode")'
											:options='tlsModeOptions'
											:placeholder='$t("config.daemon.messagings.websocket.errors.tlsMode")'
											:disabled='!tlsEnabled'
											:is-valid='touched && tlsEnabled ? valid : null'
											:invalid-feedback='$t(errors[0])'
										/>
										<p
											v-if='tlsMode !== "" && tlsMode !== undefined'
											:class='!tlsEnabled ? "text-secondary" : ""'
										>
											{{ $t('config.daemon.messagings.websocket.form.tlsModes.descriptions.' + tlsMode) }}
										</p>
									</ValidationProvider>
									<ValidationProvider
										v-if='tlsEnabled'
										v-slot='{errors, touched, valid}'
										rules='required'
										:custom-messages='{
											required: "config.daemon.messagings.websocket.errors.certificate",
										}'
									>
										<CInput
											v-model='certificate'
											:label='$t("forms.fields.certificate")'
											:disabled='!tlsEnabled'
											:is-valid='touched && tlsEnabled ? valid : null'
											:invalid-feedback='$t(errors[0])'
										/>
									</ValidationProvider>
									<ValidationProvider
										v-if='tlsEnabled'
										v-slot='{errors, touched, valid}'
										rules='required'
										:custom-messages='{
											required: "config.daemon.messagings.websocket.errors.privateKey",
										}'
									>
										<CInput
											v-model='privateKey'
											:label='$t("forms.fields.privateKey")'
											:disabled='!tlsEnabled'
											:is-valid='touched && tlsEnabled ? valid : null'
											:invalid-feedback='$t(errors[0])'
										/>
									</ValidationProvider>
								</div>
							</CCol>
						</CRow>
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
import {Component, Prop, Vue, Watch} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox, CSelect, CSwitch} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {IWsService} from '../../interfaces/messagingInterfaces';
import {AxiosError, AxiosResponse } from 'axios';
import {MetaInfo} from 'vue-meta';
import {IOption} from '../../interfaces/coreui';
import {versionHigherEqual} from '../../helpers/versionChecker';
import {mapGetters} from 'vuex';

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
	computed: {
		...mapGetters({
			daemonVersion: 'daemonVersion',
		}),
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as WebsocketServiceForm).pageTitle
		};
	}
})

/**
 * Daemon WebsocketService component configuration form
 */
export default class WebsocketServiceForm extends Vue {
	/**
	 * @var {boolean} acceptOnlyLocalhost Accept connections only from localhost?
	 */
	private acceptOnlyLocalhost = false

	/**
	 * @var {string} certificate Path to certificate for TLS
	 */
	private certificate = ''

	/**
	 * @var {string} component WebsocketService component name
	 */
	private component = ''

	/**
	 * @var {string} componentInstance WebsocketService component instance name
	 */
	private componentInstance = ''

	/**
	 * @constant {string} componentName Name of WebSocket service component
	 */
	private componentName = 'shape::WebsocketCppService'

	/**
	 * @var {boolean} daemon230 Indicates whether Daemon version is 2.3.0 or higher
	 */
	private daemon230 = false

	/**
	 * @var {string} privateKey Path to private key for TLS
	 */
	private privateKey = ''

	/**
	 * @var {boolean} tlsEnabled Use TLS?
	 */
	private tlsEnabled = false

	/**
	 * @var {string} tlsMode TLS operating mode
	 */
	private tlsMode = ''

	/**
	 * @constant {Array<IOption>} tlsModeOptions Array of CoreUI select options
	 */
	private tlsModeOptions: Array<IOption> = []

	/**
	 * @var {number} WebsocketPort Websocket port
	 */
	private WebsocketPort = 1338

	/**
	 * @property {string} instance WebSocket service component instance name
	 */
	@Prop({required: false, default: ''}) instance!: string

	/**
	 * Computes page title depending on the action (add, edit)
	 * @returns {string} Page title
	 */
	get pageTitle(): string {
		return this.$route.path === '/config/daemon/messagings/websocket/add-service' ?
			this.$t('config.daemon.messagings.websocket.service.add').toString() : this.$t('config.daemon.messagings.websocket.service.edit').toString();
	}

	/**
	 * Computes the text of form submit button depending on the action (add, edit)
	 * @returns {string} Button text
	 */
	get submitButton(): string {
		return this.$route.path === '/config/daemon/messagings/websocket/add-service' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
	}

	/**
	 * Daemon version computed property watcher to re-render elements dependent on version
	 */
	@Watch('daemonVersion')
	private updateForm(): void {
		if (versionHigherEqual('2.3.0')) {
			this.daemon230 = true;
			this.tlsModeOptions = [
				{
					value: 'intermediate',
					label: this.$t('config.daemon.messagings.websocket.form.tlsModes.intermediate').toString()
				},
				{
					value: 'modern',
					label: this.$t('config.daemon.messagings.websocket.form.tlsModes.modern').toString()
				},
				{
					value: 'old',
					label: this.$t('config.daemon.messagings.websocket.form.tlsModes.old').toString()
				}
			];
		}
	}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		this.updateForm();
		if (this.instance) {
			this.getInstance();
		}
	}

	/**
	 * Retrieves instance of WebSocket service component
	 */
	private getInstance(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentName, this.instance)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.parseConfiguration(response.data);
			})
			.catch((error: AxiosError) => {
				this.$router.push('/config/daemon/messagings/websocket');
				FormErrorHandler.configError(error);
			});
	}

	/**
	 * Parses WebsocketService component instance configration from REST API response
	 * @param {IWsService} response Configuration object from REST API response
	 */
	private parseConfiguration(response: IWsService): void {
		this.component = response.component;
		this.instance = this.componentInstance = response.instance;
		this.WebsocketPort = response.WebsocketPort;
		this.acceptOnlyLocalhost = response.acceptOnlyLocalhost;
		if (this.daemon230) {
			if (response.tlsEnabled !== undefined) {
				this.tlsEnabled = response.tlsEnabled;
			}
			if (response.tlsMode !== undefined) {
				this.tlsMode = response.tlsMode;
			}
			if (response.certificate !== undefined) {
				this.certificate = response.certificate;
			}
			if (response.privateKey !== undefined) {
				this.privateKey = response.privateKey;
			}
		}
	}

	/**
	 * Creates WebsocketService component instance configuration object
	 * @returns {IwsService} WebsocketService configuration
	 */
	private buildConfiguration(): IWsService {
		let configuration: IWsService = {
			component: this.component,
			instance: this.componentInstance,
			WebsocketPort: this.WebsocketPort,
			acceptOnlyLocalhost: this.acceptOnlyLocalhost
		};
		if (this.daemon230) {
			Object.assign(configuration, {tlsEnabled: this.tlsEnabled, tlsMode: this.tlsMode, certificate: this.certificate, privateKey: this.privateKey});
		}
		return configuration;
	}

	/**
	 * Saves new or updates existing configuration of WebSocket service component instance
	 */
	private saveInstance(): void {
		this.$store.commit('spinner/SHOW');
		if (this.instance !== '') {
			DaemonConfigurationService.updateInstance(this.componentName, this.instance, this.buildConfiguration())
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createInstance(this.componentName, this.buildConfiguration())
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	/**
	 * Handles successful REST API response
	 */
	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/messagings/websocket/add-service') {
			this.$toast.success(
				this.$t('config.daemon.messagings.websocket.service.messages.addSuccess', {service: this.componentInstance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.daemon.messagings.websocket.service.messages.editSuccess', {service: this.instance})
					.toString()
			);
		}
		this.$router.push('/config/daemon/messagings/websocket');
	}
}
</script>
