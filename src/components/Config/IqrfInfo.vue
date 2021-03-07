<template>
	<CCard class='border-0 card-margin-bottom'>
		<CCardBody>
			<ValidationObserver v-slot='{invalid}'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-if='powerUser'
						v-slot='{errors, touched, valid}'
						rules='required'
						:custom-messages='{required: "config.daemon.misc.iqrfInfo.errors.instance"}'
					>
						<CInput
							v-model='componentInstance'
							:label='$t("forms.fields.instanceName")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<div class='form-group'>
						<label for='enumPeriodicEnable'>
							{{ $t("config.daemon.misc.iqrfInfo.form.enablePeriodic") }}
						</label><br>
						<CSwitch
							id='enumPeriodicEnable'
							color='primary'
							size='lg'
							shape='pill'
							label-on='ON'
							label-off='OFF'
							:checked.sync='enumPeriodic'
						/>
					</div>
					<ValidationProvider
						v-if='daemon230 && enumPeriodic'
						v-slot='{errors, touched, valid}'
						rules='integer|min:0|required'
						:custom-messages='{
							required: "config.daemon.misc.iqrfInfo.errors.enumPeriod",
							min: "config.daemon.misc.iqrfInfo.errors.enumPeriod",
							integer: "forms.errors.integer"
						}'
					>
						<CInput
							v-model.number='enumPeriod'
							type='number'
							min='0'
							:label='$t("config.daemon.misc.iqrfInfo.form.enumPeriod")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='enumAtStartUp'
						:label='$t("config.daemon.misc.iqrfInfo.form.enumAtStartUp")'
					/>
					<CInputCheckbox
						v-if='daemon230'
						:checked.sync='enumUniformDpaVer'
						:label='$t("config.daemon.misc.iqrfInfo.form.enumUniformDpaVer")'
					/>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue, Watch} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox, CSwitch} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {IIqrfInfo} from '../../interfaces/iqrfInfo';
import {mapGetters} from 'vuex';
import {versionHigherEqual} from '../../helpers/versionChecker';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		CInputCheckbox,
		CSwitch,
		ValidationObserver,
		ValidationProvider,
	},
	computed: {
		...mapGetters({
			daemonVersion: 'daemonVersion',
		}),
	}
})

/**
 * IQRF Info component configuration
 */
export default class IqrfInfo extends Vue {
	/**
	 * @var {string} componentInstance IQRF Info component instance name
	 */
	private componentInstance = ''

	/**
	 * @constant {string} componentName IQRF Info component name
	 */
	private componentName = 'iqrf::IqrfInfo'

	/**
	 * @var {boolean} daemon230 Indicates whether Daemon version is 2.3.0 or higher
	 */
	private daemon230 = false

	/**
	 * @var {boolean} enumAtStartUp Enumerate network after startup?
	 */
	private enumAtStartUp = false

	/**
	 * @var {boolean} enumPeriodic Enumerate network periodically?
	 */
	private enumPeriodic = false

	/**
	 * @var {number} enumPeriod Enumeration period in minutes
	 */
	private enumPeriod = 0

	/**
	 * @var {boolean} enumUniformDpaVer Uniform DPA version and OS build enumeration?
	 */
	private enumUniformDpaVer = false

	/**
	 * @var {string} instance IQRF Info component instance name, used for REST API communication
	 */
	private instance = ''

	/**
	 * @var {boolean} powerUser Indicates whether user role is power user
	 */
	private powerUser = false

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
		this.getConfig();
	}

	/**
	 * Retrieves configuration of IQRF Info component
	 */
	private getConfig(): Promise<void> {
		this.$store.commit('spinner/SHOW');
		return DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				if (response.data.instances.length > 0) {
					this.parseConfiguration(response.data.instances[0]);
				}	
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Parses IQRF Info configuration from REST API response
	 * @param {IIqrfInfo} response Configuration from REST API response
	 */
	private parseConfiguration(response: IIqrfInfo): void {
		this.instance = this.componentInstance = response.instance;
		this.enumAtStartUp = response.enumAtStartUp;
		if (!this.daemon230) {
			return;
		}
		if (response.enumPeriod !== undefined) {
			this.enumPeriod = response.enumPeriod;
			this.enumPeriodic = (this.enumPeriod > 0);
		}
		if (response.enumUniformDpaVer !== undefined) {
			this.enumUniformDpaVer = response.enumUniformDpaVer;
		}
	}

	/**
	 * Creates IQRF Info configuration object for REST API request
	 * @returns {IIqrfInfo} IQRF Info configuration
	 */
	private buildConfiguration(): IIqrfInfo {
		let configuration: IIqrfInfo = {
			component: this.componentName,
			instance: this.componentInstance,
			enumAtStartUp: this.enumAtStartUp
		};
		if (this.daemon230) {
			Object.assign(configuration, {enumPeriod: this.enumPeriodic ? this.enumPeriod : 0, enumUniformDpaVer: this.enumUniformDpaVer});
		}
		return configuration;
	}

	/**
	 * Saves new or updates existing configuration of IQRF Info component instance
	 */
	private saveConfig(): void {
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
		this.getConfig().then(() => this.$toast.success(this.$t('config.success').toString()));
	}
}
</script>
