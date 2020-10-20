<template>
	<div>
		<h1 v-if='$route.path === "/config/tracer/add"'>
			{{ $t('config.tracer.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.tracer.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveInstance'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.tracer.form.messages.instance"}'
						>
							<CInput
								v-model='configuration.instance'
								:label='$t("config.tracer.form.instance")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInput
							v-model='configuration.path'
							:label='$t("config.tracer.form.path")'
						/>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='required'
							:custom-messages='{required: "config.tracer.form.messages.filename"}'
						>
							<CInput
								v-model='configuration.filename'
								:label='$t("config.tracer.form.filename")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required|min:1'
							:custom-messages='{
								required: "config.tracer.form.messages.maxSizeMb",
								min: "config.tracer.form.messages.maxSizeMb",
								integer: "forms.messages.integer"
							}'
						>
							<CInput
								v-model.number='configuration.maxSizeMB'
								type='number'
								:label='$t("config.tracer.form.maxSizeMb")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CInputCheckbox
							:checked.sync='configuration.timestampFiles'
							:label='$t("config.tracer.form.timestampFiles")'
						/>
						<h4>{{ $t("config.tracer.form.verbosityLevels.title") }}</h4>
						<div
							v-for='(level, i) of configuration.VerbosityLevels'
							:key='i'
							class='form-group'
						>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='integer|required'
								:custom-messages='{
									integer: "forms.messages.integer",
									required: "config.tracer.form.messages.verbosityLevels.channel"
								}'
							>
								<CInput
									v-model.number='level.channel'
									type='number'
									:label='$t("config.tracer.form.channel")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required'
								:custom-messages='{required: "config.tracer.form.messages.verbosityLevels.level"}'
							>
								<CSelect
									:value.sync='level.level'
									:label='$t("config.tracer.form.level")'
									:placeholder='$t("config.tracer.form.messages.verbosityLevels.level")'
									:options='selectOptions'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								v-if='configuration.VerbosityLevels.length > 1'
								color='danger'
								@click='removeLevel(i)'
							>
								{{ $t('config.tracer.form.verbosityLevels.remove') }}
							</CButton>
							<CButton
								v-if='i === (configuration.VerbosityLevels.length - 1)'
								color='success'
								:disabled='level.channel === undefined || level.level === undefined'
								@click='addLevel'
							>
								{{ $t('config.tracer.form.verbosityLevels.add') }}
							</CButton>
						</div>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {integer, min_value, required} from 'vee-validate/dist/rules';
import { MetaInfo } from 'vue-meta';
import { IOption } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';

interface VerbosityLevel {
	channel: number
	level: string
}

interface TracerConfiguration {
	component: string
	filename: string
	instance: string
	maxSizeMB: number
	path: string
	timestampFiles: boolean
	VerbosityLevels: Array<VerbosityLevel>
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as TracerForm).pageTitle
		};
	}
})

export default class TracerForm extends Vue {
	private configuration: TracerConfiguration = {
		component: '',
		instance: '',
		path: '',
		filename: '',
		maxSizeMB: 1,
		timestampFiles: false,
		VerbosityLevels: [{channel: 0, level: 'INF'}],
	}
	private componentName = 'shape::TraceFileService'
	private selectOptions: Array<IOption> = [
		{value: 'ERR', label: this.$t('config.tracer.form.levels.error')},
		{value: 'WAR', label: this.$t('config.tracer.form.levels.warning')},
		{value: 'INF', label: this.$t('config.tracer.form.levels.info')},
		{value: 'DBG', label: this.$t('config.tracer.form.levels.debug')}
	]

	@Prop({required: false, default: null}) instance!: string

	get pageTitle(): string {
		return this.$route.path === '/config/tracer/add' ?
			this.$t('config.tracer.add').toString() : this.$t('config.tracer.edit').toString();
	}

	get submitButton(): string {
		return this.$route.path === '/config/tracer/add' ?
			this.$t('forms.add').toString() : this.$t('forms.edit').toString();
	}

	mounted(): void {
		extend('integer', integer);
		extend('min', min_value);
		extend('required', required);
		if (this.instance) {
			this.getInstance();
		}
	}

	private addLevel(): void {
		this.configuration.VerbosityLevels.push({channel: 0, level: 'INF'});
	}

	private removeLevel(index: number): void {
		this.configuration.VerbosityLevels.splice(index, 1);
	}

	private getInstance(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getInstance(this.componentName, this.instance)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.configuration = response.data;
			})
			.catch((error: AxiosError) => {
				this.$router.push('/config/daemon/');
				FormErrorHandler.configError(error);
			});
	}

	private saveInstance(): void {
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

	private successfulSave(): void {
		this.$router.push('/config/daemon/');
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/tracer/add') {
			this.$toast.success(
				this.$t('config.tracer.messages.addSuccess', {instance: this.configuration.instance})
					.toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.tracer.messages.editSuccess', {instance: this.instance})
					.toString()
			);
		}
	}

}
</script>
