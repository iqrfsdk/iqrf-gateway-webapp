<template>
	<CCard>
		<CCardHeader><h3>{{ $t('config.jsonApi.title') }}</h3></CCardHeader>
		<CCardBody>
			<CForm @submit.prevent='saveConfig'>
				<CInputCheckbox
					:checked.sync='metaDataToMessages'
					:label='$t("config.jsonMngMetaDataApi.form.metaDataToMessages").toString()'
				/>
				<CInputCheckbox
					:checked.sync='asyncDpaMessage'
					:label='$t("config.jsonRawApi.form.asyncDpaMessage").toString()'
				/>
				<CInputCheckbox
					:checked.sync='validateJsonResponse'
					:label='$t("config.jsonSplitter.form.validateJsonResponse").toString()'
				/>
				<CButton
					type='submit'
					color='primary'
				>
					{{ $t('forms.save') }}
				</CButton>
			</CForm>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CInputCheckbox} from '@coreui/vue/src';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import { Dictionary } from 'vue-router/types/router';
import { AxiosError, AxiosResponse } from 'axios';
import FormErrorHandler from '../../helpers/FormErrorHandler';

interface JsonMngMetaDataApiConfig {
	instance: string
	metaDataToMessages: boolean
}

interface JsonRawApiConfig {
	instance: string
	asyncDpaMessage: boolean
}

interface JsonSplitterConfig {
	instance: string
	validateJsonResponse: boolean
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CInputCheckbox
	}
})

export default class JsonApi extends Vue {
	private componentNames: Dictionary<string> = {
		metaData: 'iqrf::JsonMngMetaDataApi',
		rawApi: 'iqrf::JsonDpaApiRaw',
		splitter: 'iqrf::JsonSplitter'
	}
	private metaData: JsonMngMetaDataApiConfig|null = null
	private rawApi: JsonRawApiConfig|null = null
	private splitter: JsonSplitterConfig|null = null
	private metaDataToMessages = false
	private asyncDpaMessage = false
	private validateJsonResponse = false

	mounted(): void {
		this.getConfig();
	}

	private getConfig() {
		this.$store.commit('spinner/SHOW');
		return Promise.all([
			DaemonConfigurationService.getComponent(this.componentNames.metaData),
			DaemonConfigurationService.getComponent(this.componentNames.rawApi),
			DaemonConfigurationService.getComponent(this.componentNames.splitter),
		])
			.then((responses: Array<AxiosResponse>) => {
				this.$store.commit('spinner/HIDE');
				this.metaData = responses[0].data.instances[0];
				this.metaDataToMessages = responses[0].data.instances[0].metaDataToMessages;
				this.rawApi = responses[1].data.instances[0];
				this.asyncDpaMessage = responses[1].data.instances[0].asyncDpaMessage;
				this.splitter = responses[2].data.instances[0];
				this.validateJsonResponse = responses[2].data.instances[0].validateJsonResponse;
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

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
			if (this.validateJsonResponse !== this.splitter.validateJsonResponse) {
				this.splitter.validateJsonResponse = this.validateJsonResponse;
				requests.push(DaemonConfigurationService.updateInstance(this.componentNames.splitter, this.splitter.instance, this.splitter));
			}
		}
		if (requests.length === 0) {
			this.$toast.info(
				this.$t('config.jsonApi.messages.noChanges').toString()
			);
			return;
		}
		this.$store.commit('spinner/SHOW');
		Promise.all(requests)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.jsonApi.messages.success').toString()
					);
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}
}
</script>
