<template>
	<div>
		<h1>{{ $t('controllerConfig.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-if='config !== null' v-slot='{ invalid }'>
				<CForm @submit.prevent='processSubmit'>
					<CRow>
						<CCol md='6'>
							<h3>{{ $t("controllerConfig.form.wsServers.title") }}</h3>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required|ws_addr'
								:custom-messages='{
									required: "controllerConfig.form.messages.missing.ws_api",
									ws_addr: "controllerConfig.form.messages.invalid.ws_format"
								}'
							>
								<CInput
									v-model='config.wsServers.api'
									:label='$t("controllerConfig.form.wsServers.api")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required|ws_addr'
								:custom-messages='{
									required: "controllerConfig.form.messages.missing.ws_monitor",
									ws_addr: "controllerConfig.form.messages.invalid.ws_format"
								}'
							>
								<CInput
									v-model='config.wsServers.monitor'
									:label='$t("controllerConfig.form.wsServers.monitor")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
						</CCol>
						<CCol md='6'>
							<h3>{{ $t("controllerConfig.form.logger.title") }}</h3>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required'
								:custom-messages='{required: "controllerConfig.form.messages.missing.l_file"}'
							>
								<CInput
									v-model='config.logger.filePath'
									:label='$t("controllerConfig.form.logger.filePath")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{ valid, touched, errors }'
								rules='required'
								:custom-messages='{
									required: "controllerConfig.form.messages.missing.l_severity",
								}'
							>
								<CSelect
									:value.sync='config.logger.severity'
									:options='[
										{value: "trace", label: "Trace"},
										{value: "debug", label: "Debug"},
										{value: "info", label: "Info"},
										{value: "warning", label: "Warning"},
										{value: "error", label: "Error"}
									]'
									:label='$t("controllerConfig.form.logger.severity")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
									:placeholder='$t("controllerConfig.form.messages.missing.l_severity")'
								/>
							</ValidationProvider>
						</CCol>
					</CRow><hr>
					<CRow>
						<CCol md='6'>
							<h3>{{ $t("controllerConfig.form.resetButton.title") }}</h3>
							<CSelect
								:value.sync='config.resetButton.api'
								:options='[
									{value: "autoNetwork", label: "AutoNetwork"},
									{value: "discovery", label: "Discovery"},
								]'
								:label='$t("controllerConfig.form.resetButton.api")'
							/><hr>
							<div v-if='config.resetButton.api === "autoNetwork"'>
								<h3>
									{{ $t("controllerConfig.form.daemonApi.autoNetwork.title") }}
								</h3>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='required|integer|between:0,3'
									:custom-messages='{
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.autoNetwork.actionRetries",
										between: "iqrfnet.networkManager.messages.autoNetwork.actionRetries"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.autoNetwork.actionRetries'
										type='number'
										min='0'
										max='3'
										:label='$t("iqrfnet.networkManager.autoNetwork.form.actionRetries")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<CInputCheckbox
									:checked.sync='config.daemonApi.autoNetwork.discoveryBeforeStart'
									:label='$t("iqrfnet.networkManager.autoNetwork.form.discoveryBeforeStart")'
								/>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='integer|required|between:0,7'
									:custom-messages='{
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.discovery.txPower",
										between: "iqrfnet.networkManager.messages.discovery.txPower"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.autoNetwork.discoveryTxPower'
										type='number'
										min='0'
										max='7'
										:label='$t("iqrfnet.networkManager.autoNetwork.form.discoveryTxPower")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<CInputCheckbox
									:checked.sync='config.daemonApi.autoNetwork.skipDiscoveryEachWave'
									:label='$t("iqrfnet.networkManager.autoNetwork.form.skipDiscoveryEachWave")'
								/><hr>
								<h4>
									{{ $t("iqrfnet.networkManager.autoNetwork.form.stopConditions") }}
								</h4>
								<CInputCheckbox
									:checked.sync='config.daemonApi.autoNetwork.stopConditions.abortOnTooManyNodesFound'
									:label='$t("iqrfnet.networkManager.autoNetwork.form.abortOnTooManyNodesFound")'
								/>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='integer|required|between:1,127'
									:custom-messages='{
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.autoNetwork.emptyWaves",
										between: "iqrfnet.networkManager.messages.autoNetwork.emptyWaves"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.autoNetwork.stopConditions.emptyWaves'
										type='number'
										min='1'
										max='127'
										:label='$t("iqrfnet.networkManager.autoNetwork.form.emptyWaves")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='integer|required|between:1,127'
									:custom-messages='{
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.autoNetwork.waves",
										between: "iqrfnet.networkManager.messages.autoNetwork.waves"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.autoNetwork.stopConditions.waves'
										type='number'
										min='1'
										max='127'
										:label='$t("iqrfnet.networkManager.autoNetwork.form.waves")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider><hr>
								<CInputCheckbox
									:checked.sync='config.daemonApi.autoNetwork.returnVerbose'
									:label='$t("controllerConfig.form.daemonApi.autoNetwork.verbose")'
								/>
							</div>
							<div v-else-if='config.resetButton.api === "discovery"'>
								<h3>
									{{ $t("controllerConfig.form.daemonApi.discovery.title") }}
								</h3>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='integer|required|between:0,239'
									:custom-messages='{
										between: "iqrfnet.networkManager.messages.discovery.maxAddr",
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.discovery.maxAddr"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.discovery.maxAddr'
										type='number'
										min='0'
										max='239'
										:label='$t("iqrfnet.networkManager.discovery.form.maxAddr")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{ errors, touched, valid }'
									rules='integer|required|between:0,7'
									:custom-messages='{
										integer: "iqrfnet.networkManager.messages.invalid.integer",
										required: "iqrfnet.networkManager.messages.discovery.txPower",
										between: "iqrfnet.networkManager.messages.discovery.txPower"
									}'
								>
									<CInput
										v-model.number='config.daemonApi.discovery.txPower'
										type='number'
										min='0'
										max='7'
										:label='$t("iqrfnet.networkManager.discovery.form.txPower")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
									/>
								</ValidationProvider>
								<CInputCheckbox
									:checked.sync='config.daemonApi.discovery.returnVerbose'
									:label='$t("controllerConfig.form.daemonApi.discovery.verbose")'
								/>
							</div>
						</CCol>
						<CCol md='6'>
							<h3>{{ $t("controllerConfig.form.factoryReset.title") }}</h3>
							<CInputCheckbox
								:checked.sync='config.factoryReset.coordinator'
								:label='$t("controllerConfig.form.factoryReset.coordinator")'
							/>
							<CInputCheckbox
								:checked.sync='config.factoryReset.daemon'
								:label='$t("controllerConfig.form.factoryReset.daemon")'
							/>
							<CInputCheckbox
								:checked.sync='config.factoryReset.network'
								:label='$t("controllerConfig.form.factoryReset.network")'
							/>
							<CInputCheckbox
								:checked.sync='config.factoryReset.webapp'
								:label='$t("controllerConfig.form.factoryReset.webapp")'
							/>
						</CCol>
					</CRow>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard , CForm, CInput, CInputCheckbox, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import FeatureConfigService from '../../services/FeatureConfigService';
