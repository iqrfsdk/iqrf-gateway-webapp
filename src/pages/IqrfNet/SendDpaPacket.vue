<template>
	<div>
		<h1>{{ $t('iqrfnet.sendPacket.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='handleSubmit'>
					<ValidationProvider
						v-slot='{ valid, touched, errors }'
						rules='dpaPacket|required'
						:custom-messages='{required: "iqrfnet.sendPacket.form.messages.missing.packet"}'
					>
						<CInput
							v-model='packet'
							:label='$t("iqrfnet.sendPacket.form.packet")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							@input='setTimeout'
						/>
					</ValidationProvider>
					<CRow>
						<CCol md='6'>
							<CInputCheckbox
								:checked.sync='addressOverwrite'
								:label='$t("iqrfnet.sendPacket.form.addressOverwrite")'
							/>
							<ValidationProvider
								v-slot='{ valid, touched, errors }'
								:disabled='!addressOverwrite'
								:rules='addressOverwrite ? "integer|between:0,239|required" : ""'
								:custom-messages='{
									between: "iqrfnet.sendPacket.form.messages.invalid.address",
									integer: "iqrfnet.sendPacket.form.messages.invalid.address",
									required: "iqrfnet.sendPacket.form.messages.missing.address",
								}'
							>
								<CInput
									v-model.number='address'
									:disabled='!addressOverwrite'
									:label='$t("iqrfnet.sendPacket.form.address")'
									:is-valid='addressOverwrite && touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
									type='number'
									min='0'
									max='239'
								/>
							</ValidationProvider>
						</CCol>
						<CCol md='6'>
							<CInputCheckbox
								:checked.sync='timeoutOverwrite'
								:label='$t("iqrfnet.sendPacket.form.timeoutOverwrite")'
							/>
							<ValidationProvider
								v-slot='{ valid, touched, errors }'
								:rules='timeoutOverwrite ? "integer|min:0|required" : ""'
								:custom-messages='{
									integer: "iqrfnet.sendPacket.form.messages.invalid.timeout",
									min: "iqrfnet.sendPacket.form.messages.invalid.timeout",
									required: "iqrfnet.sendPacket.form.messages.missing.timeout",
								}'
							>
								<CInput
									v-model.number='timeout'
									:disabled='!timeoutOverwrite'
									:label='$t("iqrfnet.sendPacket.form.timeout")'
									:is-valid='timeoutOverwrite && touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
									type='number'
								/>
							</ValidationProvider>
						</CCol>
					</CRow>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.send') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
		<DpaMacros @set-packet='setPacket($event)' />
		<div>
			<CRow>
				<CCol v-if='request !== null' md='6'>
					<JsonMessage :message='request' type='request' source='sendDpa' />
				</CCol>
				<CCol v-if='response !== null' md='6'>
					<JsonMessage :message='response' type='response' source='sendDpa' />
				</CCol>
			</CRow>
		</div>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CCardHeader, CCol, CForm, CInput, CInputCheckbox, CRow} from '@coreui/vue/src';
import DpaMacros from '../../components/IqrfNet/DpaMacros.vue';
import {between, integer, min_value, required} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import sendPacket from '../../iqrfNet/sendPacket';
import JsonMessage from '../../components/IqrfNet/JsonMessage.vue';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';
import {RawMessage} from '../../interfaces/dpa';

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
		DpaMacros,
		JsonMessage,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo: {
		title: 'iqrfnet.sendPacket.title',
	}
})

export default class SendDpaPacket extends Vue {
	private address = 0
	private addressOverwrite = false
	private msgId: string|null = null
	private packet = ''
	private request: string|null = null
	private response: string|null = null
	private timeout = 1000
	private timeoutOverwrite = false
	private unsubscribe: CallableFunction = () => {return;}

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('min', min_value);
		extend('required', required);
		extend('dpaPacket', (packet) => {
			return sendPacket.validatePacket(packet) ? true : 'iqrfnet.sendPacket.form.messages.invalid.packet';
		});
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONSEND' &&
					mutation.payload.mType === 'iqrfRaw') {
				this.request = JSON.stringify(mutation.payload, null, 4);
				this.response = null;
			}
			if (mutation.type === 'SOCKET_ONMESSAGE' &&
				mutation.payload.mType === 'iqrfRaw' &&
				mutation.payload.data.msgId === this.msgId) {
				this.$store.commit('spinner/HIDE');
				this.$store.dispatch('removeMessage', this.msgId);
				this.response = JSON.stringify(mutation.payload, null, 4);
				switch (mutation.payload.data.status) {
					case 0:
						this.$toast.success(this.$t('iqrfnet.sendPacket.messages.success').toString());
						break;
					case 2:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.pcmd').toString());
						break;
					case 3:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.pnum').toString());
						break;
					case 5:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.dataLength').toString());
						break;
					case 6:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.data').toString());
						break;
					case 7:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.hwpid').toString());
						break;
					case 8:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.incorrect.nadr').toString());
						break;
					default:
						this.$toast.error(this.$t('iqrfnet.sendPacket.messages.failure').toString());
						break;
				}
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	/**
	 * Handles Send DPA packet form submit event
	 */
	private handleSubmit(): Promise<string>|void {
		if (this.packet === null || this.packet === '') {
			this.$toast.error(this.$t('iqrfnet.sendPacket.form.messages.missing.packet').toString());
			return;
		}
		const json: RawMessage = {
			'mType': 'iqrfRaw',
			'data': {
				'req': {
					'rData': this.packet,
				},
				'returnVerbose': true,
			},
		};
		if (this.addressOverwrite && this.address !== null) {
			json.data.req.rData = sendPacket.updateNadr(this.packet, this.address);
		}
		if (this.timeoutOverwrite && this.timeout !== null) {
			json.data.timeout = this.timeout;
		}
		let options = new WebSocketOptions(json);
		const packet = sendPacket.Packet.parse(this.packet);
		if (packet.nadr === 255) {
			options.timeout = 1000;
		} else if (packet.pnum === 2 && (packet.pcmd === 5 || packet.pcmd === 11)) {
			options.timeout = 1000;
		} else {
			options.timeout = 60000;
			options.message = 'iqrfnet.sendPacket.messages.failure';
			this.$store.commit('spinner/SHOW');
		}
		options.callback = () => this.msgId = null;
		return this.$store.dispatch('sendRequest', options)
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Sets new DPA packet
	 * @param {string} newPacket New DPA packet
	 */
	private setPacket(newPacket: string): void {
		this.packet = newPacket;
		this.setTimeout();
	}

	/**
	 * Sets DPA timeout
	 */
	private setTimeout(): void {
		let packet = sendPacket.Packet.parse(this.packet);
		let newTimeout = packet.detectTimeout();
		if (newTimeout === null) {
			this.timeoutOverwrite = false;
			this.timeout = 1000;
		} else {
			this.timeoutOverwrite = true;
			this.timeout = newTimeout;
		}
	}
}
</script>
