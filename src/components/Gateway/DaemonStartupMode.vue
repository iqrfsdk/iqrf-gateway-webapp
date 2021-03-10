<template>
	<CCard body-wrapper>
		<CSpinner
			v-if='loading'
			class='loading-spinner-centered'
		/>
		<CForm 
			v-else
			@submit.prevent='saveConfig'
		>
			<CSelect
				:value.sync='mode'
				:label='$t("gateway.mode.startupMode")'
				:options='modeOptions'
			/>
			<CButton
				type='submit'
				color='primary'
			>
				{{ $t('forms.save') }}
			</CButton>
		</CForm>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CForm, CSelect, CSpinner} from '@coreui/vue/src';

import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import {DaemonModeEnum} from '../../services/DaemonModeService';

import {IIdeCounterpart} from '../../interfaces/ideCounterpart';
import {IOption} from '../../interfaces/coreui';
import {AxiosError, AxiosResponse} from 'axios';
import FormErrorHandler from '../../helpers/FormErrorHandler';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CSelect,
		CSpinner,
	},
})

/**
 * Daemon start mode component
 */
export default class DaemonStartupMode extends Vue {

	/**
	 * @constant {string} component Component name
	 */
	private component = 'iqrf::IdeCounterpart'

	/**
	 * @var {IIdeCounterpart|null} configuration IDE counterpart component configuration
	 */
	private configuration: IIdeCounterpart|null = null

	/**
	 * @var {DaemonModeEnum} mode Daemon startup mode
	 */
	private mode: DaemonModeEnum = DaemonModeEnum.operational;

	/**
	 * @var {boolean} loading Indicates that fetch or save action is in progress
	 */
	private loading = true

	/**
	 * @constant {Array<IOption>} modeOptions Daemon mode CoreUI select options
	 */
	private modeOptions: Array<IOption> = [
		{
			label: this.$t('gateway.mode.modes.operational').toString(),
			value: DaemonModeEnum.operational,
		},
		{
			label: this.$t('gateway.mode.modes.service').toString(),
			value: DaemonModeEnum.service,
		},
		{
			label: this.$t('gateway.mode.modes.forwarding').toString(),
			value: DaemonModeEnum.forwarding,
		},
	]

	/**
	 * Retrieves configuration for starting Daemon mode
	 */
	mounted(): void {
		this.getConfig();
	}

	/**
	 * Retrieves IDE counterpart component configuration
	 */
	private getConfig(): Promise<void> {
		return DaemonConfigurationService.getComponent(this.component)
			.then((response: AxiosResponse) => {
				this.configuration = response.data.instances[0];
				if (this.configuration?.operMode !== undefined) {
					this.mode = this.configuration.operMode;
				}
				this.loading = false;
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Saves updated IDE countepart component configuration
	 */
	private saveConfig(): void {
		if (this.configuration === null) {
			return;
		}
		this.loading = true;
		let configuration = this.configuration;
		if (configuration.operMode !== undefined) {
			configuration.operMode = this.mode;
			console.warn('first');
		} else {
			console.warn('second');
			Object.assign(configuration, {operMode: this.mode});
		}
		DaemonConfigurationService.updateInstance(this.component, configuration.instance, configuration)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t(
							'gateway.mode.messages.startupModeSuccess',
							{mode: this.mode},
						).toString()
					);
				});
			})
			.catch(() => {
				this.$toast.error(
					this.$t('gateway.mode.messages.startupModeFailure').toString()
				);
			});
	}

}
</script>
