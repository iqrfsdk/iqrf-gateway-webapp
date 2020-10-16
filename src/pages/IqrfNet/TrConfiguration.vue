<template>
	<div>
		<h1>{{ $t('iqrfnet.trConfiguration.title') }}</h1>
		<AddressChanger :current-address='address' />
		<CCard v-if='config !== null'>
			<CCardHeader>{{ $t('iqrfnet.trConfiguration.title') }}</CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='handleSubmit'>
						<CRow>
							<CCol md='6'>
								<h2>{{ $t('iqrfnet.trConfiguration.form.rf') }}</h2>
								<CSelect
									v-model='config.rfBand'
									:label='$t("iqrfnet.trConfiguration.form.rfBand")'
									:options='[
										{value: "443", label: $t("iqrfnet.trConfiguration.form.rfBands.443")},
										{value: "868", label: $t("iqrfnet.trConfiguration.form.rfBands.868")},
										{value: "916", label: $t("iqrfnet.trConfiguration.form.rfBands.916")},
									]'
									disabled='true'
								/>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									:rules='rfChannelRules.rule'
									:custom-messages='rfChannelValidatorMessages'
								>
									<CInput
										v-model.number='config.rfChannelA'
										:label='$t("iqrfnet.trConfiguration.form.rfChannelA")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										:max='rfChannelRules.max'
										:min='rfChannelRules.min'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									:rules='rfChannelRules.rule'
									:custom-messages='rfChannelValidatorMessages'
								>
									<CInput
										v-model.number='config.rfChannelB'
										:label='$t("iqrfnet.trConfiguration.form.rfChannelB")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										:max='rfChannelRules.max'
										:min='rfChannelRules.min'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-if='config.rfSubChannelA !== undefined'
									v-slot='{ valid, touched, errors }'
									:rules='rfChannelRules.rule'
									:custom-messages='rfChannelValidatorMessages'
								>
									<CInput
										v-model.number='config.rfSubChannelA'
										:label='$t("iqrfnet.trConfiguration.form.rfSubChannelA")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										:max='rfChannelRules.max'
										:min='rfChannelRules.min'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-if='config.rfSubChannelB !== undefined'
									v-slot='{ valid, touched, errors }'
									:rules='rfChannelRules.rule'
									:custom-messages='rfChannelValidatorMessages'
								>
									<CInput
										v-model.number='config.rfSubChannelB'
										:label='$t("iqrfnet.trConfiguration.form.rfSubChannelB")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										:max='rfChannelRules.max'
										:min='rfChannelRules.min'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-if='config.rfAltDsmChannel !== undefined'
									v-slot='{ valid, touched, errors }'
									:rules='rfChannelRules.rule'
									:custom-messages='rfChannelValidatorMessages'
								>
									<CInput
										v-model.number='config.rfAltDsmChannel'
										:label='$t("iqrfnet.trConfiguration.form.rfAltDsmChannel")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										:max='rfChannelRules.max'
										:min='rfChannelRules.min'
									/>
								</ValidationProvider>
								<CAlert v-if='config.stdAndLpNetwork === false' color='warning'>
									{{ $t('iqrfnet.trConfiguration.form.messages.breakInteroperability') }}
								</CAlert>
								<CInputCheckbox
									v-if='config.stdAndLpNetwork !== undefined'
									:checked.sync='config.stdAndLpNetwork'
									:label='$t("iqrfnet.trConfiguration.form.stdAndLpNetwork")'
								/>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									rules='integer|between:0,7|required'
									:custom-messages='{
										between: "iqrfnet.trConfiguration.form.messages.txPower",
										integer: "iqrfnet.trConfiguration.form.messages.txPower",
										required: "iqrfnet.trConfiguration.form.messages.txPower",
									}'
								>
									<CInput
										v-model.number='config.txPower'
										:label='$t("iqrfnet.trConfiguration.form.txPower")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										max='7'
										min='0'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									rules='integer|between:0,64|required'
									:custom-messages='{
										between: "iqrfnet.trConfiguration.form.messages.rxFilter",
										integer: "iqrfnet.trConfiguration.form.messages.rxFilter",
										required: "iqrfnet.trConfiguration.form.messages.rxFilter",
									}'
								>
									<CInput
										v-model.number='config.rxFilter'
										:label='$t("iqrfnet.trConfiguration.form.rxFilter")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
										max='64'
										min='0'
									/>
								</ValidationProvider>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									rules='integer|between:1,255|required'
									:custom-messages='{
										between: "iqrfnet.trConfiguration.form.messages.lpRxTimeout",
										integer: "iqrfnet.trConfiguration.form.messages.lpRxTimeout",
										required: "iqrfnet.trConfiguration.form.messages.lpRxTimeout",
									}'
								>
									<CInput
										v-model.number='config.lpRxTimeout'
										:label='$t("iqrfnet.trConfiguration.form.lpRxTimeout")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										type='number'
									/>
								</ValidationProvider>
								<h2>{{ $t('iqrfnet.trConfiguration.form.rfPgm') }}</h2>
								<CInputCheckbox
									:checked.sync='config.rfPgmEnableAfterReset'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmEnableAfterReset")'
								/>
								<CInputCheckbox
									:checked.sync='config.rfPgmTerminateAfter1Min'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmTerminateAfter1Min")'
								/>
								<CInputCheckbox
									:checked.sync='config.rfPgmTerminateMcuPin'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmTerminateMcuPin")'
								/>
								<CInputCheckbox
									:checked.sync='config.rfPgmDualChannel'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmDualChannel")'
								/>
								<CInputCheckbox
									:checked.sync='config.rfPgmLpMode'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmLpMode")'
								/>
								<CInputCheckbox
									:checked.sync='config.rfPgmIncorrectUpload'
									:label='$t("iqrfnet.trConfiguration.form.rfPgmIncorrectUpload")'
									disabled='true'
								/>
							</CCol>
							<CCol md='6'>
								<h2>{{ $t('iqrfnet.trConfiguration.form.dpa.embeddedPeripherals') }}</h2>
								<CInputCheckbox
									v-for='peripheral of peripherals'
									:key='peripheral.name'
									:checked.sync='peripheral.enabled'
									:disabled='unchangeablePeripherals.includes(peripheral.name)'
									:label='$t("iqrfnet.trConfiguration.form.embPers." + peripheral.name)'
								/>
								<h2>{{ $t('iqrfnet.trConfiguration.form.dpa.other') }}</h2>
								<CInputCheckbox
									:checked.sync='config.customDpaHandler'
									:label='$t("iqrfnet.trConfiguration.form.customDpaHandler")'
									:disabled='!dpaHandlerDetected'
								/>
								<CInputCheckbox
									v-if='config.dpaPeerToPeer !== undefined'
									:checked.sync='config.dpaPeerToPeer'
									:label='$t("iqrfnet.trConfiguration.form.dpaPeerToPeer")'
								/>
								<CInputCheckbox
									:checked.sync='config.peerToPeer'
									:label='$t("iqrfnet.trConfiguration.form.peerToPeer")'
								/>
								<CInputCheckbox
									v-if='config.localFrcReception !== undefined && address !== 0'
									:checked.sync='config.localFrcReception'
									:label='$t("iqrfnet.trConfiguration.form.localFrcReception")'
								/>
								<CInputCheckbox
									:checked.sync='config.ioSetup'
									:label='$t("iqrfnet.trConfiguration.form.ioSetup")'
								/>
								<CInputCheckbox
									:checked.sync='config.dpaAutoexec'
									:label='$t("iqrfnet.trConfiguration.form.dpaAutoexec")'
								/>
								<CInputCheckbox
									:checked.sync='config.routingOff'
									:label='$t("iqrfnet.trConfiguration.form.routingOff")'
								/>
								<CInputCheckbox
									v-if='config.neverSleep !== undefined'
									:checked.sync='config.neverSleep'
									:label='$t("iqrfnet.trConfiguration.form.neverSleep")'
								/>
								<ValidationProvider
									v-slot='{ valid, touched, errors }'
									rules='required'
									:custom-messages='{
										required: "iqrfnet.trConfiguration.form.messages.uartBaudrate",
									}'
								>
									<CSelect
										:value.sync='config.uartBaudrate'
										:label='$t("iqrfnet.trConfiguration.form.uartBaudrate")'
										:is-valid='touched ? valid : null'
										:invalid-feedback='$t(errors[0])'
										:placeholder='$t("iqrfnet.trConfiguration.form.messages.uartBaudrate")'
										:options='uartBaudRates'
									/>
								</ValidationProvider>
								<CInputCheckbox
									v-if='config.nodeDpaInterface !== undefined'
									:checked.sync='config.nodeDpaInterface'
									:label='$t("iqrfnet.trConfiguration.form.nodeDpaInterface")'
								/>
							</CCol>
						</CRow>
						<CButton color='primary' type='submit' :disabled='invalid'>
							{{ $t('forms.write') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
		<SecurityForm v-if='config !== null' :address='address' />
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue, Watch} from 'vue-property-decorator';
import {CCard, CCardBody, CCardHeader, CForm, CInput} from '@coreui/vue/src';
import {
	between,
	integer,
	min_value,
	max_value,
	required,
} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import AddressChanger from '../../components/IqrfNet/AddressChanger.vue';
import SecurityForm from '../../components/IqrfNet/SecurityForm.vue';
import IqrfNetService from '../../services/IqrfNetService';
import { IOption } from '../../interfaces/coreui';
import { WebSocketClientState } from '../../store/modules/webSocketClient.module';
import { MutationPayload } from 'vuex';
import { Dictionary } from 'vue-router/types/router';
import { IEmbedPers, IEmbedPersEnabled, ITrConfiguration } from '../../interfaces/dpa';

@Component({
	components: {
		AddressChanger,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		SecurityForm,
		ValidationObserver,
		ValidationProvider,
	}
})

export default class TrConfiguration extends Vue {
	private config: ITrConfiguration|null = null
	private dpaHandlerDetected = false
	private dpaVersion: string|null = null
	private msgId: string|null = null
	private peripherals: Array<IEmbedPersEnabled> = []
	private unchangeablePeripherals: Array<string> = [
		'coordinator',
		'node',
		'os'
	]
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

	@Prop({required: true}) address!: number

	@Watch('address')
	getAddress(): void {
		this.config = null;
		this.peripherals = [];
		if (this.$store.getters.isSocketConnected) {
			this.$store.dispatch('spinner/show', {timeout: 30000});
			IqrfNetService.enumerateDevice(this.address, 30000, 'iqrfnet.trConfiguration.messages.read.failure', () => this.msgId = null)
				.then((msgId: string) => this.msgId = msgId);
		}
	}
	
	get rfChannelRules(): Dictionary<string|number>|undefined {
		if (this.config === null) {
			return undefined;
		}
		switch (this.config.rfBand) {
			case '433':
				return {rule:'integer|between:0,16|required', min: 0, max: 16};
			case '868':
				return {rule: 'integer|between:0,67|required', min: 0, max: 67};
			case '916':
				return {rule: 'integer|between:0,255|required', min: 0, max: 255};
		}
		return undefined;
	}

	get rfChannelValidatorMessages(): Dictionary<string> {
		let message = '';
		if (this.config !== null) {
			switch (this.config.rfBand) {
				case '433':
					message = 'iqrfnet.trConfiguration.form.messages.rfChannel.433';
					break;
				case '868':
					message = 'iqrfnet.trConfiguration.form.messages.rfChannel.868';
					break;
				case '916':
					message = 'iqrfnet.trConfiguration.form.messages.rfChannel.916';
					break;
			}
		}
		return {
			between: message,
			integer: message,
			required: message
		};
	}

	get uartBaudRates(): Array<IOption> {
		const uartBaudRates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200, 230400];
		return uartBaudRates.map((uartBaudRate) => {
			return {
				value: uartBaudRate,
				label: this.$t('iqrfnet.trConfiguration.form.uartBaudrates.' + uartBaudRate).toString(),
			};
		});
	}

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('min', min_value);
		extend('max', max_value);
		extend('required', required);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type !== 'SOCKET_ONMESSAGE') {
				return;
			}
			if (mutation.payload.mType === 'iqmeshNetwork_WriteTrConf' &&
				mutation.payload.data.msgId === this.msgId) {
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				this.handleWriteResponse(mutation.payload);
				return;
			}
			if (mutation.payload.mType === 'iqmeshNetwork_EnumerateDevice' &&
				mutation.payload.data.msgId === this.msgId) {
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				this.handleEnumerationResponse(mutation.payload);
			}
		});
		if (this.$store.getters.isSocketConnected) {
			this.enumerate();
		} else {
			this.unwatch = this.$store.watch(
				(state: WebSocketClientState, getter: any) => getter.isSocketConnected,
				(newVal: boolean, oldVal: boolean) => {
					if (!oldVal && newVal) {
						this.enumerate();
						this.unwatch();
					}
				}
			);
		}
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	private enumerate(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.enumerateDevice(this.address, 30000, 'iqrfnet.trConfiguration.messages.read.failure', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	private handleEnumerationResponse(response): void {
		if (response.data.status !== 0) {
			this.$store.commit('spinner/HIDE');
			this.$toast.error(
				this.$t('iqrfnet.trConfiguration.messages.read.failure').toString()
			);
			return;
		}
		let rsp = response.data.rsp;
		this.config = rsp.trConfiguration;
		this.dpaHandlerDetected = rsp.osRead.flags.dpaHandlerDetected;
		this.dpaVersion = rsp.peripheralEnumeration.dpaVer;
		this.setEmbeddedPeripherals();
		this.$store.commit('spinner/HIDE');
		if (this.$store.getters['user/getRole'] === 'normal') {
			this.$toast.success(
				this.$t('iqrfnet.trConfiguration.messages.read.success').toString()
			);
		}
	}

	private handleSubmit(): void {
		let config = JSON.parse(JSON.stringify(this.config));
		config.embPers = this.getEmbeddedPeripherals();
		this.$store.dispatch('spinner/show', {timeout: 60000});
		IqrfNetService.writeTrConfiguration(this.address, config, 60000, 'iqrfnet.trConfiguration.messages.write.failure', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}

	private handleWriteResponse(response): void {
		this.$store.commit('spinner/HIDE');
		if (response.data.status === 0) {
			this.$toast.success(
				this.$t('iqrfnet.trConfiguration.messages.write.success').toString()
			);
		} else {
			this.$toast.error(
				this.$t('iqrfnet.trConfiguration.messages.write.failure').toString()
			);
		}
	}

	private setEmbeddedPeripherals(): void {
		if (this.config === null) {
			return;
		}
		let peripherals = JSON.parse(JSON.stringify(this.config.embPers));
		this.peripherals = [];
		for (let peripheral in peripherals) {
			if (!Object.prototype.hasOwnProperty.call(peripherals, peripheral)) {
				continue;
			}
			if (typeof this.config.embPers[peripheral] !== 'boolean') {
				continue;
			}
			if (this.unchangeablePeripherals.includes(peripheral) &&
					this.$store.getters['user/getRole'] === 'normal') {
				continue;
			}
			this.peripherals.push({
				name: peripheral,
				enabled: this.config.embPers[peripheral],
			});
		}
	}

	private getEmbeddedPeripherals(): IEmbedPers {
		let peripherals = {};
		for (let peripheral of this.peripherals) {
			peripherals[peripheral.name] = peripheral.enabled;
		}
		return (peripherals as IEmbedPers);
	}
}
</script>
