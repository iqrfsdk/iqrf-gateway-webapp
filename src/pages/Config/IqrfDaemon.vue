<template>
	<CCard>
		<CTabs variant='tabs' :active-tab='activeTab'>
			<CTab v-if='powerUser' :title='$t("config.main.title")'>
				<CCard body-wrapper class='border-0'>
					<MainConfiguration />
				</CCard>
			</CTab>
			<CTab :title='$t("config.components.title")'>
				<CCard body-wrapper class='border-0'>
					<ComponentList />
				</CCard>
			</CTab>
			<CTab :title='$t("config.daemon.tabs.interfaces")'>
				<CCard body-wrapper class='border-0'>
					<CSelect
						color='primary'
						:value.sync='interfaceOption'
						:options='interfaceSelect'
						:label='$t("config.daemon.form.interface")'
					/>
					<IqrfSpi v-if='interfaceOption === "spi"' />
					<IqrfCdc v-if='interfaceOption === "cdc"' />
					<IqrfUart v-if='interfaceOption === "uart"' />
					<IqrfDpa />
				</CCard>
			</CTab>
			<CTab :title='$t("config.daemon.tabs.messaging")'>
				<CCard body-wrapper class='border-0'>
					<CSelect
						:value.sync='messagingOption'
						:options='messagingSelect'
						:label='$t("config.daemon.form.messaging")'
					/>
					<MqttMessagingTable v-if='messagingOption === "mqtt"' />
					<WebsocketList v-if='messagingOption === "ws"' />
					<MqMessagingTable v-if='messagingOption === "mq"' />
					<UdpMessagingTable v-if='messagingOption === "udp"' />
				</CCard>
			</CTab>
			<CTab :title='$t("config.daemon.tabs.scheduler")'>
				<CCard body-wrapper class='border-0'>
					<SchedulerList />
				</CCard>
			</CTab>
			<CTab :title='$t("config.daemon.tabs.other")'>
				<CCard body-wrapper class='border-0'>
					<IqrfRepository />
					<IqrfInfo />
					<IqmeshServices />
					<JsonRawApi />
					<JsonMngMetaDataApi />
					<JsonSplitter />
					<TracerList />
					<MonitorList />
				</CCard>
			</CTab>
		</CTabs>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CForm, CSelect, CTab, CTabs} from '@coreui/vue/src';
import MainConfiguration from '../../pages/Config/MainConfiguration.vue';
import ComponentList from '../../pages/Config/ComponentList.vue';
import IqrfSpi from '../../pages/Config/IqrfSpi.vue';
import IqrfCdc from '../../pages/Config/IqrfCdc.vue';
import IqrfUart from '../../pages/Config/IqrfUart.vue';
import IqrfDpa from '../../pages/Config/IqrfDpa.vue';
import IqrfRepository from '../../pages/Config/IqrfRepository.vue';
import IqrfInfo from '../../pages/Config/IqrfInfo.vue';
import IqmeshServices from '../../pages/Config/IqmeshServices.vue';
import MqttMessagingTable from '../../pages/Config/MqttMessagingTable.vue';
import WebsocketList from '../../pages/Config/WebsocketList.vue';
import MqMessagingTable from '../../pages/Config/MqMessagingTable.vue';
import UdpMessagingTable from '../../pages/Config/UdpMessagingTable.vue';
import JsonRawApi from '../../pages/Config/JsonRawApi.vue';
import JsonMngMetaDataApi from '../../pages/Config/JsonMngMetaDataApi.vue';
import JsonSplitter from '../../pages/Config/JsonSplitter.vue';
import SchedulerList from '../../pages/Config/SchedulerList.vue';
import TracerList from '../../pages/Config/TracerList.vue';
import MonitorList from '../../pages/Config/MonitorList.vue';
import ConfigMigration from '../../pages/Config/ConfigMigration.vue';
import { IOption } from '../../interfaces/coreui';

@Component({
	components: {
		CCard,
		CForm,
		CSelect,
		CTab,
		CTabs,
		ComponentList,
		ConfigMigration,
		IqmeshServices,
		IqrfCdc,
		IqrfDpa,
		IqrfInfo,
		IqrfRepository,
		IqrfSpi,
		IqrfUart,
		JsonMngMetaDataApi,
		JsonRawApi,
		JsonSplitter,
		MainConfiguration,
		MonitorList,
		MqMessagingTable,
		MqttMessagingTable,
		SchedulerList,
		TracerList,
		UdpMessagingTable,
		WebsocketList,
	},
	metaInfo: {
		title: 'config.daemon.description'
	}
})

export default class IqrfDaemon extends Vue {
	private activeTab = 0
	private powerUser = false;
	private interfaceOption = 'spi'
	private interfaceSelect: Array<IOption> = [
		{
			value: 'spi',
			label: this.$t('config.iqrfSpi.title').toString()
		},
		{
			value: 'cdc',
			label: this.$t('config.iqrfCdc.title').toString()
		},
		{
			value: 'uart',
			label: this.$t('config.iqrfUart.title').toString()
		}
	]
	private messagingOption = 'mqtt'
	private messagingSelect: Array<IOption> = [
		{
			value: 'mqtt',
			label: this.$t('config.mqtt.title').toString()
		},
		{
			value: 'ws',
			label: this.$t('config.websocket.title').toString()
		},
		{
			value: 'mq',
			label: this.$t('config.mq.title').toString()
		},
		{
			value: 'udp',
			label: this.$t('config.udp.title').toString()
		}
	]

	mounted(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
	}
}
</script>