import {NavigationGuardNext, Route} from 'vue-router/types/router';
import {ControllerBase} from '../../interfaces/controller';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		CInputCheckbox,
		CSelect,
		ValidationObserver,
		ValidationProvider
	},
	beforeRouteEnter(to: Route, from: Route, next: NavigationGuardNext): void {
		next((vm: Vue) => {
			if (!vm.$store.getters['features/isEnabled']('iqrfGatewayController')) {
				vm.$toast.error(
					vm.$t('controllerConfig.form.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	metaInfo: {
		title: 'controllerConfig.description',
	},
})

export default class ControllerConfig extends Vue {
	private name = 'controller'
	private config: ControllerBase|null = null

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		extend('ws_addr', (addr: string) => {
			const regex = RegExp('^ws:\\/\\/.+:([1-9]|[1-9][0-9]|[1-9][0-9]{2}|[1-9][0-9]{3}|[1-4][0-9][0-1][0-5][0-1])$');
			return regex.test(addr);
		});
		this.getConfig();
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		FeatureConfigService.getConfig(this.name)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.config = response.data;
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
			});
	}

	private processSubmit(): void {
		this.$store.commit('spinner/SHOW');
		FeatureConfigService.saveConfig(this.name, this.config)
			.then(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.success(this.$t('forms.messages.saveSuccess').toString());
			})
			.catch((error: AxiosError) => {
				FormErrorHandler.configError(error);
			});
	}
}
</script>
