<template>
	<CCard>
		<CCardHeader>
			{{ $t('config.daemon.interfaces.iqrfSpi.title') }}
		</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{invalid}'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-if='powerUser'
						v-slot='{errors, touched, valid}'
						rules='required'
						:custom-messages='{required: "config.daemon.interfaces.iqrfSpi.errors.instance"}'
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
						rules='required'
						:custom-messages='{required: "config.daemon.interfaces.iqrfSpi.errors.iqrfInterface"}'
					>
						<CInput
							v-model='IqrfInterface'
							:label='$t("config.daemon.interfaces.iqrfSpi.form.iqrfInterface")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='spiReset'
						:label='$t("config.daemon.interfaces.iqrfSpi.form.spiReset")'
					/>
					<CRow>
						<CCol :md='(i2cEnableGpioPin !== null || spiEnableGpioPin !== null || uartEnableGpioPin !== null) ? 6 : 12'>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required|integer'
								:custom-messages='{
									integer: "config.daemon.interfaces.interfaceMapping.errors.powerPin",
									required: "config.daemon.interfaces.interfaceMapping.errors.powerPin",
								}'
							>
								<CInput
									v-model='powerEnableGpioPin'
									:label='$t("config.daemon.interfaces.interfaceMapping.form.powerPin")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required|integer'
								:custom-messages='{
									integer: "config.daemon.interfaces.interfaceMapping.errors.busPin",
									required: "config.daemon.interfaces.interfaceMapping.errors.busPin",
								}'
							>
								<CInput
									v-model='busEnableGpioPin'
									:label='$t("config.daemon.interfaces.interfaceMapping.form.busPin")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required|integer'
								:custom-messages='{
									integer: "config.daemon.interfaces.interfaceMapping.errors.pgmPin",
									required: "config.daemon.interfaces.interfaceMapping.errors.pgmPin",
								}'
							>
								<CInput
									v-model='pgmSwitchGpioPin'
									:label='$t("config.daemon.interfaces.interfaceMapping.form.pgmPin")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
						</CCol>
						<CCol 
							v-if='(i2cEnableGpioPin !== null || spiEnableGpioPin !== null || uartEnableGpioPin !== null)'
							md='6'
						>
							<CInput
								v-if='i2cEnableGpioPin !== null'
								v-model.number='i2cEnableGpioPin'
								type='number'
								:label='$t("config.daemon.interfaces.interfaceMapping.form.i2cPin")'
								:disabled='true'
							/>
							<CInput
								v-if='spiEnableGpioPin !== null'
								v-model.number='spiEnableGpioPin'
								type='number'
								:label='$t("config.daemon.interfaces.interfaceMapping.form.spiPin")'
								:disabled='true'
							/>
							<CInput
								v-if='uartEnableGpioPin !== null'
								v-model.number='uartEnableGpioPin'
								type='number'
								:label='$t("config.daemon.interfaces.interfaceMapping.form.uartPin")'
								:disabled='true'
							/>
						</CCol>
					</CRow>
					<div v-if='i2cEnableGpioPin !== null || spiEnableGpioPin !== null || uartEnableGpioPin !== null'>
						<i>{{ $t('config.daemon.interfaces.interfaceMapping.form.gwOnly') }}</i>
					</div><br v-if='i2cEnableGpioPin !== null || spiEnableGpioPin !== null || uartEnableGpioPin !== null'>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
		<CCardFooter>
			<h4>{{ $t('config.daemon.interfaces.iqrfSpi.mappings' ) }}</h4><hr>
			<CRow>
				<CCol md='6'>
					<InterfaceMappings interface-type='spi' @update-mapping='updateMapping' />
				</CCol>
				<CCol md='6'>
					<InterfacePorts interface-type='spi' @update-port='updatePort' />
				</CCol>
			</CRow>
		</CCardFooter>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {
	CButton,
	CCard,
	CCardBody,
	CCardFooter,
	CCardHeader,
	CCol,
	CForm,
	CInput,
	CInputCheckbox,
	CRow
} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required} from 'vee-validate/dist/rules';
import InterfaceMappings from '../../components/Config/InterfaceMappings.vue';
import InterfacePorts from '../../components/Config/InterfacePorts.vue';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import {IIqrfSpi} from '../../interfaces/iqrfInterfaces';
import {IMapping} from '../../interfaces/mappings';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardFooter,
		CCardHeader,
		CCol,
		CForm,
		CInput,
		CInputCheckbox,
		CRow,
		InterfaceMappings,
		InterfacePorts,
		ValidationObserver,
		ValidationProvider,
	}
})

/**
 * IQRF SPI communication interface configuration component
 */
export default class IqrfSpi extends Vue {
	/**
	 * @var {number} busEnableGpioPin SPI bus enable ping
	 */
	private busEnableGpioPin = 7

	/**
	 * @var {string} component SPI component name
	 */
	private component = ''

	/**
	 * @var {string} componentInstance SPI component instance name
	 */
	private componentInstance = ''

