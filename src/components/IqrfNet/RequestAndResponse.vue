<template>
	<div>
		<CRow>
			<CCol v-if='request !== null' md='6'>
				<CCard>
					<CCardHeader class='d-flex'>
						<span class='mr-auto'>
							{{ $t('iqrfnet.requestAndResponse.request') }}
						</span>
						<CButton
							v-clipboard='request'
							v-clipboard:success='() => $toast.success(requestClipboard)'
							color='primary'
							size='sm'
						>
							{{ $t('iqrfnet.requestAndResponse.copyRequest') }}
						</CButton>
					</CCardHeader>
					<CCardBody>
						<prism-editor
							v-model='request'
							:highlight='highlighter'
							:readonly='true'
						/>
					</CCardBody>
				</CCard>
			</CCol>
			<CCol v-if='response !== null' md='6'>
				<CCard>
					<CCardHeader class='d-flex'>
						<span class='mr-auto'>
							{{ $t('iqrfnet.requestAndResponse.response') }}
						</span>
						<CButton
							v-clipboard='response'
							v-clipboard:success='() => $toast.success(responseClipboard)'
							color='primary'
							size='sm'
						>
							{{ $t('iqrfnet.requestAndResponse.copyResponse') }}
						</CButton>
					</CCardHeader>
					<CCardBody>
						<prism-editor
							v-model='response'
							:highlight='highlighter'
							:readonly='true'
						/>
					</CCardBody>
				</CCard>
			</CCol>
		</CRow>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CCol, CRow} from '@coreui/vue/src';
import {PrismEditor} from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css';
import Prism from 'prismjs/components/prism-core';
import 'prismjs/components/prism-json';
import 'prismjs/themes/prism.css';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CCol,
		CRow,
		PrismEditor
	}
})

export default class RequestAndResponse extends Vue {
	/**
	 * @property {string|null} request request message
	 */
	@Prop({required: true, default: null}) request!: string|null

	/**
	 * @property {string|null} response response message
	 */
	@Prop({required: true, default: null}) response!: string|null

	/**
	 * @property {string} source messages source
	 */
	@Prop({required: true}) source!: string

	/**
	 * Computes clipboard request copy message
	 */
	get requestClipboard(): string {
		return this.source === 'sendDpa' ?
			this.$t('iqrfnet.requestAndResponse.dpaRequestCopied').toString():
			this.$t('iqrfnet.requestAndResponse.jsonRequestCopied').toString();
	}

	/**
	 * Computes clipboard response copy message
	 */
	get responseClipboard(): string {
		return this.source === 'sendDpa' ?
			this.$t('iqrfnet.requestAndResponse.dpaResponseCopied').toString():
			this.$t('iqrfnet.requestAndResponse.jsonResponseCopied').toString();
	}

	/**
	 * JSON highlighter method
	 * @param {string} code text to highlight
	 */
	private highlighter(code: string) {
		return Prism.highlight(code, Prism.languages.json, 'json');
	}
}
</script>
