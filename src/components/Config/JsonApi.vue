<template>
	<CCard body-wrapper class='border-0 card-margin-bottom'>
		<ValidationObserver
			v-slot='{invalid}'
		>
			<CForm @submit.prevent='saveConfig'>
				<ValidationProvider
					v-slot='{errors, touched, valid}'
					rules='required'
					:custom-messages='{
						required: "config.daemon.misc.jsonSplitter.errors.insId"
					}'
				>
					<CInput
						v-model='insId'
						:label='$t("config.daemon.misc.jsonSplitter.form.insId")'
						:is-valid='touched ? valid : null'
						:invalid-feedback='$t(errors[0])'
					/>
				</ValidationProvider>
				<CInputCheckbox
					:checked.sync='metaDataToMessages'
					:label='$t("config.daemon.misc.jsonMngMetaDataApi.form.metaDataToMessages").toString()'
				/>
				<CInputCheckbox
					:checked.sync='asyncDpaMessage'
					:label='$t("config.daemon.misc.jsonRawApi.form.asyncDpaMessage").toString()'
				/>
				<CInputCheckbox
					:checked.sync='validateJsonResponse'
					:label='$t("config.daemon.misc.jsonSplitter.form.validateJsonResponse").toString()'
				/>
				<CButton
					type='submit'
					color='primary'
					:disabled='invalid'
				>
					{{ $t('forms.save') }}
				</CButton>
			</CForm>
		</ValidationObserver>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';

import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';

import {Dictionary} from 'vue-router/types/router';
import {AxiosError, AxiosResponse} from 'axios';
import {IJsonMetaData, IJsonRaw, IJsonSplitter} from '../../interfaces/jsonApi';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider,
	}
})

/**
 * JSON API config component for normal user
 */
export default class JsonApi extends Vue {
	/**
	 * @constant {Dictionary<string>} componentNames Array of JSON component names
	 */
	private componentNames: Dictionary<string> = {
		metaData: 'iqrf::JsonMngMetaDataApi',
		rawApi: 'iqrf::JsonDpaApiRaw',
		splitter: 'iqrf::JsonSplitter'
	}

	/**
	 * @var {string} insId JSON splitter instance ID
	 */
	private insId = ''

	/**
	 * @var {IJsonMetaData|null} metaData JSON metadata configuration object
	 */
	private metaData: IJsonMetaData|null = null

	/**
	 * @var {IJsonRaw|null} rawApi JSON raw api configuration object
	 */
	private rawApi: IJsonRaw|null = null

	/**
	 * @var {IJsonSplitter|null} splitter JSON splitter configuration object
	 */
	private splitter: IJsonSplitter|null = null

	/**
	 * @var {boolean} metaDataToMessages Should metadata be added to messages?
	 */
	private metaDataToMessages = false

	/**
	 * @var {boolean} asyncDpaMessage Asynchronous DPA messages?
	 */
	private asyncDpaMessage = false

	/**
	 * @var {boolean} validateJsonResponse Should JSON responses be validated?
	 */
	private validateJsonResponse = false

	/**
	 * Initializes validation rules
	 */
	created(): void {
		extend('required', required);
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		this.getConfig();
	}

	/**
	 * Retrieves configuration of JSON API components
	 */
	private getConfig(): Promise<void> {
		return Promise.all([
			DaemonConfigurationService.getComponent(this.componentNames.metaData),
			DaemonConfigurationService.getComponent(this.componentNames.rawApi),
			DaemonConfigurationService.getComponent(this.componentNames.splitter),
		])
			.then((responses: Array<AxiosResponse>) => {
				this.metaData = responses[0].data.instances[0];
				this.metaDataToMessages = responses[0].data.instances[0].metaDataToMessages;
				this.rawApi = responses[1].data.instances[0];
				this.asyncDpaMessage = responses[1].data.instances[0].asyncDpaMessage;
				this.splitter = responses[2].data.instances[0];
				this.insId = responses[2].data.instances[0].insId;
				this.validateJsonResponse = responses[2].data.instances[0].validateJsonResponse;
				this.$emit('fetched', 'jsonApi');
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Updates configuration of JSON API component instances
	 */
	private saveConfig(): void {
		let requests: Array<Promise<AxiosResponse>> = [];
		if (this.metaData !== null) {
			if (this.metaDataToMessages !== this.metaData.metaDataToMessages) {
				this.metaData.metaDataToMessages = this.metaDataToMessages;
				requests.push(DaemonConfigurationService.updateInstance(this.componentNames.metaData, this.metaData.instance, this.metaData));
			}
		}
		if (this.rawApi !== null) {
			if (this.asyncDpaMessage !== this.rawApi.asyncDpaMessage) {
				this.rawApi.asyncDpaMessage = this.asyncDpaMessage;
				requests.push(DaemonConfigurationService.updateInstance(this.componentNames.rawApi, this.rawApi.instance, this.rawApi));
			}
		}
		if (this.splitter !== null) {
			if (this.validateJsonResponse !== this.splitter.validateJsonResponse ||
				this.insId !== this.splitter.insId) {
				this.splitter.insId = this.insId;
				this.splitter.validateJsonResponse = this.validateJsonResponse;
				requests.push(DaemonConfigurationService.updateInstance(this.componentNames.splitter, this.splitter.instance, this.splitter));
			}
		}
		if (requests.length === 0) {
			this.$toast.info(
				this.$t('config.daemon.misc.jsonApi.messages.noChanges').toString()
			);
			return;
		}
		this.$store.commit('spinner/SHOW');
		Promise.all(requests)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.daemon.misc.jsonApi.messages.success').toString()
					);
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}
}
</script>
