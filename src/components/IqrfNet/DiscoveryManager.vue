<template>
	<CCard class='border-0 card-margin-bottom'>
		<CCardBody>
			<h4>{{ $t('iqrfnet.networkManager.discovery.title') }}</h4><br>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='runDiscovery'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:0,7'
						:custom-messages='{
							integer: "forms.errors.integer",
							required: "iqrfnet.networkManager.discovery.errors.txPower",
							between: "iqrfnet.networkManager.discovery.errors.txPower"
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
							integer: "forms.errors.integer",
							required: "iqrfnet.networkManager.discovery.errors.maxAddr",
							between: "iqrfnet.networkManager.discovery.errors.maxAddr"
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
						{{ $t("forms.runDiscovery") }}
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

/**
 * Discovery manager card for Network Manager
 */
export default class DiscoveryManager extends Vue {
	/**
	 * @var {number} maxAddr Maximum node address
	 */
	private maxAddr = 239
	
	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {number} txPower Discovery call TX power
	 */
	private txPower = 6

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.data.msgId !== this.msgId) {
					return;
				}
				this.$store.commit('spinner/HIDE');
				this.$store.dispatch('removeMessage', this.msgId);
				this.handleDiscoveryResponse(mutation.payload);
			}
		});
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	/**
	 * Performs Discovery Daemon API call
	 */
	private runDiscovery(): void {
		this.$store.commit('spinner/SHOW');
		this.$store.commit('spinner/UPDATE_TEXT', this.$t('iqrfnet.networkManager.discovery.messages.spinnerNote').toString());
		IqrfNetService.discovery(this.txPower, this.maxAddr, new WebSocketOptions(null))
			.then((msgId: string) => this.msgId = msgId);
	}

	/**
	 * Handles Discovery Daemon API call response
	 */
	private handleDiscoveryResponse(response): void {
		if (response.data.status === 0) {
			this.$emit('update-devices', {
				message: this.$t('iqrfnet.networkManager.discovery.messages.success').toString(),
				type: 'success',
			});
		} else if (response.data.status === -1) {
			this.$toast.error(
				this.$t('iqrfnet.networkManager.discovery.messages.timeout').toString()
			);
		} else {
			this.$toast.error(
				this.$t('iqrfnet.networkManager.discovery.mesages.genericError').toString()
			);
		}
	}
}
</script>
