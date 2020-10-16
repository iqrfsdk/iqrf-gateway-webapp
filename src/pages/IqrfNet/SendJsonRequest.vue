<template>
	<div>
		<h1>{{ $t('iqrfnet.sendJson.title') }}</h1>
		<CCard>
			<CCardHeader>
				<CButton 
					color='primary'
					size='sm' 
					href='https://docs.iqrf.org/iqrf-gateway/daemon-api.html'
					target='_blank'
				>
					{{ $t("iqrfnet.sendJson.documentation") }}
				</CButton>
			</CCardHeader>
			<CCardBody v-if='daemonAvailable'>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='processSubmit'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required|json|mType'
							:custom-messages='{
								required: "iqrfnet.sendJson.form.messages.missing",
								json: "iqrfnet.sendJson.form.messages.invalid",
								mType: "iqrfnet.sendJson.form.messages.mType"
							}'
						>
							<CTextarea
								v-model='json'
								v-autogrow
								:label='$t("iqrfnet.sendJson.form.json")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CButton color='primary' type='submit' :disabled='invalid'>
							{{ $t('forms.send') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
			<CCardBody v-else>
				<CAlert color='danger'>
					{{ $t('iqrfnet.sendJson.notAvailable', {attempt: reconnectAttempt}) }}
				</CAlert>
			</CCardBody>
		</CCard>
		<RequestAndResponse :request='request' :response='response' :source='"sendJson"' />
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CAlert, CButton, CCard, CCardBody, CCardHeader, CForm, CTextarea} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import RequestAndResponse from '../../components/IqrfNet/RequestAndResponse.vue';

import {TextareaAutogrowDirective} from 'vue-textarea-autogrow-directive/src/VueTextareaAutogrowDirective';
import {StatusMessages} from '../../iqrfNet/sendJson';
import IqrfNetService from '../../services/IqrfNetService';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';

@Component({
	components: {
		CAlert,
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CTextarea,
		RequestAndResponse,
		ValidationObserver,
		ValidationProvider,
	},
	directives: {
		'autogrow': TextareaAutogrowDirective
	},
	metaInfo: {
		title: 'iqrfnet.sendJson.title',
	},
})

export default class SendJsonRequest extends Vue {
	private daemonAvailable = true
	private json: string|null = null
	private msgId: string|null = null
	private reconnectAttempt = 0
	private request: string|null = null
	private response: string|null = null
	private unsubscribe: CallableFunction = () => {return;}

	created(): void {
		extend('json', (json) => {
			try {
				JSON.parse(json);
				return true;
			} catch (error) {
				return false;
			}
		});
		extend('mType', (json) => {
			let object = JSON.parse(json);
			return {}.hasOwnProperty.call(object, 'mType');
		});
		extend('required', required);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONOPEN') {
				this.daemonAvailable = true;
			} else if (mutation.type === 'SOCKET_ONCLOSE' || 
				mutation.type === 'SOCKET_ONERROR') {
				this.daemonAvailable = false;
			} else if (mutation.type === 'SOCKET_RECONNECT') {
				this.reconnectAttempt = mutation.payload;
			} else if (mutation.type === 'SOCKET_ONMESSAGE') {
				if ({}.hasOwnProperty.call(mutation.payload, 'mType')) {
					if (mutation.payload.data.msgId === this.msgId) {
						this.$store.commit('spinner/HIDE');
						this.$store.dispatch('removeMessage', this.msgId);
						this.response = JSON.stringify(mutation.payload, null, 4);
						if (mutation.payload.data.status === 0) {
							this.$toast.success(
								this.$t('iqrfnet.sendJson.form.messages.success')
									.toString()
							);
						} else {
							if (mutation.payload.data.status in StatusMessages) {
								this.$toast.error(
									this.$t(StatusMessages[mutation.payload.data.status])
										.toString()
								);
							} else {
								this.$toast.error(
									this.$t('iqrfnet.sendJson.form.messages.error.fail')
										.toString()
								);
							}
						}
					} else if (mutation.payload.mType === 'iqmeshNetwork_AutoNetwork') {
						this.response = JSON.stringify(mutation.payload, null, 4);
					} else if (mutation.payload.mType === 'messageError') {
						this.response = JSON.stringify(mutation.payload, null, 4);
						if (mutation.payload.data.rsp.errorStr.includes('daemon overload')) {
							this.$toast.error(
								this.$t('iqrfnet.sendJson.form.messages.error.messageQueueFull')
									.toString()
							);
						} else {
							this.$toast.error(
								this.$t('iqrfnet.sendJson.form.messages.error.invalidMessage')
									.toString()
							);
						}
						
					}
				}
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private processSubmit(): void {
		if (this.json === null) {
			return;
		}
		const json = JSON.parse(this.json);
		let options = new WebSocketOptions(json);
		if ({}.hasOwnProperty.call(json.data.req, 'nAdr') && json.data.req.nAdr === 255) {
			options.timeout = 1000;
		} else if (json.mType === 'iqrfEmbedOs_Batch' || json.mType === 'iqrfEmbedOs_SelectiveBatch') {
			options.timeout = 1000;
		} else {
			options.timeout = 60000;
			options.message = 'iqrfnet.sendJson.form.messages.error.fail';
			this.$store.commit('spinner/SHOW');
		}
		options.callback = () => this.msgId = null;
		this.request = JSON.stringify(json, null, 4);
		this.response = null;
		IqrfNetService.sendJson(options)
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
