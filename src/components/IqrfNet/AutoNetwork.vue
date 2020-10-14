<template>
	<CCard class='border-0'>
		<CCardBody v-if='daemonVersion == null'>
			<CAlert color='danger'>
				{{ $t('iqrfnet.networkManager.messages.autoNetwork.versionMissing') }}
			</CAlert>
		</CCardBody>
		<CCardBody v-else-if='daemonVersion !== null && versionValid'>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm>
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
							v-model.number='autoNetwork.discoveryTxPower'
							requred='true'
							type='number'
							min='0'
							max='7'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.discoveryTxPower")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='autoNetwork.discoveryBeforeStart'
						:label='$t("iqrfnet.networkManager.autoNetwork.form.discoveryBeforeStart")'
					/>
					<CInputCheckbox
						:checked.sync='autoNetwork.skipDiscoveryEachWave'
						:label='$t("iqrfnet.networkManager.autoNetwork.form.skipDiscoveryEachWave")'
					/><hr>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:0,3'
						:custom-messages='{
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							required: "iqrfnet.networkManager.messages.autoNetwork.actionRetries",
							between: "iqrfnet.networkManager.messages.autoNetwork.actionRetries"
						}'
					>
						<CInput
							v-model.number='autoNetwork.actionRetries'
							type='number'
							min='0'
							max='3'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.actionRetries")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider><hr>
					<h4>
						{{ $t('iqrfnet.networkManager.autoNetwork.form.bondingControl') }}
					</h4>
					<CInputCheckbox
						:checked.sync='useOverlappingNetworks'
						:label='$t("iqrfnet.networkManager.autoNetwork.form.overlappingNetworks")'
					/>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:1,50'
						:custom-messages='{
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							required: "iqrfnet.networkManager.messages.autoNetwork.networks",
							between: "iqrfnet.networkManager.messages.autoNetwork.networks"
						}'
					>
						<CInput
							v-model.number='overlappingNetworks.networks'
							type='number'
							min='1'
							max='50'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.networks")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:disabled='!useOverlappingNetworks'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:1,50'
						:custom-messages='{
							integer: "iqrfnet.networkManager.messages.invalid.integer",
							required: "iqrfnet.networkManager.messages.autoNetwork.network",
							between: "iqrfnet.networkManager.messages.autoNetwork.network"
						}'
					>
						<CInput
							v-model.number='overlappingNetworks.network'
							type='number'
							min='1'
							max='50'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.network")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:disabled='!useOverlappingNetworks'
						/>
					</ValidationProvider><hr>
					<h4>
						{{ $t('iqrfnet.networkManager.autoNetwork.form.hwpidFiltering') }}
					</h4>
					<CInputCheckbox
						:checked.sync='useHwpidFiltering'
						:label='$t("iqrfnet.networkManager.autoNetwork.form.hwpidEnable")'
					/>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='hwpidFilter'
						:custom-messages='{
							hwpidFilter: "iqrfnet.networkManager.messages.invalid.autoNetwork.hwpidFilter"
						}'
					>
						<CInput
							v-model='hwpidFiltering'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.hwpids")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:disabled='!useHwpidFiltering'
						/>
					</ValidationProvider><hr>
					<h4>
						{{ $t('iqrfnet.networkManager.autoNetwork.form.stopConditions') }}
					</h4>
					<div class='form-group'>
						<CInputCheckbox
							:checked.sync='useWaves'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.waves")'
						/>
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
								v-model.number='stopConditions.waves'
								type='number'
								min='1'
								max='127'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:disabled='!useWaves'
							/>
						</ValidationProvider>
					</div>
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
							v-model.number='stopConditions.emptyWaves'
							type='number'
							min='1'
							max='127'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.emptyWaves")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<div class='form-group'>
						<CInputCheckbox
							:checked.sync='useNodes'
							:label='$t("iqrfnet.networkManager.autoNetwork.form.nodes")'
						/>
						<CSelect
							:value.sync='nodeCondition'
							:options='[
								{value: "new", label: "New"},
								{value: "total", label: "Total"}
							]'
							:disabled='!useNodes'
						/>
						<ValidationProvider
							v-if='nodeCondition === "total"'
							v-slot='{ errors, touched, valid }'
							rules='integer|required|between:1,239'
							:custom-messages='{
								integer: "iqrfnet.networkManager.messages.invalid.integer",
								required: "iqrfnet.networkManager.messages.autoNetwork.totalNodes",
								between: "iqrfnet.networkManager.messages.autoNetwork.totalNodes"
							}'
						>
							<CInput
								v-model.number='stopConditions.numberOfTotalNodes'
								type='number'
								min='1'
								max='239'
								:label='$t("iqrfnet.networkManager.autoNetwork.form.numberOfTotalNodes")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:disabled='!useNodes'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-if='nodeCondition === "new"'
							v-slot='{ errors, touched, valid }'
							rules='integer|required|between:1,239'
							:custom-messages='{
								integer: "iqrfnet.networkManager.messages.invalid.integer",
								required: "iqrfnet.networkManager.messages.autoNetwork.newNodes",
								between: "iqrfnet.networkManager.messages.autoNetwork.newNodes"
							}'
						>
							<CInput
								v-model.number='stopConditions.numberOfNewNodes'
								type='number'
								min='1'
								max='239'
								:label='$t("iqrfnet.networkManager.autoNetwork.form.numberOfNewNodes")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:disabled='!useNodes'
							/>
						</ValidationProvider>
					</div>
					<CInputCheckbox
						:checked.sync='stopConditions.abortOnTooManyNodesFound'
						:label='$t("iqrfnet.networkManager.autoNetwork.form.abortOnTooManyNodesFound")'
						:disabled='!useNodes'
					/>
					<CButton 
						color='primary'
						type='button'
						:disabled='invalid'
						@click='processSubmitAutoNetwork'
					>
						{{ $t('forms.start') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
		<CCardBody v-else>
			<CAlert color='danger'>
				{{ invalidVersionBody }}<br>
				<span v-if='daemonVersion !== null'>
					{{ $t('iqrfnet.networkManager.messages.autoNetwork.versionCurrent') }} {{ daemonVersion }}
				</span>
			</CAlert>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CAlert, CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import compareVersions from 'compare-versions';
import {between, integer, required} from 'vee-validate/dist/rules';
import IqrfNetService from '../../services/IqrfNetService';
import VersionService from '../../services/VersionService';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';
import VueI18n from 'vue-i18n';
import {AutoNetworkBase, AutoNetworkOverlappingNetworks, AutoNetworkStopConditions} from '../../interfaces/autonetwork';

interface NodeMessages {
	nodesNew: string
	nodesTotal: string
}

@Component({
	components: {
		CAlert,
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider
	}
})

export default class AutoNetwork extends Vue {
	private autoAddress = false
	private autoNetwork: AutoNetworkBase = {
		actionRetries: 1,
		discoveryBeforeStart: false,
		discoveryTxPower: 7,
		skipDiscoveryEachWave: false
	}
	private daemonVersion: string|null = null
	private hwpidFiltering = ''
	private messages: NodeMessages = {
		nodesNew: '',
		nodesTotal: ''
	}
	private msgId: string|null = null
	private nodeCondition = 'new'
	private overlappingNetworks: AutoNetworkOverlappingNetworks = {
		network: 1,
		networks: 1
	}
	private stopConditions: AutoNetworkStopConditions = {
		abortOnTooManyNodesFound: false,
		emptyWaves: 2,
		numberOfNewNodes: 1,
		numberOfTotalNodes: 1,
		waves: 10
	}
	private useHwpidFiltering = false
	private useNodes = true
	private useOverlappingNetworks = false
	private useWaves = false
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}

	get invalidVersionBody(): VueI18n.TranslateResult {
		if (this.daemonVersion === null) {
			return this.$t('iqrfnet.networkManager.messages.autoNetwork.versionMissing').toString();
		} else {
			return this.$t('iqrfnet.networkManager.messages.autoNetwork.versionInvalid').toString();
		}
	}

	get versionValid(): boolean {
		if (this.daemonVersion === null) {
			return false;
		} else {
			if (compareVersions.compare(this.daemonVersion, '2.3.0', '>=')) {
				return true;
			}
			return false;
		}
	}

	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		extend('hwpidFilter', (val) => {
			const regex = RegExp('^(6553[0-5]|655[0-2][0-9]|65[0-4][0-9]{2}|6[0-4][0-9]{3}|[1-5][0-9]{4}|[1-9][0-9]{1,3}|[1-9])( (6553[0-5]|655[0-2][0-9]|65[0-4][0-9]{2}|6[0-4][0-9]{3}|[1-5][0-9]{4}|[1-9][0-9]{1,3}|[1-9]))*$');
			return regex.test(val);
		});
		this.unsubscribe = this.$store.subscribe(mutation => {
			if (mutation.type === 'SOCKET_ONERROR' ||
				mutation.type === 'SOCKET_ONCLOSE') {
				if (this.$store.getters['spinner/isEnabled']) {
					this.$store.commit('spinner/HIDE');
				}
				return;
			}
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'iqmeshNetwork_AutoNetwork') {
					switch(mutation.payload.data.status) {
						case -1:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.timeout')
									.toString()
							);
							break;
						case 0:
							this.$store.commit('spinner/UPDATE_TEXT', this.autoNetworkProgress(mutation.payload.data));
							if (mutation.payload.data.rsp.lastWave) {
								this.$store.commit('spinner/HIDE');
								this.$store.dispatch('removeMessage', this.msgId);
								this.$toast.success(
									this.$t('iqrfnet.networkManager.messages.submit.autoNetwork.success')
										.toString()
								);
								this.$emit('update-devices');
							}
							break;
						default:
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.autoNetwork.failure')
									.toString()
							);
							break;
					}
				} else if (mutation.payload.mType === 'messageError') {
					this.$store.commit('spinner/HIDE');
					this.$toast.error(
						this.$t('iqrfnet.networkManager.messages.submit.invalidMessage')
							.toString()
					);
				} else if (mutation.payload.mType === 'mngDaemon_Version' &&
							mutation.payload.data.msgId === this.msgId) {
					this.$store.dispatch('spinner/hide');
					this.$store.dispatch('removeMessage', this.msgId);
					if (mutation.payload.data.status === 0 ) {
						this.daemonVersion = mutation.payload.data.rsp.version.substring(0, 6);
					} else {
						this.$toast.error(this.$t('iqrfnet.networkManager.messages.autoNetwork.versionFailure').toString());
					}
				}
			}
		});
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	private getVersion(): void {
		this.$store.dispatch('spinner/show', {timeout: 10000});
		VersionService.getVersion(new WebSocketOptions(null, 10000, 'iqrfnet.networkManager.messages.autoNetwork.versionFailure', () => this.msgId = null))
			.then((msgId) => this.msgId = msgId);
	}

	private autoNetworkProgress(response): string {
		let message = '\nWave ' + response.rsp.wave;
		if (this.useWaves) {
			message += '/ ' + this.stopConditions.waves;
		}
		message += '\n[' + response.rsp.progress + '%] ';
		if (response.rsp.waveState) {
			message += response.rsp.waveState;
		}
		if (response.rsp.nodesNr) {
			this.messages.nodesTotal = '\nTotal number of nodes in network: ' + response.rsp.nodesNr;
		}
		if (response.rsp.newNodesNr) {
			this.messages.nodesNew = '\nNumber of nodes added in last wave: ' + response.rsp.newNodesNr;
		}
		message += this.messages.nodesTotal + this.messages.nodesNew;
		return message;
	}

	private processSubmitAutoNetwork(): void {
		this.messages.nodesTotal = this.messages.nodesNew = '';
		let submitData = this.autoNetwork;
		let stopConditions = {};
		stopConditions['emptyWaves'] = this.stopConditions.emptyWaves;
		if (this.useWaves) {
			stopConditions['waves'] = this.stopConditions.waves;
		}
		if (this.useNodes) {
			stopConditions['abortOnTooManyNodesFound'] = this.stopConditions.abortOnTooManyNodesFound;
			if (this.nodeCondition === 'total') {
				stopConditions['numberOfTotalNodes'] = this.stopConditions.numberOfTotalNodes;
			} else {
				stopConditions['numberOfNewNodes'] = this.stopConditions.numberOfNewNodes;
			}
		}
		if (Object.keys(stopConditions).length > 0) {
			submitData['stopConditions'] = stopConditions;
		}
		if (this.useOverlappingNetworks) {
			submitData['overlappingNetworks'] = this.overlappingNetworks;
		}
		if (this.useHwpidFiltering && this.hwpidFiltering.length > 0) {
			submitData['hwpidFiltering'] = this.hwpidFiltering.split(', ').map((i) => parseInt(i));
		}
		this.$store.commit('spinner/SHOW');
		IqrfNetService.autoNetwork(submitData, new WebSocketOptions(null))
			.then((msgId) => this.msgId = msgId);
	}
}
</script>