	/**
	 * @constant {string} componentName SPI component name, used for REST API communication
	 */
	private componentName = 'iqrf::IqrfSpi'

	/**
	 * @var {number|null} i2cEnableGpioPin I2C interface enable pin
	 */
	private i2cEnableGpioPin: number|null = null

	/**
	 * @var {string} instance SPI component instance name, used for REST API communication
	 */
	private instance = ''

	/**
	 * @var {string} IqrfInterface SPI interface device name
	 */
	private IqrfInterface = '/dev/spidev0.0'

	/**
	 * @var {number} pgmSwitchGpioPin SPI programming mode switch pin
	 */
	private pgmSwitchGpioPin = 22

	/**
	 * @var {number} powerEnableGpioPin SPI power enable pin
	 */
	private powerEnableGpioPin = 23

	/**
	 * @var {boolean} powerUser Indicates whether user role is power user
	 */
	private powerUser = false

	/**
	 * @var {number|null} spiEnableGpioPin SPI interface enable pin
	 */
	private spiEnableGpioPin: number|null = null

	/**
	 * @var {boolean} spiReset Should SPI component instance reset?
	 */
	private spiReset = true

	/**
	 * @var {number|null} uartEnableGpioPin UART interface enable pin
	 */
	private uartEnableGpioPin: number|null = null

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('integer', integer);
		extend('required', required);
	}

	mounted(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
		this.getConfig();
	}

	/**
	 * Retrieves configuration of IQRF SPI interface component
	 */
	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				if (response.data.instances.length > 0) {
					this.parseConfiguration(response.data.instances[0]);
				}
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Parses IQRF SPI interface configuration from REST API response
	 * @param {IIqrfSpi} response Configuration object from REST API response
	 */
	private parseConfiguration(response: IIqrfSpi): void {
		this.component = response.component;
		this.instance = this.componentInstance = response.instance;
		this.IqrfInterface = response.IqrfInterface;
		this.busEnableGpioPin = response.busEnableGpioPin;
		this.pgmSwitchGpioPin = response.pgmSwitchGpioPin;
		this.powerEnableGpioPin = response.powerEnableGpioPin;
		this.spiReset = response.spiReset;
		if (response.i2cEnableGpioPin !== undefined) {
			this.i2cEnableGpioPin = response.i2cEnableGpioPin;
		}
		if (response.spiEnableGpioPin !== undefined) {
			this.spiEnableGpioPin = response.spiEnableGpioPin;
		}
		if (response.uartEnableGpioPin !== undefined) {
			this.uartEnableGpioPin = response.uartEnableGpioPin;
		}
	}

	private buildConfiguration(): IIqrfSpi {
		let configuration: IIqrfSpi = {
			component: this.component,
			instance: this.componentInstance,
			IqrfInterface: this.IqrfInterface,
			busEnableGpioPin: this.busEnableGpioPin,
			pgmSwitchGpioPin: this.pgmSwitchGpioPin,
			powerEnableGpioPin: this.powerEnableGpioPin,
			spiReset: this.spiReset
		};
		if (this.i2cEnableGpioPin !== undefined) {
			Object.assign(configuration, {i2cEnableGpioPin: this.i2cEnableGpioPin});
		}
		if (this.spiEnableGpioPin !== undefined) {
			Object.assign(configuration, {spiEnableGpioPin: this.spiEnableGpioPin});
		}
		if (this.uartEnableGpioPin !== undefined) {
			Object.assign(configuration, {uartEnableGpioPin: this.uartEnableGpioPin});
		}
		return configuration;
	}

	/**
	 * Saves new or updates existing configuration of IQRF SPI interface component instance
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
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}

	/**
	 * Updates pin configuration from mapping
	 * @param {IMapping} mapping Board mapping
	 */
	private updateMapping(mapping: IMapping): void {
		this.IqrfInterface = mapping.IqrfInterface;
		this.busEnableGpioPin = mapping.busEnableGpioPin;
		this.pgmSwitchGpioPin = mapping.pgmSwitchGpioPin;
		this.powerEnableGpioPin = mapping.powerEnableGpioPin;
		if (mapping.i2cEnableGpioPin !== undefined) {
			this.i2cEnableGpioPin = mapping.i2cEnableGpioPin;
		} else {
			this.i2cEnableGpioPin = null;
		}
		if (mapping.spiEnableGpioPin !== undefined) {
			this.spiEnableGpioPin = mapping.spiEnableGpioPin;
		} else {
			this.spiEnableGpioPin = null;
		}
		if (mapping.uartEnableGpioPin !== undefined) {
			this.uartEnableGpioPin = mapping.uartEnableGpioPin;
		} else {
			this.uartEnableGpioPin = null;
		}
	}
	
	/**
	 * Updates port in configuration from mapping
	 * @param {string} port Port
	 */
	private updatePort(port: string): void {
		this.IqrfInterface = port;
	}
}
</script>
