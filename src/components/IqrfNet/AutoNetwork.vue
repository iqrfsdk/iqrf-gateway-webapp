<template>
	<CCard class='border-0 card-margin-bottom'>
		<CCardBody>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required|between:0,7'
						:custom-messages='{
							integer: "forms.errors.integer",
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
							integer: "forms.errors.integer",
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
							integer: "forms.errors.integer",
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
							integer: "forms.errors.integer",
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
								integer: "forms.errors.integer",
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
							integer: "forms.errors.integer",
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
								integer: "forms.errors.integer",
								required: "iqrfnet.networkManager.messages.autoNetwork.totalNodes",
								between: "iqrfnet.networkManager.messages.autoNetwork.totalNodes"
							}'
						>
							<CInput
								v-model.number='stopConditions.nodeCount'
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
								integer: "forms.errors.integer",
								required: "iqrfnet.networkManager.messages.autoNetwork.newNodes",
								between: "iqrfnet.networkManager.messages.autoNetwork.newNodes"
							}'
						>
							<CInput
								v-model.number='stopConditions.nodeCount'
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
						{{ $t('forms.runAutonetwork') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {between, integer, required} from 'vee-validate/dist/rules';
import IqrfNetService from '../../services/IqrfNetService';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';
import {AutoNetworkBase, AutoNetworkOverlappingNetworks, AutoNetworkStopConditions} from '../../interfaces/autonetwork';
import {MutationPayload} from 'vuex';

interface NodeMessages {
	nodesNew: string
	nodesTotal: string
}

@Component({
	components: {
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

/**
 * AutoNetwork card for IqrfNet network manager
 */
export default class AutoNetwork extends Vue {
	/**
	 * @var {boolean} autoAddress Use first available address for bonding
	 */
	private autoAddress = false
	
	/**
	 * @var {AutoNetworkBase} autoNetwork Basic AutoNetwork process configuration
	 */
	private autoNetwork: AutoNetworkBase = {
		actionRetries: 1,
		discoveryBeforeStart: false,
		discoveryTxPower: 7,
		skipDiscoveryEachWave: false
	}

	/**
	 * @var {string} hwpidFiltering String of HWPIDs to filter nodes by
	 */
	private hwpidFiltering = ''

	/**
	 * @var {NodeMessages} messages Messages used in displaying AutoNetwork progress when spinner is active, bonded nodes
	 */
	private messages: NodeMessages = {
		nodesNew: '',
		nodesTotal: ''
	}
	
	/**
	 * @var {string|null} msgId Daemon api message id
	 */
	private msgId: string|null = null

	/**
	 * @var {string} nodeCondition AutoNetwork stop condition type for new or total nodes found in network
	 */
	private nodeCondition = 'new'

	/**
	 * @var {AutoNetworkOverlappingNetworks} overlappingNetworks AutoNetwork overlapping networks settings
	 */
	private overlappingNetworks: AutoNetworkOverlappingNetworks = {
		network: 1,
		networks: 1
	}

	/**
	 * @var {AutoNetworkStopConditions} stopConditions AutoNetwork process stop conditions configuration
	 */
	private stopConditions: AutoNetworkStopConditions = {
		abortOnTooManyNodesFound: false,
		emptyWaves: 2,
		waves: 10,
		nodeCount: 1
	}

	/**
	 * @var {boolean} useHwpidFiltering Filter nodes by HWPIDs
	 */
	private useHwpidFiltering = false

	/**
	 * @var {boolean} useNodes Use nodes found stop condition
	 */
	private useNodes = false

	/**
	 * @var {boolean} useOverlappingNetworks Use overlapping networks settings
	 */
	private useOverlappingNetworks = false

	/**
	 * @var {boolean} useWaves Use maximum number of waves stop condition
	 */
	private useWaves = false

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * Component unwatch function
	 */
	private unwatch: CallableFunction = () => {return;}

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		extend('hwpidFilter', (val) => {
			const regex = RegExp('^(6553[0-5]|655[0-2][0-9]|65[0-4][0-9]{2}|6[0-4][0-9]{3}|[1-5][0-9]{4}|[1-9][0-9]{1,3}|[1-9])( (6553[0-5]|655[0-2][0-9]|65[0-4][0-9]{2}|6[0-4][0-9]{3}|[1-5][0-9]{4}|[1-9][0-9]{1,3}|[1-9]))*$');
			return regex.test(val);
		});
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONERROR' ||
				mutation.type === 'SOCKET_ONCLOSE') { // websocket connection with daemon terminated, recover from state after sending message
				if (this.$store.getters['spinner/isEnabled']) {
					this.$store.commit('spinner/HIDE');
				}
				return;
			}
			if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'iqmeshNetwork_AutoNetwork') {
					switch(mutation.payload.data.status) {
						case -1:
							this.$store.commit('spinner/HIDE');
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
								this.$emit('update-devices', {
									message: this.$t('iqrfnet.networkManager.messages.submit.autoNetwork.success').toString(),
									type: 'success',
								});
							}
							break;
						default:
							this.$store.commit('spinner/HIDE');
							this.$toast.error(
								this.$t('iqrfnet.networkManager.messages.submit.autoNetwork.failure')
									.toString()
							);
							break;
					}
				} else if (mutation.payload.mType === 'messageError' &&
							mutation.payload.data.msgId === this.msgId) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', this.msgId);
					this.$toast.error(
						this.$t('iqrfnet.networkManager.messages.submit.invalidMessage')
							.toString()
					);
				}
			}
		});
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		this.unwatch();
		this.unsubscribe();
	}

	/**
	 * Creates progress message for running AutoNetwork process, used in spinner
	 * @param {any} response Daemon api response
	 * @returns {string} AutoNetwork progress message
	 */
	private autoNetworkProgress(response: any): string {
		let message = '\n' + this.$t('iqrfnet.networkManager.messages.autoNetwork.statusWave').toString() + response.rsp.wave;
		if (this.useWaves) { // maximum number of waves used in request
			message += '/ ' + this.stopConditions.waves;
		}
		message += '\n[' + response.rsp.progress + '%] ';
		if (response.rsp.waveState) { // wave information exists
			message += response.rsp.waveState;
		}
		if (response.rsp.nodesNr !== undefined) { // collects number of nodes in network
			this.messages.nodesTotal = '\n' + this.$t('iqrfnet.networkManager.messages.autoNetwork.statusTotalNodes').toString() + response.rsp.nodesNr;
		}
		if (response.rsp.newNodesNr !== undefined) { // collects number of nodes added to network in last wave
			this.messages.nodesNew = '\n' + this.$t('iqrfnet.networkManager.messages.autoNetwork.statusAddedNodes').toString() + response.rsp.newNodesNr;
		}
		message += this.messages.nodesTotal + this.messages.nodesNew;
		return message;
	}

	/**
	 * Builds AutoNetwork configuration object and performs the AutoNetwork process
	 */
	private processSubmitAutoNetwork(): void {
		this.messages.nodesTotal = this.messages.nodesNew = '';
		let submitData = this.autoNetwork;
		let stopConditions = {};
		stopConditions['emptyWaves'] = this.stopConditions.emptyWaves;
		if (this.useWaves) { // maximum number of waves is enabled
			stopConditions['waves'] = this.stopConditions.waves;
		}
		if (this.useNodes) { // node count stop conditions are used
			stopConditions['abortOnTooManyNodesFound'] = this.stopConditions.abortOnTooManyNodesFound;
			if (this.nodeCondition === 'total') {
				stopConditions['numberOfTotalNodes'] = this.stopConditions.nodeCount;
			} else {
				stopConditions['numberOfNewNodes'] = this.stopConditions.nodeCount;
			}
		}
		if (Object.keys(stopConditions).length > 0) { // local stop conditions are added to the request if they exist
			submitData['stopConditions'] = stopConditions;
		}
		if (this.useOverlappingNetworks) { // overlapping networks is enabled
			submitData['overlappingNetworks'] = this.overlappingNetworks;
		}
		if (this.useHwpidFiltering && this.hwpidFiltering.length > 0) { // hwpid filtering is enabled, convert from string to array of integers
			submitData['hwpidFiltering'] = this.hwpidFiltering.split(', ').map((i) => parseInt(i));
		}
		this.$store.commit('spinner/SHOW');
		IqrfNetService.autoNetwork(submitData, new WebSocketOptions(null))
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>
