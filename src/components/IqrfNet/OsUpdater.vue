<template>
	<div>
		<CCard>
			<CCardHeader>
				{{ $t('iqrfnet.trUpload.osUpload.title') }}
			</CCardHeader>
			<CCardBody>
				<ValidationObserver v-slot='{invalid}'>
					<CForm @submit.prevent='uploadOs'>
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
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CModal, CSelect} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {required} from 'vee-validate/dist/rules';
import {IOption} from '../../interfaces/coreui';

@Component({
	components: {
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
	 * @var {string} osVersion Currently selected version of IQRF OS
	 */
	private osVersion = ''

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

	public handleOsInfoResponse(response): void {
		console.error(response);
	}
}
</script>
