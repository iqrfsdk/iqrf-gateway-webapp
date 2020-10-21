<template>
	<CCard>
		<CCardHeader><h3>{{ $t('config.daemon.interfaces.title') }}</h3></CCardHeader>
		<CCardBody>
			<CSelect
				v-if='powerUser'
				color='primary'
				:value.sync='interfaceOption'
				:options='interfaceSelect'
				:label='$t("config.daemon.form.interface")'
			/>
			<IqrfSpi v-if='getInterface === "iqrf::IqrfSpi"' />
			<IqrfCdc v-if='getInterface === "iqrf::IqrfCdc"' />
			<IqrfUart v-if='getInterface === "iqrf::IqrfUart"' />
			<IqrfDpa />
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CCardBody, CCardHeader, CSelect} from '@coreui/vue/src';
import IqrfSpi from '../../pages/Config/IqrfSpi.vue';
import IqrfCdc from '../../pages/Config/IqrfCdc.vue';
import IqrfUart from '../../pages/Config/IqrfUart.vue';
import IqrfDpa from '../../pages/Config/IqrfDpa.vue';
import { IOption } from '../../interfaces/coreui';

@Component({
	components: {
		CCard,
		CCardBody,
		CCardHeader,
		CSelect,
		IqrfCdc,
		IqrfDpa,
		IqrfSpi,
		IqrfUart
	},
	metaInfo: {
		title: 'config.daemon.interfaces.title'
	}
})

export default class Interfaces extends Vue {
	private powerUser = false;
	private interfaceOption = 'iqrf::IqrfSpi'
	private interfaceSelect: Array<IOption> = [
		{
			value: 'iqrf::IqrfSpi',
			label: this.$t('config.iqrfSpi.title').toString()
		},
		{
			value: 'iqrf::IqrfCdc',
			label: this.$t('config.iqrfCdc.title').toString()
		},
		{
			value: 'iqrf::IqrfUart',
			label: this.$t('config.iqrfUart.title').toString()
		}
	]
	
	mounted(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
	}

	get getInterface(): string {
		return this.interfaceOption;
	}
}
</script>
