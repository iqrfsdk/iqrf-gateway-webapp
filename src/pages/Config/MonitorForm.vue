<template>
	<div>
		<h1 v-if='$route.path === "/config/monitor/add"'>
			{{ $t('config.monitor.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.monitor.edit') }}
		</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='saveConfig'>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mq.form.messages.instance"}'
					>
						<CInput
							v-model='monitor.instance'
							:label='$t("config.monitor.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required'
						:custom-messages='{
							required: "config.monitor.form.messages.reportPeriod",
							integer: "forms.messages.integer"
						}'
					>
						<CInput
							v-model.number='monitor.reportPeriod'
							type='number'
							:label='$t("config.monitor.form.reportPeriod")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='integer|required'
						:custom-messages='{
							required: "config.monitor.form.messages.WebsocketPort",
							integer: "forms.messages.integer"
						}'
					>
						<CInput
							v-model.number='webSocket.WebsocketPort'
							type='number'
							:label='$t("config.monitor.form.WebsocketPort")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='webSocket.acceptOnlyLocalhost'
						:label='$t("config.monitor.form.acceptOnlyLocalhost")'
					/>
					<CButton type='submit' color='primary' :disabled='invalid'>
						{{ submitButton }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {integer, required} from 'vee-validate/dist/rules';
import { MetaInfo } from 'vue-meta';
import { RequiredInterface } from '../../interfaces/requiredInterfaces';

interface MonitorComponents {
	monitor: string
	webSocket: string
}

interface MonitorInstance {
	component: string
	instance: string
	reportPeriod: number
	RequiredInterfaces: Array<RequiredInterface>
}

interface MonitorWebSocket {
	instance: string
	WebsocketPort: number
	acceptOnlyLocalhost: boolean
}

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		CInputCheckbox,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as MonitorForm).pageTitle
		};
	}
})

export default class MonitorForm extends Vue {
	private componentNames: MonitorComponents = {
		monitor: 'iqrf::MonitorService',
		webSocket: 'shape::WebsocketCppService',
	}
	private instances: MonitorComponents = {
		monitor: '',
		webSocket: '',
	}
	private monitor: MonitorInstance = {
		component: '',
		instance: '',
		reportPeriod: 10,
		RequiredInterfaces: []
	}
	private webSocket: MonitorWebSocket = {
		instance: '',
		WebsocketPort: 1438,
		acceptOnlyLocalhost: false,
	}
	@Prop({required: false, default: null}) instance!: string


	get pageTitle(): string {
		return this.$route.path === '/config/monitor/add' ?
			this.$t('config.monitor.add').toString() :
			this.$t('config.monitor.edit').toString();
	}
	
	get submitButton(): string {
		return this.$route.path === '/config/mq/add' ?
			this.$t('forms.add').toString() :
			this.$t('forms.save').toString();
	}

	created(): void {
		extend('integer', integer);
		extend('required', required);
		if (this.instance !== null) {
			this.getConfig();
		}
	}

	private getConfig(): void {
		if (this.componentNames.monitor === null || this.componentNames.webSocket === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentNames.monitor, this.instance)
			.then((response) => {
				this.monitor = response.data;
				this.instances.monitor = this.instance;
				this.instances.webSocket = this.monitor.RequiredInterfaces[0].target.instance;
				DaemonConfigurationService.getInstance(this.componentNames.webSocket, this.instances.webSocket)
					.then((response) => {
						this.webSocket = response.data;
						this.$store.commit('spinner/HIDE');
					});
			})
			.catch((error) => {
				this.$router.push('/config/monitor/');
				FormErrorHandler.configError(error);
			});
	}

	private saveConfig(): void {
		this.$store.commit('spinner/SHOW');
		this.webSocket.instance = this.monitor.instance;
		if (this.monitor.RequiredInterfaces.length === 0) {
			this.monitor.RequiredInterfaces[0] = {
				name: 'shape::IWebsocketService',
				target: {
					instance: this.webSocket.instance,
				},
			};
		} else {
			this.monitor.RequiredInterfaces[0].target.instance = this.monitor.instance;
		}
		if (this.instance === null) {
			Promise.all([
				DaemonConfigurationService.createInstance(this.componentNames.webSocket, this.webSocket),
				DaemonConfigurationService.createInstance(this.componentNames.monitor, this.monitor),
			])
				.then(() => this.successfulSave())
				.catch((error) => FormErrorHandler.configError(error));
		} else {
			Promise.all([
				DaemonConfigurationService.updateInstance(this.componentNames.webSocket, this.instances.webSocket, this.webSocket),
				DaemonConfigurationService.updateInstance(this.componentNames.monitor, this.instances.monitor, this.monitor),
			])
				.then(() => this.successfulSave())
				.catch((error) => FormErrorHandler.configError(error));
		}
	}

	private successfulSave() {
		this.$router.push('/config/monitor/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/monitor/add') {
			this.$toast.success(
				this.$t('config.monitor.messages.add.success', {instance: this.monitor.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.monitor.messages.edit.success', {instance: this.monitor.instance})
					.toString()
			);
		}
	}
}
</script>
