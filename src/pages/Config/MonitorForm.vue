<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/misc/monitor/add"'>
			{{ $t('config.daemon.misc.monitor.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.daemon.misc.monitor.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{invalid}'>
				<CForm @submit.prevent='saveConfig'>
					<CRow>
						<CCol md='6'>
							<legend>{{ $t('config.daemon.misc.monitor.form.title') }}</legend>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{required: "config.daemon.misc.monitor.errors.instance"}'
							>
								<CInput
									v-model='monitor.instance'
									:label='$t("forms.fields.instanceName")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='integer|min:1|required'
								:custom-messages='{
									required: "config.daemon.misc.monitor.errors.reportPeriod",
									min: "config.daemon.misc.monitor.errors.reportPeriod",
									integer: "forms.errors.integer"
								}'
							>
								<CInput
									v-model.number='monitor.reportPeriod'
									type='number'
									:label='$t("config.daemon.misc.monitor.form.reportPeriod")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='integer|between:1,65535|required'
								:custom-messages='{
									between: "config.daemon.messagings.websocket.errors.WebsocketPortRange",
									required: "config.daemon.misc.monitor.errors.WebsocketPort",
									integer: "forms.errors.integer"
								}'
							>
								<CInput
									v-model.number='webSocket.WebsocketPort'
									type='number'
									:label='$t("config.daemon.misc.monitor.form.WebsocketPort")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CInputCheckbox
								:checked.sync='webSocket.acceptOnlyLocalhost'
								:label='$t("config.daemon.misc.monitor.form.acceptOnlyLocalhost")'
							/>
						</CCol>
						<CCol md='6'>
							<div v-if='daemon230'>
								<label style='font-size: 1.5rem'>
									{{ $t('config.daemon.messagings.tlsTitle') }}
								</label>
								<CSwitch
									color='primary'
									size='lg'
									shape='pill'
									label-on='ON'
									label-off='OFF'
									:checked.sync='webSocket.tlsEnabled'
									style='float: right;'
								/>
								<ValidationProvider
									v-if='webSocket.tlsEnabled'
									v-slot='{errors, touched, valid}'
									rules='required'
									:custom-messages='{
										required: "config.daemon.messagings.websocket.errors.tlsMode",
									}'
								>
									<CSelect
										:value.sync='webSocket.tlsMode'
										:label='$t("config.daemon.messagings.websocket.form.tlsMode")'
										:options='tlsModeOptions'
										:placeholder='$t("config.daemon.messagings.websocket.errors.tlsMode")'
										:disabled='!webSocket.tlsEnabled'
										:is-valid='touched && webSocket.tlsEnabled ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
									<p
										v-if='webSocket.tlsMode !== "" && webSocket.tlsMode !== undefined'
										:class='!webSocket.tlsEnabled ? "text-secondary" : ""'
									>
										{{ $t('config.daemon.messagings.websocket.form.tlsModes.descriptions.' + webSocket.tlsMode) }}
									</p>
								</ValidationProvider>
								<ValidationProvider
									v-if='webSocket.tlsEnabled'
									v-slot='{errors, touched, valid}'
									rules='required'
									:custom-messages='{
										required: "config.daemon.messagings.websocket.errors.certificate",
									}'
								>
									<CInput
										v-model='webSocket.certificate'
										:label='$t("forms.fields.certificate")'
										:disabled='!webSocket.tlsEnabled'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-if='webSocket.tlsEnabled'
									v-slot='{errors, touched, valid}'
									rules='required'
									:custom-messages='{
										required: "config.daemon.messagings.websocket.errors.privateKey",
									}'
								>
									<CInput
										v-model='webSocket.privateKey'
										:label='$t("forms.fields.privateKey")'
										:disabled='!webSocket.tlsEnabled'
										:is-valid='touched ? valid : null'
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
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue, Watch} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {between, integer, required, min_value} from 'vee-validate/dist/rules';
import {MetaInfo} from 'vue-meta';
import {RequiredInterface} from '../../interfaces/requiredInterfaces';
import {AxiosError, AxiosResponse} from 'axios';
import {versionHigherEqual} from '../../helpers/versionChecker';
import {IOption} from '../../interfaces/coreui';
import {mapGetters} from 'vuex';

interface MonitorComponents {
	monitor: string
	webSocket: string
}

interface MonitorInstance {
	component: string
	instance: string
	reportPeriod: number
	RequiredInterfaces: Array<RequiredInterface>
}

interface MonitorWebSocket {
	instance: string
	WebsocketPort: number
	acceptOnlyLocalhost: boolean
	tlsEnabled?: boolean
	tlsMode?: string
	certificate?: string
	privateKey?: string
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
			title: (this as unknown as MonitorForm).pageTitle
		};
	}
})

/**
 * Daemon monitoring component configuration card
 */
