<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.trConfiguration.security.title') }}</CCardHeader>
		<CCardBody>
			<CForm>
				<CSelect
					:value.sync='format'
					:options='selectOptions'
					:label='$t("iqrfnet.trConfiguration.security.form.format")'
				/>
				<CInput
					v-model='password'
					:label='$t("iqrfnet.trConfiguration.security.form.password")'
				/>
				<CButton
					color='primary'
					@click='save(true)'
				>
					{{ $t("iqrfnet.trConfiguration.security.form.setPassword") }}
				</CButton> <CButton
					color='primary'
					@click='save(false)'
				>
					{{ $t("iqrfnet.trConfiguration.security.form.setKey") }}
				</CButton>
			</CForm>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CSelect} from '@coreui/vue/src';
import SecurityService from '../../services/SecurityService';
import {SecurityFormat} from '../../iqrfNet/securityFormat';
import { IOption } from '../../interfaces/IOption';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		CSelect,
	}
})

export default class SecurityForm extends Vue {
	private format: SecurityFormat = SecurityFormat.ASCII.valueOf()
	private msgId: string|null = null
	private password = ''
	private selectOptions: Array<IOption> = [
		{
			value: SecurityFormat.ASCII.valueOf(),
			label: this.$t('iqrfnet.trConfiguration.security.form.ascii').toString(),
		},
		{
			value: SecurityFormat.HEX.valueOf(),
			label: this.$t('iqrfnet.trConfiguration.security.form.hex').toString(),
		}
	]
	private unsubscribe: CallableFunction = () => {return;}

	@Prop({required: true}) address!: number

	created(): void {
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type !== 'SOCKET_ONMESSAGE') {
				return;
			}
			if (mutation.payload.data.msgId === this.msgId) {
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				if (mutation.payload.data.status === 0) {
					this.$toast.success(this.$t('iqrfnet.trConfiguration.security.messages.success').toString());
				} else {
					this.$toast.error(this.$t('iqrfnet.trConfiguration.security.messages.failure').toString());
				}
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private save(password: boolean): void {
		let pattern = '';
		if (this.format === SecurityFormat.ASCII) {
			pattern = '^[ -~]{0,16}$';
		} else {
			pattern = '^[a-fA-F0-9]{0,32}$';
		}
		const regex = RegExp(pattern);
		if (!regex.test(this.password)) {
			this.$toast.error('Password string is not valid for the selected format.');
			return;
		}
		this.$store.dispatch('spinner/show', {timeout: 15000});
		const type = password ? 0 : 1;
		SecurityService.setSecurity(this.address, this.password, this.format, type, 15000, 'iqrfnet.trConfiguration.security.messages.failure', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
