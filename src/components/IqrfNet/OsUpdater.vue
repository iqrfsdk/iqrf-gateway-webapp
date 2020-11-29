<template>
	<div>
		<CCard>
			<CCardHeader>
				{{ $t('iqrfnet.trUpload.osUpload.title') }}
			</CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{invalid}'>
					<CForm @submit.prevent='uploadOs'>
						<p>
							<span v-if='currentOsVersion !== "" && currentOsBuild !== 0'>
								<b>{{ $t('iqrfnet.trUpload.osUpload.form.current') }}</b> {{ currentOsVersion + ' (' + currentOsBuild + ')' }}
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
								{{ $t('forms.update') }}
							</CButton>
						</div>
						<CAlert 
							v-if='selectVersions.length === 0 && patches.length !== 0'
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
import {AxiosResponse} from 'axios';
import {IqrfOsPatch} from '../../interfaces/iqrfOs';

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
	 * @var {number} currentOsBuild Currently uploaded OS build
	 */
	private currentOsBuild = 0

	/**
	 * @var {string} currentOsVersion Currently uploaded OS version
	 */
	private currentOsVersion = ''

	/**
	 * @var {number} osVersion Selected version of IQRF OS
	 */
	private osVersion = 0

	/**
	 * @var {Array<IqrfOsPatch} patches Array of all patches in database
	 */
	private patches: Array<IqrfOsPatch> = []

	/**
	 * @var {Array<IOption>} selectVersions Array of available IQRF OS versions for CoreUI select
	 */
	private selectVersions: Array<IOption> = []
	
	/**
	 * Vue lifecycle hook created
	 * Initializes validation rules
	 */
	created(): void {
		extend('required', required);
	}

	/**
	 * Handles 
	 */
	public handleOsInfoResponse(response: any): void {
		this.currentOsBuild = response.osBuild;
		const osVersion = response.osVersion.toString(16);
		this.currentOsVersion = osVersion.charAt(0) + '.0' + osVersion.charAt(1);
		this.getOsPatches();
	}

	/**
	 * Retrieves list of IQRF OS patches from database
	 */
	private getOsPatches(): void {
		this.$store.commit('spinner/SHOW');
		IqrfService.getPatches()
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.patches = response.data;
				this.updateVersions();
			});
	}

	/**
	 * Updates list of available IQRF OS versions for update, only newer versions are included
	 */
	private updateVersions(): void {
		let filteredVersions: Array<IOption> = [];
		for (const patch of this.patches) {
			let version = this.prettyVersion(patch.fromOsVersion.toString());
			if (version === this.currentOsVersion && patch.fromOsBuild == this.currentOsBuild 
				&& this.currentOsBuild < patch.toOsBuild && patch.partNumber === 1) {
				filteredVersions.push({
					value: patch.id,
					label: this.prettyVersion(patch.toOsVersion.toString()) + ' (' + patch.toOsBuild.toString() + ')'
				});
			}	
		}
		this.selectVersions = filteredVersions.sort().reverse();
	}

	/**
	 * Converts OS version string to string with major and minor version
	 * @param {string} version OS version string
	 * @returns {string} Updated version string
	 */
	private prettyVersion(version: string): string {
		return version.charAt(0) + '.' + version.substring(1, version.length);
	}

	private uploadOs(): void {
		this.currentOsVersion = this.prettyVersion(this.patches[this.osVersion - 1].toOsVersion.toString());
		this.updateVersions();
	}
}
</script>
