<template>
	<div>
		<h1>{{ $t('config.main.title') }}</h1>
		<CCard>
			<CCardBody>
				<CForm @submit.prevent='saveConfig'>
					<CInput
						v-model='configuration.applicationName'
						:label='$t("config.main.form.applicationName")'
					/>
					<CInput
						v-model='configuration.resourceDir'
						:label='$t("config.main.form.resourceDir")'
					/>
					<CInput
						v-model='configuration.dataDir'
						:label='$t("config.main.form.dataDir")'
					/>
					<CInput
						v-model='configuration.cacheDir'
						:label='$t("config.main.form.cacheDir")'
					/>
					<CInput
						v-model='configuration.userDir'
						:label='$t("config.main.form.userDir")'
					/>
					<CInput
						v-model='configuration.configurationDir'
						:label='$t("config.main.form.configurationDir")'
					/>
					<CInput
						v-model='configuration.deploymentDir'
						:label='$t("config.main.form.deploymentDir")'
					/>
					<CButton type='submit' color='primary'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CCardBody, CForm, CInput} from '@coreui/vue/src';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';

interface IMainConfig {
	applicationName: string|null
	resourceDir: string|null
	cacheDir: string|null
	configurationDir: string|null
	dataDir: string|null
	deploymentDir: string|null
	userDir: string|null
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
	},
	metaInfo: {
		title: 'config.main.title',
	}
})

export default class MainConfiguration extends Vue {
	private configuration: IMainConfig = {
		applicationName: null,
		resourceDir: null,
		cacheDir: null,
		configurationDir: null,
		dataDir: null,
		deploymentDir: null,
		userDir: null
	}
	
	created(): void {
		this.getConfig();
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent('')
			.then((response: AxiosResponse) =>  {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data;
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	private saveConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.updateComponent('', this.configuration)
			.then(() => this.successfulSave())
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		this.$toast.success(this.$t('config.success').toString());
	}
}
</script>
