<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/misc/tracer/add"'>
			{{ $t('config.daemon.misc.tracer.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.daemon.misc.tracer.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{invalid}'>
				<CForm @submit.prevent='saveInstance'>
					<CRow>
						<CCol md='6'>
							<legend>{{ $t("config.daemon.misc.tracer.form.title") }}</legend>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{required: "config.daemon.misc.tracer.errors.instance"}'
							>
								<CInput
									v-model='componentInstance'
									:label='$t("forms.fields.instanceName")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CInput
								v-model='path'
								:label='$t("config.daemon.misc.tracer.form.path")'
							/>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{required: "config.daemon.misc.tracer.errors.filename"}'
							>
								<CInput
									v-model='filename'
									:label='$t("config.daemon.misc.tracer.form.filename")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='integer|required|min:1'
								:custom-messages='{
									required: "config.daemon.misc.tracer.errors.maxSizeMb",
									min: "config.daemon.misc.tracer.errors.maxSizeMb",
									integer: "forms.errors.integer"
								}'
							>
								<CInput
									v-model.number='maxSize'
									type='number'
									:label='$t("config.daemon.misc.tracer.form.maxSize")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<div class='form-group'>
								<label for='timestampFilesEnable'>
									{{ $t('config.daemon.misc.tracer.form.timestampFiles') }}
								</label><br>
								<CSwitch
									id='timestampFilesEnable'
									color='primary'
									size='lg'
									shape='pill'
									label-on='ON'
									label-off='OFF'
									:checked.sync='timestampFiles'
								/>
							</div>
							<div 
								v-if='daemon230 && timestampFiles'
								class='form-group'
							>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='integer|min:0'
									:custom-messages='{
										integer: "forms.errors.integer",
										min: "config.daemon.misc.tracer.errors.maxAgeMinutes"
									}'
								>
									<CInput
										v-model.number='maxAgeMinutes'
										type='number'
										min='0'
										:label='$t("config.daemon.misc.tracer.form.maxAgeMinutes") + " *"'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='integer|min:0'
									:custom-messages='{
										integer: "forms.errors.integer",
										min: "config.daemon.misc.tracer.errors.maxNumber"
									}'
								>
									<CInput
										v-model.number='maxNumber'
										type='number'
										min='0'
										:label='$t("config.daemon.misc.tracer.form.maxNumber") + " *"'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<i>* {{ $t('config.daemon.misc.tracer.messages.zeroValues') }}</i>
							</div>
						</CCol>
						<CCol md='6'>
							<legend>{{ $t("config.daemon.misc.tracer.form.verbosityLevels.title") }}</legend>
							<div
								v-for='(level, i) of VerbosityLevels'
								:key='i'
								class='form-group'
							>
								<hr v-if='i > 0'>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='integer|required'
									:custom-messages='{
										integer: "forms.errors.integer",
										required: "config.daemon.misc.tracer.errors.verbosityLevels.channel"
									}'
								>
									<CInput
										v-model.number='level.channel'
										type='number'
										:label='$t("config.daemon.misc.tracer.form.channel")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{errors, touched, valid}'
									rules='required'
									:custom-messages='{required: "config.daemon.misc.tracer.errors.verbosityLevels.level"}'
								>
									<CSelect
										:value.sync='level.level'
										:label='$t("config.daemon.misc.tracer.form.level")'
										:placeholder='$t("config.daemon.misc.tracer.errors.verbosityLevels.level")'
										:options='selectOptions'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<CButton
									v-if='VerbosityLevels.length > 1'
									color='danger'
									@click='removeLevel(i)'
								>
									{{ $t('config.daemon.misc.tracer.form.verbosityLevels.remove') }}
								</CButton> <CButton
									v-if='i === (VerbosityLevels.length - 1)'
									color='success'
									:disabled='level.channel === undefined || level.level === undefined'
									@click='addLevel'
								>
									{{ $t('config.daemon.misc.tracer.form.verbosityLevels.add') }}
								</CButton>
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
import {CButton, CCard, CForm, CInput, CSelect, CSwitch} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import {MetaInfo} from 'vue-meta';
import {IOption} from '../../interfaces/coreui';
import {AxiosError, AxiosResponse} from 'axios';
import {ITracerFile, IVerbosityLevel} from '../../interfaces/tracerFile';
import {versionHigherEqual} from '../../helpers/versionChecker';
import {mapGetters} from 'vuex';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
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
			title: (this as unknown as TracerForm).pageTitle
		};
	}
})

/**
 * Daemon Logging service component configuration card
 */
export default class TracerForm extends Vue {
	/**
	 * @var {string} component Logging service component name
	 */
	private component = ''

	/**
	 * @var {string} componentInstance Logging service component instance name
	 */
	private componentInstance = ''

	/**
	 * @constant {string} componentName Logging service component name, used for REST API communication
	 */
	private componentName = 'shape::TraceFileService'

	/**
	 * @var {boolean} daemon230 Indicates whether Daemon version is 2.3.0 or higher
	 */
	private daemon230 = false

	/**
	 * @var {string} filename Name of file used by this Logging service component instance
	 */
	private filename = ''

	/**
	 * @var {number} maxSize Maximum size of Logging service component instance file
	 */
	private maxSize = 1048576

	/**
	 * @var {number} maxAgeMinutes Maximum lifespan of timestamped files in minutes, 0 means disabled
	 */
	private maxAgeMinutes = 0

