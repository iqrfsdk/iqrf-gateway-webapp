<template>
	<CCard>
		<CCardHeader>{{ $t('iqrfnet.standard.binaryOutput.title') }}</CCardHeader>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:1,239'
						:custom-messages='{
							integer: "forms.messages.integer",
							required: "iqrfnet.standard.form.messages.address",
							between: "iqrfnet.standard.form.messages.address"
						}'
					>
						<CInput
							v-model.number='address'
							type='number'
							min='1'
							max='239'
							:label='$t("iqrfnet.standard.form.address")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:0,31'
						:custom-messages='{
							integer: "forms.messages.integer",
							required: "iqrfnet.standard.binaryOutput.form.messages.index",
							between: "iqrfnet.standard.binaryOutput.form.messages.index"
						}'
					>
						<CInput
							v-model.number='index'
							type='number'
							min='0'
							max='31'
							:label='$t("iqrfnet.standard.binaryOutput.form.index")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<div class='form-group'>
						<label for='formStateSwitch'>
							{{ $t('iqrfnet.standard.binaryOutput.form.state') }}
						</label><br>
						<CSwitch
							id='formStateSwitch'
							:checked.sync='state'
							color='primary'
							size='lg'
							shape='pill'
							:label-on='$t("iqrfnet.standard.binaryOutput.form.enabled")'
							:label-off='$t("iqrfnet.standard.binaryOutput.form.disabled")'
						/>
					</div>
					<CButton
						color='primary'
						:disabled='invalid'
						@click='submitEnumerate'
					>
						{{ $t('forms.enumerate') }}
					</CButton> <CButton
						color='secondary'
						:disabled='invalid'
						@click='submitGetStates'
					>
						{{ $t('iqrfnet.standard.binaryOutput.form.getStates') }}
					</CButton> <CButton
						color='secondary'
						:disabled='invalid'
						@click='submitSetState'
					>
						{{ $t('iqrfnet.standard.binaryOutput.form.setState') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
		<CCardFooter v-if='responseType !== null'>
			<table v-if='responseType === "enum"' class='table'>
				<thead>
					{{ $t('iqrfnet.standard.binaryOutput.enum') }}
				</thead>
				<tbody>
					<tr>
						<th>{{ $t('iqrfnet.standard.binaryOutput.outputs') }}</th>
						<td>{{ numOutputs }}</td>
					</tr>
				</tbody>
			</table>
			<table v-if='responseType === "set"' class='table scroll-table'>
				<thead>
					{{ $t('iqrfnet.standard.binaryOutput.prev') }}
				</thead>
				<tbody>
					<tr>
						<th>{{ $t('iqrfnet.standard.binaryOutput.index') }}</th>
						<td v-for='col of Array(32).keys()' :key='col'>
							{{ col }}
						</td>
					</tr>
					<tr>
						<th>{{ $t('iqrfnet.standard.binaryOutput.state') }}</th>
						<td v-for='ind of Array(32).keys()' :key='ind'>
							<CIcon
								v-if='states[ind] === true'
								class='text-success'
								:content='icons.on'
							/>
							<CIcon
								v-if='states[ind] === false'
								class='text-danger'
								:content='icons.off'
							/>
						</td>
					</tr>
				</tbody>
			</table>
		</CCardFooter>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CIcon, CInput, CSwitch} from '@coreui/vue/src';
import {cilCheckAlt, cilX} from '@coreui/icons';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import StandardBinaryOutputService, {StandardBinaryOutput} from '../../services/DaemonApi/StandardBinaryOutputService';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';
import { Dictionary } from 'vue-router/types/router';
import { MutationPayload } from 'vuex';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CIcon,
		CInput,
		CSwitch,
		ValidationObserver,
		ValidationProvider
	}
})

export default class BinaryOutputManager extends Vue {
	private address = 1
	private allowedMTypes: Array<string> = [
		'iqrfBinaryoutput_Enumerate',
		'iqrfBinaryoutput_SetOutput'
	]
	private icons: Dictionary<string[]> = {
		on: cilCheckAlt,
		off: cilX
	}
	private index = 0
	private msgId: string|null = null
	private numOutputs = 0
	private responseType: string|null = null
	private state = false
	private states: Array<number> = []
	private unsubscribe: CallableFunction = () => {return;}

	created(): void {
		extend('integer', integer);
		extend('required', required);
		extend('between', between);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONSEND') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				this.responseType = null;
			}
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (!this.allowedMTypes.includes(mutation.payload.mType)) {
					return;
				}
				if (mutation.payload.data.msgId !== this.msgId) {
					return;
				}
				this.$store.dispatch('spinner/hide');
				this.$store.dispatch('removeMessage', this.msgId);
				switch(mutation.payload.data.status) {
					case -1:
						this.$toast.error(
							this.$t('iqrfnet.standard.binaryOutput.messages.timeout')
								.toString()
						);
						break;
					case 0:
						this.$toast.success(
							this.$t('iqrfnet.standard.binaryOutput.messages.success')
								.toString()
						);
						if (mutation.payload.mType === 'iqrfBinaryoutput_Enumerate') {
							this.numOutputs = mutation.payload.data.rsp.result.binOuts;
							this.responseType = 'enum';
						} else if (mutation.payload.mType === 'iqrfBinaryoutput_SetOutput') {
							this.parseSetOutput(mutation.payload.data.rsp.result.prevVals);
							this.responseType = 'set';
						}
						break;
					case 1:
						this.$toast.error(
							this.$t('iqrfnet.standard.binaryOutput.messages.fail')
								.toString()
						);
						break;
					case 3:
						this.$toast.error(
							this.$t('iqrfnet.standard.binaryOutput.messages.pnum')
								.toString()
						);
						break;
				}

			}
		});
		this.generateStates();
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unsubscribe();
	}

	private generateStates(): void {
		this.states = new Array(60).fill(false);
	}

	private parseSetOutput(states: Array<number>): void {
		for(let i = 0; i < states.length; ++i) {
			this.states[i] = states[i];
		}
	}

	private buildOptions(): WebSocketOptions {
		return new WebSocketOptions(null, 30000, 'iqrfnet.standard.binaryOutput.messages.timeout', () => this.msgId = null);
	}

	private submitEnumerate(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		StandardBinaryOutputService.enumerate(this.address, this.buildOptions())
			.then((msgId: string) => this.msgId = msgId);
	}

	private submitGetStates(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		StandardBinaryOutputService.getOutputs(this.address, this.buildOptions())
			.then((msgId: string) => this.msgId = msgId);
	}

	private submitSetState(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		const output = new StandardBinaryOutput(this.index, this.state);
		StandardBinaryOutputService.setOutputs(this.address, [output], this.buildOptions())
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>

<style scoped>
.scroll-table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
}
</style>
