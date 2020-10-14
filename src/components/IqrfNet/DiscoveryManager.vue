<template>
	<CCard class='border-0'>
		<CCardHeader>
			{{ $t('iqrfnet.networkManager.discovery.title') }}
		</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='processSubmit'>
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
							v-model.number='txPower'
							type='number'
							min='0'
							max='7'
							:label='$t("iqrfnet.networkManager.discovery.form.txPower")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:0,239'
						:custom-messages='{
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							required: "iqrfnet.networkManager.messages.discovery.maxAddr",
							between: "iqrfnet.networkManager.messages.discovery.maxAddr"
						}'
					>
						<CInput
							v-model.number='maxAddr'
							type='number'
							min='0'
							max='239'
							:label='$t("iqrfnet.networkManager.discovery.form.maxAddr")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t("forms.discovery") }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import IqrfNetService from '../../services/IqrfNetService';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		ValidationObserver,
		ValidationProvider
	}
})

export default class DiscoveryManager extends Vue {
	private maxAddr = 239
	private msgId: string|null = null
	private txPower = 6
	private unsubscribe: CallableFunction = () => {return;}

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.data.msgId !== this.msgId) {
					return;
				}
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				switch (mutation.payload.data.status) {
					case -1:
						this.$toast.error(
							this.$t('iqrfnet.networkManager.messages.submit.timeout')
								.toString()
						);
						break;
					case 0:
						this.$toast.success(
							this.$t('iqrfnet.networkManager.messages.submit.discovery.success')
								.toString()
						);
						this.$emit('update-devices');
						break;
					default:
						this.$toast.success(
							this.$t('iqrfnet.networkManager.messages.submit.discovery.error_fail')
								.toString()
						);
						break;
				}
			
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private processSubmit(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.discovery(this.txPower, this.maxAddr, new WebSocketOptions(null, 30000, 'iqrfnet.networkManager.messages.submit.timeout', () => this.msgId = null))
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