	/**
	 * @var {number} maxNumber Maximum number of timestamped files, 0 means disabled
	 */
	private maxNumber = 0

	/**
	 * @var {string} msgId Daemon api message id
	 */
	private msgId = ''

	/**
	 * @var {string} path Path to directory containing Logging service files
	 */
	private path = ''

	/**
	 * @var {boolean} powerUser Indicates whether user role is power user
	 */
	private powerUser = false

	/**
	 * @var {string} timestampFiles Should Logging service files be timestamped?
	 */
	private timestampFiles = false

	/**
	 * @var {Array<IVerbosityLevel>} VerbosityLevels Logging service verbosity level settings
	 */
	private VerbosityLevels: Array<IVerbosityLevel> = [{channel: 0, level: 'INF'}]

	/**
	 * @constant {Array<IOption>} selectOptions Array of CoreUI logging severity select options
	 */
	private selectOptions: Array<IOption> = [
		{
			value: 'DBG',
			label: this.$t('forms.fields.messageLevel.debug')
		},
		{
			value: 'INF',
			label: this.$t('forms.fields.messageLevel.info')
		},
		{
			value: 'WAR',
			label: this.$t('forms.fields.messageLevel.warning')
		},
		{
			value: 'ERR',
			label: this.$t('forms.fields.messageLevel.error')
		},
	]

	/**
	 * @property {string} instance Logging service component instance name, used for REST API communication
	 */
	@Prop({required: false, default: ''}) instance!: string

	/**
	 * Computes page title depending on the action (add, edit)
	 * @returns {string} Page title
	 */
	get pageTitle(): string {
		return this.$route.path === '/config/daemon/misc/tracer/add' ?
			this.$t('config.daemon.misc.tracer.add').toString() : this.$t('config.daemon.misc.tracer.edit').toString();
	}

	/**
	 * Computes the text of form submit button depending on the action (add, edit)
	 * @returns {string} Button text
	 */
	get submitButton(): string {
		return this.$route.path === '/config/daemon/misc/tracer/add' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
	}

	/**
	 * Daemon version computed property watcher to re-render elements dependent on version
	 */
	@Watch('daemonVersion')
	private updateForm(): void {
		if (versionHigherEqual('2.3.0')) {
			this.daemon230 = true;
		}
	}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
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
			this.getInstance();
		}
	}

	/**
	 * Adds a logging severity level object
	 */
	private addLevel(): void {
		this.VerbosityLevels.push({channel: 0, level: 'INF'});
	}

	/**
	 * Removes a logging severity level object specified by index
	 * @param {number} index Index of logging severity object
	 */
	private removeLevel(index: number): void {
		this.VerbosityLevels.splice(index, 1);
	}

	/**
	 * Retrieves configuration of Logging service component instance
	 */
	private getInstance(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentName, this.instance)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.parseConfiguration(response);
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
				this.$router.push({
					name: 'misc',
					params: {
						tabName: 'tracer'
					}
				});
			});
	}

	/**
	 * Parses Logging service component instance data
	 */
	private parseConfiguration(response: AxiosResponse): void {
		const configuration = response.data;
		this.component = configuration.component;
		this.componentInstance = configuration.instance;
		this.path = configuration.path;
		this.filename = configuration.filename;
		this.timestampFiles = configuration.timestampFiles;
		this.VerbosityLevels = configuration.VerbosityLevels;
		if (this.daemon230) { // Daemon v2.3.0 supports new properties
			this.maxSize = configuration.maxSize ? configuration.maxSize : configuration.maxSizeMB;
			if (configuration.maxAgeMinutes) { // optional property
				this.maxAgeMinutes = configuration.maxAgeMinutes;
			}
			if (configuration.maxNumber) { // optional property
				this.maxNumber = configuration.maxNumber;
			}
		} else {
			this.maxSize = configuration.maxSizeMB;
		}
	}

	/**
	 * Creates a new Logging service configuration object
	 * @returns {ITracerFile} Logging service configuration
	 */
	private buildConfiguration(): ITracerFile {
		let configuration: ITracerFile = {
			component: this.component,
			instance: this.componentInstance,
			filename: this.filename,
			path: this.path,
			timestampFiles: this.timestampFiles,
			VerbosityLevels: this.VerbosityLevels
		};
		if (this.daemon230) { // Daemon version 2.3.0 or higher
			Object.assign(configuration, {maxSizeMB: this.maxSize}); // TODO: to be changed for version 2.4.0 as maxSize is currently not supported by Shape
			Object.assign(configuration, {maxAgeMinutes: this.timestampFiles ? this.maxAgeMinutes : 0});
			Object.assign(configuration, {maxNumber: this.timestampFiles ? this.maxNumber : 0});
		} else { // Demon version 2.2.2 or lower
			Object.assign(configuration, {maxSizeMB: this.maxSize});
		}
		return configuration;
	}

	/**
	 * Saves new or updates existing configuration of Logging service component instance
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
		if (this.$route.path === '/config/daemon/misc/tracer/add') {
			this.$toast.success(
				this.$t('config.daemon.misc.tracer.messages.addSuccess', {instance: this.componentInstance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.daemon.misc.tracer.messages.editSuccess', {instance: this.instance})
					.toString()
			);
		}
		this.$router.push({
			name: 'misc',
			params: {
				tabName: 'tracer'
			}
		});
	}

}
</script>
