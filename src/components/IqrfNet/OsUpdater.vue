<template>
	<div>
		<CCard>
			<CCardHeader>
				{{ $t('iqrfnet.trUpload.osUpload.title') }}
			</CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{invalid}'>
					<CForm @submit.prevent='upgradeOs'>
						<p>
							<span v-if='currentOsVersion !== "" && currentOsBuild !== ""'>
								<b>{{ $t('iqrfnet.trUpload.osUpload.form.current') }}</b> {{ prettyVersion(currentOsVersion) + ' (' + currentOsBuild + ')' }}
							</span>
							<CAlert
								v-else
								color='danger'
							>
								{{ $t('iqrfnet.trUpload.osUpload.messages.fetchFail') }}
							</CAlert>
						</p>
						<div v-if='selectVersions.length > 0'>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{
									required: "iqrfnet.trUpload.osUpload.errors.version"
								}'
							>
								<CSelect
									:value.sync='osVersion'
									:label='$t("iqrfnet.trUpload.osUpload.form.version")'
									:placeholder='$t("iqrfnet.trUpload.osUpload.errors.version")'
									:options='selectVersions'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								color='primary'
								type='submit'
								:disabled='invalid'
							>
								{{ $t('forms.upload') }}
							</CButton>
						</div>
						<CAlert 
							v-if='selectVersions.length === 0 && currentOsVersion !== "" && currentOsBuild !== ""'
							color='success'
						>
							{{ $t('iqrfnet.trUpload.osUpload.messages.newest') }}
						</CAlert>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CAlert, CButton, CCard, CCardBody, CCardHeader, CForm, CModal, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import {IOption} from '../../interfaces/coreui';
import IqrfService from '../../services/IqrfService';
import {AxiosError, AxiosResponse} from 'axios';
import {IqrfOsUpgrade, UploadUtilFile, IqrfOsUpgradeFiles} from '../../interfaces/trUpload';
import {Dictionary} from 'vue-router/types/router';
import FormErrorHandler from '../../helpers/FormErrorHandler';

@Component({
	components: {
		CAlert,
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CModal,
		CSelect,
		ValidationObserver,
		ValidationProvider
	}
})

/**
 * IQRF OS update component for Coordinator Upload
 */
export default class OsUpdater extends Vue {
	/**
	 * @var {string} currentOsBuild Currently uploaded OS build
	 */
	private currentOsBuild = ''

	/**
	 * @var {string} currentOsVersion Currently uploaded OS version
	 */
	private currentOsVersion = ''

	/**
	 * @var {string} interfaceType Used IQRF interface
	 */
	private interfaceType = ''

	/**
	 * @var {number} osVersion Selected version of IQRF OS
	 */
	private osVersion: number|null = null

	/**
	 * @var {Array<IOption>} selectVersions Array of available IQRF OS versions for CoreUI select
	 */
	private selectVersions: Array<IOption> = []

	/**
	 * @var {number} trMcuType Module TR and MCU type
	 */
	private trMcuType = 0

	/**
	 * @var {Array<IqrfOsUpgrade>} upgrades Array of possible IQRF OS upgrades
	 */
	private upgrades: Array<IqrfOsUpgrade> = []
	
	/**
	 * Vue lifecycle hook created
	 * Initializes validation rules
	 */
	created(): void {
		extend('required', required);
	}

	/**
	 * Handles OS Info response
	 */
	public handleOsInfoResponse(response: any): void {
		this.currentOsBuild = ('0000' + response.osBuild.toString(16)).slice(-4).toUpperCase();
		const osVersion = response.osVersion.toString(16);
		this.currentOsVersion = osVersion.charAt(0) + '0' + osVersion.charAt(1);
		this.trMcuType = response.trMcuType;
		const flags = ('00000000' + response.flags.toString(2)).slice(-8);
		if (flags[3] === '0') {
			this.interfaceType = flags[6] === '0' ? 'SPI' : 'UART';
		}
		const data = {
			build: this.currentOsBuild,
			version: this.currentOsVersion,
			mcuType: parseInt(response.trMcuType.toString(2).slice(-3), 2),
		};
		this.getOsUpgrades(data);
	}

	/**
	 * Retrieves list of IQRF OS patches from database
	 */
	private getOsUpgrades(data: Dictionary<string|number>): void {
		this.$store.commit('spinner/SHOW');
		IqrfService.getUpgrades(data)
			.then((response: AxiosResponse) => {
				this.upgrades = response.data;
				this.updateVersions();
			});
	}

	/**
	 * Updates list of available IQRF OS upgrades for select
	 */
	private updateVersions(): void {
		let versions: Array<IOption> = [];
		for (let i = 0; i < this.upgrades.length; ++i) {
			let upgrade: IqrfOsUpgrade = this.upgrades[i];
			let label = this.prettyVersion(upgrade.osVersion) + ' (' + upgrade.osBuild + ')' + ', DPA ' + upgrade.dpa;
			versions.push({
				value: i,
				label: label,
			});
		}
		this.selectVersions = versions.sort().reverse();
	}

	/**
	 * Converts OS version string to string with major and minor version
	 * @param {string} version OS version string
	 * @returns {string} Updated version string
	 */
	private prettyVersion(version: string): string {
		return version.charAt(0) + '.' + version.substring(1, version.length);
	}

	/**
	 * Creates REST API request body to fetch files needed to upgrade IQRF OS,
	 * then executes upgrade using the IQRF Upload Utility via REST API.
	 */
	private upgradeOs(): void {
		if (this.osVersion === null) {
			return;
		}
		let upgrade = this.upgrades[this.osVersion];
		let data = {
			fromBuild: this.currentOsBuild,
			fromVersion: this.currentOsVersion,
			toVersion: upgrade.osVersion,
			toBuild: upgrade.osBuild,
			dpa: upgrade.dpaRaw,
			interface: this.interfaceType,
			trMcuType: this.trMcuType,
		};
		if (upgrade.dpaRaw < '0400') {
			Object.assign(data, {rfMode: upgrade.dpa.endsWith('STD') ? 'STD' : 'LP'});
		}
		this.$store.commit('spinner/SHOW');
		IqrfService.getUpgradeFiles(data)
			.then((response: AxiosResponse) => {
				this.uploadFiles(response.data);
			})
			.catch((error: AxiosError) => FormErrorHandler.fileFetchError(error));
	}

	/**
	 * Creates REST API request body to upgrade IQRF OS and executes upgrade.
	 * @param {IqrfOsUpgradeFiles} responseFiles Files needed to upgrade IQRF OS
	 */
	private uploadFiles(responseFiles: IqrfOsUpgradeFiles): void {
		let files: Array<UploadUtilFile> = [];
		for (let file of responseFiles.os) {
			files.push({name: file, type: 'OS'});
		}
		files.push({name: responseFiles.dpa, type: 'DPA'});
		IqrfService.utilUpload(files)
			.then(() => {
				this.$store.commit('spinner/HIDE');
				this.$toast.success(
					this.$t('iqrfnet.trUpload.osUpload.messages.upgradeSuccess').toString()
				);
			})
			.catch((error: AxiosError) => FormErrorHandler.uploadUtilError(error));
	}
}
</script>
