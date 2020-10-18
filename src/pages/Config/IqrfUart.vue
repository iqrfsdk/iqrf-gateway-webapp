<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.iqrfUart.title') }}</h3></CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveConfig'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfUart.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.iqrfUart.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfUart.form.messages.IqrfInterface"}'
						>
							<CInput
								v-model='configuration.IqrfInterface'
								:label='$t("config.iqrfUart.form.IqrfInterface")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ valid, touched, errors }'
							rules='required'
							:custom-messages='{
								required: "config.iqrfUart.form.messages.baudRate",
							}'
						>
							<CSelect
								:value.sync='configuration.baudRate'
								:label='$t("config.iqrfUart.form.baudRate")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:placeholder='$t("config.iqrfUart.form.messages.baudRate")'
								:options='baudRates'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|integer'
							:custom-messages='{
								integer: "config.iqrfUart.form.messages.powerEnableGpioPin",
								required: "config.iqrfUart.form.messages.powerEnableGpioPin",
							}'
						>
							<CInput
								v-model='configuration.powerEnableGpioPin'
								:label='$t("config.iqrfUart.form.powerEnableGpioPin")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|integer'
							:custom-messages='{
								integer: "config.iqrfUart.form.messages.busEnableGpioPin",
								required: "config.iqrfUart.form.messages.busEnableGpioPin",
							}'
						>
							<CInput
								v-model='configuration.busEnableGpioPin'
								:label='$t("config.iqrfUart.form.busEnableGpioPin")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
		<CCard>
			<CCardHeader>{{ $t('config.iqrfUart.mappings' ) }}</CCardHeader>
			<CCardBody>
				<CRow>
					<CCol md='6'>
						<InterfaceMappings interface-type='uart' @update-mapping='updateMapping' />
					</CCol>
					<CCol md='6'>
						<InterfacePorts interface-type='uart' @update-port='updatePort' />
					</CCol>
				</CRow>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {
	CButton,
	CCard,
	CCardBody,
	CCardHeader,
	CCol,
	CForm,
	CInput,
	CRow,
	CSelect,
} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required} from 'vee-validate/dist/rules';
import InterfaceMappings from '../../components/Config/InterfaceMappings.vue';
import InterfacePorts from '../../components/Config/InterfacePorts.vue';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';

interface IqrfUartConfig {
	instance: string|null
	IqrfInterface: string|null
	baudRate: null
	powerEnableGpioPin: number|null
	busEnableGpioPin: number|null
}

interface BaudRateOptions {
	value: number
	label: string
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CCol,
		CForm,
		CInput,
		CRow,
		CSelect,
		InterfaceMappings,
		InterfacePorts,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo: {
		title: 'config.iqrfUart.title',
	},
})

export default class IqrfUart extends Vue {
	private componentName = 'iqrf::IqrfUart'
	private configuration: IqrfUartConfig = {
		instance: null,
		IqrfInterface: null,
		baudRate: null,
		powerEnableGpioPin: null,
		busEnableGpioPin: null,
	}
	private instance: string|null = null

	get baudRates(): Array<BaudRateOptions> {
		const baudRates: Array<number> = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200, 230400];
		return baudRates.map((baudRate: number) => ({value: baudRate, label: baudRate + ' Bd'}));
	}
	
	mounted(): void {
		extend('integer', integer);
		extend('required', required);
		this.getConfig();
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				if (response.data.instances.length > 0) {
					this.configuration = response.data.instances[0];
					this.instance = this.configuration.instance;
				}
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
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
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}

	private updateMapping(mapping: IqrfUartConfig): void {
		Object.assign(this.configuration, mapping);
	}

	private updatePort(port: string): void {
		this.configuration.IqrfInterface = port;
	}
	
}
</script>
