<template>
	<div>
		<h1 v-if='$route.path === "/config/component/add"'>
			{{ $t('config.components.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.components.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveComponent'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.components.form.messages.name"}'
						>
							<CInput
								v-model='configuration.name'
								:label='$t("config.components.form.name")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInput
							v-model='configuration.libraryPath'
							:label='$t("config.components.form.libraryPath")'
						/>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.components.form.messages.libraryName"}'
						>
							<CInput
								v-model='configuration.libraryName'
								:label='$t("config.components.form.libraryName")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.enabled'
							:label='$t("config.components.form.enabled")'
						/>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required'
							:custom-messages='{
								integer: "forms.messages.integer",
								required: "config.components.form.messages.startLevel"
							}'
						>
							<CInput
								v-model.number='configuration.startlevel'
								type='number'
								:label='$t("config.components.form.startLevel")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ submitButton }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import { MetaInfo } from 'vue-meta/types/vue-meta';

interface ComponentFormConfig {
	name: string|null
	libraryPath: string|null
	libraryName: string|null
	enabled: boolean
	startlevel: number|null
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
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as ComponentForm).pageTitle
		};
	}
})

export default class ComponentForm extends Vue {
	private configuration: ComponentFormConfig = {
		name: null,
		libraryPath: null,
		libraryName: null,
		enabled: false,
		startlevel: null
	}
	
	@Prop({ required: false,default: null })
	component!: string;
	
	get pageTitle(): string {
		return this.$route.path === '/config/component/add' ?
			this.$t('config.components.add').toString() : this.$t('config.components.edit').toString();
	}

	get submitButton(): string {
		return this.$route.path === '/config/component/add' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
	}

	created(): void {
		extend('integer', integer);
		extend('required', required);
		if (this.component !== null) {
			this.getComponent();
		}
	}

	private getComponent(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent(this.component)
			.then((response) => {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data.configuration;
			})
			.catch((error) => {
				this.$router.push('/config/component/');
				FormErrorHandler.configError(error);
			});
	}

	private saveComponent(): void {
		this.$store.commit('spinner/SHOW');
		if (this.component !== null) {
			DaemonConfigurationService.updateComponent(this.component, this.configuration)
				.then(() => this.successfulSave())
				.catch((error) => FormErrorHandler.configError(error));
		} else {
			DaemonConfigurationService.createComponent(this.configuration)
				.then(() => this.successfulSave())
				.catch((error) => FormErrorHandler.configError(error));
		}
	}

	private successfulSave(): void {
		this.$router.push('/config/component');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/component/add') {
			this.$toast.success(
				this.$t('config.components.form.messages.addSuccess', {component: this.configuration.name})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.components.form.messages.editSuccess', {component: this.component})
					.toString()
			);
		}
	}
}
</script>
