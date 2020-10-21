<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/mq/add"'>
			{{ $t('config.mq.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.mq.edit') }}
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
							v-model='configuration.instance'
							:label='$t("config.mq.form.instance")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mq.form.messages.LocalMqName"}'
					>
						<CInput
							v-model='configuration.LocalMqName'
							:label='$t("config.mq.form.LocalMqName")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ errors, touched, valid }'
						rules='required'
						:custom-messages='{required: "config.mq.form.messages.RemoteMqName"}'
					>
						<CInput
							v-model='configuration.RemoteMqName'
							:label='$t("config.mq.form.RemoteMqName")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<CInputCheckbox
						:checked.sync='configuration.acceptAsyncMsg'
						:label='$t("config.mq.form.acceptAsyncMsg")'
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
import {required} from 'vee-validate/dist/rules';
import { MqInstance } from '../../interfaces/messagingInterfaces';
import { MetaInfo } from 'vue-meta';
import {AxiosError, AxiosResponse} from 'axios';

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
			title: (this as unknown as MqMessagingForm).pageTitle
		};
	},
})

export default class MqMessagingForm extends Vue {
	private componentName = 'iqrf::MqMessaging'
	private configuration: MqInstance = {
		component: '',
		instance: '',
		LocalMqName: '',
		RemoteMqName: '',
		acceptAsyncMsg: false,
	}

	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/daemon/mq/add' ?
			this.$t('config.mq.add').toString() : this.$t('config.mq.edit').toString();
	}
	
	get submitButton(): string {
		return this.$route.path === '/config/daemon/mq/add' ?
			this.$t('forms.add').toString() : this.$t('forms.save').toString();
	}

	mounted(): void {
		extend('required', required);
		if (this.instance !== null) {
			this.getConfig();
		}
	}

	private getConfig(): void  {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentName, this.instance)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data;
			})
			.catch((error: AxiosError) => {
				this.$router.push('/config/daemon/messagings/');
				FormErrorHandler.configError(error);
			});
	}

	private saveConfig(): void  {
		this.$store.commit('spinner/SHOW');
		if (this.instance !== null) {
			DaemonConfigurationService.updateInstance(this.componentName, this.instance, this.configuration)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createInstance(this.componentName, this.configuration)
				.then(() => this.successfulSave())
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}

	private successfulSave(): void  {
		this.$router.push('/config/daemon/messagings');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/mq/add') {
			this.$toast.success(
				this.$t('config.mq.messages.add.success', {instance: this.configuration.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.mq.messages.edit.success', {instance: this.configuration.instance})
					.toString()
			);
		}
	}
}
</script>