export default class MonitorForm extends Vue {
	/**
	 * @constant {MonitorComponents} componentNames Names of components required for the monitoring service
	 */
	private componentNames: MonitorComponents = {
		monitor: 'iqrf::MonitorService',
		webSocket: 'shape::WebsocketCppService',
	}

	/**
	 * @var {boolean} daemon230 Indicates that Daemon is version 2.3.0 or higher
	 */
	private daemon230 = false;

	/**
	 * @var {MonitorComponents} instances Names of component instances required for the monitoring service
	 */
	private instances: MonitorComponents = {
		monitor: '',
		webSocket: '',
	}

	/**
	 * @var {MonitorInstance} monitor Daemon monitoring instance configuration
	 */
	private monitor: MonitorInstance = {
		component: '',
		instance: '',
		reportPeriod: 10,
		RequiredInterfaces: []
	}

	/**
	 * @var {boolean} powerUser Indicates that the user role is power user
	 */
	private powerUser = false;

	/**
	 * @var {Array<IOption>} tlsModeOptions Array of CoreUI select options for TLS mode
	 */
	private tlsModeOptions: Array<IOption> = []

	/**
	 * @var {MonitorWebSocket} webSocket Daemon websocket instance configuration
	 */
	private webSocket: MonitorWebSocket = {
		instance: '',
		WebsocketPort: 1438,
		acceptOnlyLocalhost: false,
		tlsEnabled: false,
		tlsMode: '',
		certificate: '',
		privateKey: ''
	}

	/**
	 * @property {string} instance Monitoring service instance name
	 */
	@Prop({required: false, default: ''}) instance!: string

	/**
	 * Computes page title depending on the action (add, edit)
	 * @returns {string} Page title
	 */
	get pageTitle(): string {
		return this.$route.path === '/config/daemon/misc/monitor/add' ?
			this.$t('config.daemon.misc.monitor.add').toString() :
			this.$t('config.daemon.misc.monitor.edit').toString();
	}

	/**
	 * Computes form submit button text depending on the action (add, edit)
	 * @returns {string} Button text
	 */
	get submitButton(): string {
		return this.$route.path === '/config/daemon/misc/monitor/add' ?
			this.$t('forms.add').toString() :
			this.$t('forms.save').toString();
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
				},
			];
		}
	}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('min', min_value);
		extend('required', required);
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		this.updateForm();
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
		if (this.instance !== '') {
			this.getConfig();
		}
	}

	/**
	 * Retrieves configuration of the monitoring component and websocket instance
	 */
	private getConfig(): void {
		if (this.componentNames.monitor === '' || this.componentNames.webSocket === '') {
			return;
		}
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentNames.monitor, this.instance)
			.then((response: AxiosResponse) => {
				this.monitor = response.data;
				this.instances.monitor = this.instance;
				this.instances.webSocket = this.monitor.RequiredInterfaces[0].target.instance;
				DaemonConfigurationService.getInstance(this.componentNames.webSocket, this.instances.webSocket)
					.then((response: AxiosResponse) => {
						this.webSocket = response.data;
						this.$store.commit('spinner/HIDE');
					});
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
				this.$router.push({
					name: 'misc',
					params: {
						tabName: 'monitor'
					}
				});
			});
	}

	/**
	 * Saves new or updates existing configurations of monitoring and websocket component instances
	 */
	private saveConfig(): void {
		this.$store.commit('spinner/SHOW');
		if (!this.daemon230) {
			delete this.webSocket.tlsEnabled;
			delete this.webSocket.tlsMode;
			delete this.webSocket.certificate;
			delete this.webSocket.privateKey;
		}
		this.webSocket.instance = this.monitor.instance;
		if (this.monitor.RequiredInterfaces.length === 0) {
			this.monitor.RequiredInterfaces[0] = {
				name: 'shape::IWebsocketService',
				target: {
					instance: this.webSocket.instance,
				},
			};
		} else {
			this.monitor.RequiredInterfaces[0].target.instance = this.monitor.instance;
		}
		if (this.instance === '') {
			Promise.all([
				DaemonConfigurationService.createInstance(this.componentNames.webSocket, this.webSocket),
				DaemonConfigurationService.createInstance(this.componentNames.monitor, this.monitor),
			])
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			Promise.all([
				DaemonConfigurationService.updateInstance(this.componentNames.webSocket, this.instances.webSocket, this.webSocket),
				DaemonConfigurationService.updateInstance(this.componentNames.monitor, this.instances.monitor, this.monitor),
			])
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	/**
	 * Handles successful REST APi response
	 */
	private successfulSave() {
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/misc/monitor/add') {
			this.$toast.success(
				this.$t('config.daemon.misc.monitor.messages.addSuccess', {instance: this.monitor.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.daemon.misc.monitor.messages.editSuccess', {instance: this.monitor.instance})
					.toString()
			);
		}
		this.$router.push({
			name: 'misc',
			params: {
				tabName: 'monitor'
			}
		});
	}
}
</script>
