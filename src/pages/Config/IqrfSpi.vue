<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.iqrfSpi.title') }}</h3></CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveConfig'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfSpi.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.iqrfSpi.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.iqrfSpi.form.messages.IqrfInterface"}'
						>
							<CInput
								v-model='configuration.IqrfInterface'
								:label='$t("config.iqrfSpi.form.IqrfInterface")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|integer'
							:custom-messages='{
								integer: "config.iqrfSpi.form.messages.powerEnableGpioPin",
								required: "config.iqrfSpi.form.messages.powerEnableGpioPin",
							}'
						>
							<CInput
								v-model='configuration.powerEnableGpioPin'
								:label='$t("config.iqrfSpi.form.powerEnableGpioPin")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|integer'
							:custom-messages='{
								integer: "config.iqrfSpi.form.messages.busEnableGpioPin",
								required: "config.iqrfSpi.form.messages.busEnableGpioPin",
							}'
						>
							<CInput
								v-model='configuration.busEnableGpioPin'
								:label='$t("config.iqrfSpi.form.busEnableGpioPin")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|integer'
							:custom-messages='{
								integer: "config.iqrfSpi.form.messages.pgmSwitchGpioPin",
								required: "config.iqrfSpi.form.messages.pgmSwitchGpioPin",
							}'
						>
							<CInput
								v-model='configuration.pgmSwitchGpioPin'
								:label='$t("config.iqrfSpi.form.pgmSwitchGpioPin")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.spiReset'
							:label='$t("config.iqrfSpi.form.spiReset")'
						/>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
		<CCard>
			<CCardHeader>{{ $t('config.iqrfSpi.mappings' ) }}</CCardHeader>
			<CCardBody>
				<CRow>
					<CCol md='6'>
						<InterfaceMappings interface-type='spi' @update-mapping='updateMapping' />
					</CCol>
					<CCol md='6'>
						<InterfacePorts interface-type='spi' @update-port='updatePort' />
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
	CInputCheckbox,
	CRow
} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required} from 'vee-validate/dist/rules';
import InterfaceMappings from '../../components/Config/InterfaceMappings.vue';
import InterfacePorts from '../../components/Config/InterfacePorts.vue';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';

interface IqrfSpiConfig {
	instance: string|null
	IqrfInterface: string|null
	powerEnableGpioPin: number|null
	busEnableGpioPin: number|null
	pgmSwitchGpioPin: number|null
	spiReset: boolean
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
		CInputCheckbox,
		CRow,
		InterfaceMappings,
		InterfacePorts,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo: {
		title: 'config.iqrfSpi.title',
	},
})

export default class IqrfSpi extends Vue {
	private componentName = 'iqrf::IqrfSpi'
	private configuration: IqrfSpiConfig = {
		instance: null,
		IqrfInterface: null,
		powerEnableGpioPin: null,
		busEnableGpioPin: null,
		pgmSwitchGpioPin: null,
		spiReset: false,
	}
	private instance: string|null = null

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

	private updateMapping(mapping: IqrfSpiConfig): void {
		Object.assign(this.configuration, mapping);
	}
	
	private updatePort(port: string): void {
		this.configuration.IqrfInterface = port;
	}
}
</script>
