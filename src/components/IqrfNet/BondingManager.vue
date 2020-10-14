<template>
	<CCard class='border-top-0 border-left-0 border-right-0'>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm>
					<CSelect
						:value.sync='bondMethod'
						:label='$t("iqrfnet.networkManager.bonding.form.bondMethod")'
						:options='[
							{value: "local", label: $t("iqrfnet.networkManager.bonding.form.bondMethodLocal")},
							{value: "smartConnect", label: $t("iqrfnet.networkManager.bonding.form.bondMethodSmart")},
						]'
					/>
					<ValidationProvider
						v-if='bondMethod !== "autoNetwork"'
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:1,239'
						:custom-messages='{
							required: "iqrfnet.networkManager.messages.bonding.address",
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							between: "iqrfnet.networkManager.messages.bonding.address"
						}'
					>
						<CInput
							v-model.number='address'
							type='number'
							min='1'
							max='239'
							:label='$t("iqrfnet.networkManager.bonding.form.address")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:disabled='autoAddress'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='autoAddress'
						:label='$t("iqrfnet.networkManager.bonding.form.autoAddress")'
					/>
					<ValidationProvider
						v-slot='{ errors, touched, valid}'
						rules='integer|required|between:0,255'
						:custom-messages='{
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							required: "iqrfnet.networkManager.messages.bonding.bondingRetries",
							between: "iqrfnet.networkManager.messages.bonding.bondingRetries"
						}'
					>
						<CInput
							v-model.number='bondingRetries'
							type='number'
							min='0'
							max='255'
							:label='$t("iqrfnet.networkManager.bonding.form.bondingRetries")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-if='bondMethod === "smartConnect"'
						v-slot='{ errors, valid }'
						rules='required|scCode'
						:custom-messages='{
							required: "iqrfnet.networkManager.messages.missing.bonding.scCode",
							scCode: "iqrfnet.networkManager.messages.invalid.bonding.scCode"
						}'
					>
						<CInput
							v-model='scCode'
							:label='$t("iqrfnet.networkManager.bonding.form.smartConnect")'
							:is-valid='valid'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='unbondCoordinatorOnly'
						:label='$t("iqrfnet.networkManager.bonding.form.unbondCoordinatorOnly")'
					/>
					<CButton
						color='primary' 
						type='button' 
						:disabled='invalid' 
						@click.prevent='processSubmitBond'
					>
						{{ $t('forms.bond') }}
					</CButton> <CButton
						color='secondary' 
						type='button' 
						:disabled='invalid || autoAddress'
						@click='modalUnbond = true'
					>
						{{ $t('forms.unbond') }}
					</CButton> <CButton
						color='secondary' 
						type='button'
						@click='modalClear = true'
					>
						{{ $t('forms.clearBonds') }}
					</CButton>
					<CModal
						:title='$t("forms.clearBonds")'
						color='danger'
						:show.sync='modalClear'
					>
						{{ $t('iqrfnet.networkManager.messages.submit.removeBond.confirmClear') }}
						<template #footer>
							<CButton
								color='danger'
								@click='modalClear = false'
							>
								{{ $t('forms.no') }}
							</CButton> <CButton
								color='success'
								@click='processSubmitClearAll'
							>
								{{ $t('forms.yes') }}
							</CButton>
						</template>
					</CModal>
					<CModal
						:title='$t("forms.unbond")'
						color='danger'
						:show.sync='modalUnbond'
					>
						{{ $t('iqrfnet.networkManager.messages.submit.removeBond.confirmUnbond') }}
						<template #footer>
							<CButton
								color='danger'
								@click='modalUnbond = false'
							>
								{{ $t('forms.no') }}
							</CButton> <CButton
								color='success'
								@click='processSubmitUnbond'
							>
								{{ $t('forms.yes') }}
							</CButton>
						</template>
					</CModal>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox, CModal, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import IqrfNetService from '../../services/IqrfNetService';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		CModal,
		CSelect,
		ValidationObserver,
		ValidationProvider
	}
})

