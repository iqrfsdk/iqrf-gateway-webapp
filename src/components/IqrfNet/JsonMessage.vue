<template>
	<CCard>
		<CCardHeader class='d-flex'>
			<span class='mr-auto'>
				{{ $t('iqrfnet.jsonMessage.' + type) }}
			</span>
			<CButton
				v-clipboard='message'
				v-clipboard:success='successMessage'
				color='primary'
				size='sm'
			>
				{{ $t('iqrfnet.jsonMessage.copy.' + type) }}
			</CButton>
		</CCardHeader>
		<CCardBody>
			<prism-editor
				v-model='message'
				:highlight='highlighter'
				:readonly='true'
			/>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader} from '@coreui/vue/src';
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
		PrismEditor
	}
})

/**
 * JSON message card
 */
export default class JsonMessage extends Vue {

	/**
	 * @property {string|null} message Message
	 */
	@Prop({required: true, default: null}) message!: string|null

	/**
	 * @property {string} type Message type
	 */
	@Prop({required: true}) type!: string

	/**
	 * @property {string} source Message source
	 */
	@Prop({required: true}) source!: string

	/**
	 * Shows the success message
	 */
	private successMessage(): void {
		let message = '';
		if (this.source === 'sendDpa') {
			message = this.$t('iqrfnet.jsonMessage.copySuccess.dpa.' + this.type).toString();
		} else {
			message = this.$t('iqrfnet.jsonMessage.copySuccess.json.' + this.type).toString();
		}
		this.$toast.success(message);
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
