<template>
	<CCard class='border-0'>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-if='powerUser'
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.daemon.misc.iqrfRepository.errors.instance"}'
					>
						<CInput
							v-model='componentInstance'
							:label='$t("forms.fields.instanceName")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.daemon.misc.iqrfRepository.errors.urlRepo"}'
					>
						<CInput
							v-model='urlRepo'
							:label='$t("config.daemon.misc.iqrfRepository.form.urlRepo")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<div class='form-group'>
						<label for='checkEnableSwitch'>
							{{ $t("config.daemon.misc.iqrfRepository.form.enableCheck") }}
						</label><br>
						<CSwitch
							id='checkEnableSwitch'
							color='primary'
							size='lg'
							shape='pill'
							label-on='ON'
							label-off='OFF'
							:checked.sync='checkEnabled'
						/>
					</div>
					<ValidationProvider
						v-if='checkEnabled'
						v-slot='{ errors, touched, valid }'
						rules='integer|required|min:0'
						:custom-messages='{
							integer: "forms.errors.integer",
							required: "config.daemon.misc.iqrfRepository.errors.checkPeriod",
							min: "config.daemon.misc.iqrfRepository.errors.checkPeriod"
						}'
					>
						<CInput
							v-model.number='checkPeriodInMinutes'
							type='number'
							min='0'
							:label='$t("config.daemon.misc.iqrfRepository.form.checkPeriod")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						v-if='daemon230'
						:checked.sync='downloadIfRepoCacheEmpty'
						:label='$t("config.daemon.misc.iqrfRepository.form.downloadIfEmpty")'
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
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService	from '../../services/DaemonConfigurationService';
import {IIqrfRepository} from '../../interfaces/iqrfRepository';
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
 * IQRF Repository component configuration
 */
export default class IqrfRepository extends Vue {

	/**
	 * @var {boolean} checkEnabled Enable periodical update check
	 */
	private checkEnabled = false

	/**
	 * @var {number} checkPeriodInMinutes Check period in minutes
	 */
	private checkPeriodInMinutes = 0

	/**
	 * @var {string} componentInstance IQRF Repository component instance name
	 */
	private componentInstance = ''

	/**
	 * @constant {string} componentName IQRF Repository component name, used for REST API communication
	 */
	private componentName = 'iqrf::JsCache'

	/**
	 * @var {boolean} daemon230 Indicates whether Daemon version is 2.3.0 or higher
	 */
	private daemon230 = false

	/**
	 * @var {boolean} downloadIfRepoCacheEmpty Download if repository cache is empty?
	 */
	private downloadIfRepoCacheEmpty = true

	/**
	 * @var {string} instance IQRF Repository component instance name, used for REST API communication
	 */
	private instance = ''

	/**
	 * @var {boolean} powerUser Indicates whether user role is power user
	 */
	private powerUser = false

	/**
	 * @var {string} urlRepo Repository URL
	 */
	private urlRepo = ''

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
	 * Retrieves configuration of IQRF Repository component
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
	 * Parses IQRF Repository configuration from REST API response
	 * @param {IIqrfRepository} response Configuration from REST API response
	 */
	private parseConfiguration(response: IIqrfRepository): void {
		this.instance = this.componentInstance = response.instance;
		this.urlRepo = response.urlRepo;
		this.checkPeriodInMinutes = response.checkPeriodInMinutes;
		if (this.checkPeriodInMinutes > 0) {
			this.checkEnabled = true;
		}
		if (!this.daemon230) {
			return;
		}
		if (response.downloadIfRepoCacheEmpty !== undefined) {
			this.downloadIfRepoCacheEmpty = response.downloadIfRepoCacheEmpty;
		}
	}

	/**
	 * Creates IQRF Repository component instance configuration
	 */
	private buildConfiguration(): IIqrfRepository {
		let configuration: IIqrfRepository = {
			component: this.componentName,
			instance: this.componentInstance,
			urlRepo: this.urlRepo,
			checkPeriodInMinutes: this.checkEnabled ? this.checkPeriodInMinutes : 0,
		};
		if (this.daemon230) {
			Object.assign(configuration, {downloadIfRepoCacheEmpty: this.downloadIfRepoCacheEmpty});
		}
		return configuration;
	}

	/**
	 * Saves new or updates existing configuration of IQRF Repository component instance
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