export default class BondingManager extends Vue {
	private address = 1
	private autoAddress = false
	private bondMethod = 'local'
	private bondingRetries = 1
	private modalClear = false
	private modalUnbond = false
	private msgId: string|null = null
	private scCode = ''
	private unbondCoordinatorOnly = false
	private unsubscribe: CallableFunction = () => {return;}

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		extend('scCode', (code) => {
			const regex = RegExp('^[a-zA-Z0-9]{34}$');
			return regex.test(code);
		});
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'iqmeshNetwork_BondNodeLocal' ||
					mutation.payload.mType === 'iqmeshNetwork_SmartConnect') {
					if (mutation.payload.data.msgId !== this.msgId) {
						return;
					}
					this.$store.dispatch('spinner/hide');
					this.$store.dispatch('removeMessage', this.msgId);
					switch(mutation.payload.data.status) {
						case -1:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.timeout')
									.toString()
							);
							break;
						case 0:
							this.$toast.success(
								this.$t('iqrfnet.networkManager.messages.submit.bonding.success')
									.toString()
							);
							this.$emit('update-devices');
							break;
						default:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.bonding.error_fail')
									.toString()
							);
							break;
					}
				} else if (mutation.payload.mType === 'iqrfEmbedCoordinator_ClearAllBonds' ||
							mutation.payload.mType === 'iqmeshNetwork_RemoveBond' ||
							mutation.payload.mType === 'iqrfEmbedCoordinator_RemoveBond') {
					if (mutation.payload.data.msgId !== this.msgId) {
						return;
					}
					this.$store.dispatch('spinner/hide');
					this.$store.dispatch('removeMessage', this.msgId);
					switch(mutation.payload.data.status) {
						case -1:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.timeout')
									.toString()
							);
							break;
						case 0:
							if (mutation.payload.data.rsp.nodesNr === 0) {
								this.$toast.success(
									this.$t('iqrfnet.networkManager.messages.submit.removeBond.clearSuccess')
										.toString()
								);
							} else {
								this.$toast.success(
									this.$t('iqrfnet.networkManager.messages.submit.removeBond.success')
										.toString()
								);
							}
							this.$emit('update-devices');
							break;
						case 1002:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.removeBond.remove_error')
									.toString()
							);
							break;
						default:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.removeBond.error_fail')
									.toString()
							);
							break;
					}
				} else if (mutation.payload.mType === 'messageError') {
					this.$store.dispatch('spinner/hide');
					this.$toast.error(
						this.$t('iqrfnet.networkManager.messages.submit.invalidMessage')
							.toString()
					);
				}
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private buildOptions(timeout: number, message: string): WebSocketOptions {
		return new WebSocketOptions(null, timeout, message, () => this.msgId = null);
	}

	private processSubmitBond(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		const address = this.autoAddress ? 0 : this.address;
		if (this.bondMethod === 'local') {
			IqrfNetService.bondLocal(address, this.buildOptions(30000, 'iqrfnet.networkManager.messages.submit.timeout'))
				.then((msgId: string) => this.msgId = msgId);

		} else if (this.bondMethod === 'smartConnect') {
			IqrfNetService.bondSmartConnect(address, this.scCode, this.bondingRetries, this.buildOptions(30000, 'iqrfnet.networkManager.messages.submit.timeout'))
				.then((msgId: string) => this.msgId = msgId);
		}
	}

	private processSubmitUnbond(): void {
		this.modalUnbond = false;
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.removeBond(this.address, this.unbondCoordinatorOnly, this.buildOptions(30000, 'iqrfnet.networkManager.messages.submit.timeout'))
			.then((msgId: string) => this.msgId = msgId);
	}

	private processSubmitClearAll(): void {
		this.modalClear = false;
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.clearAllBonds(this.unbondCoordinatorOnly, this.buildOptions(30000, 'iqrfnet.networkManager.messages.submit.timeout'))
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
