<template>
	<CTabs variant='pills' :active-tab='activeTab' :vertical='{content: "col-lg"}'>
		<CTab v-if='powerUser' :title='$t("config.main.title")'>
			<MainConfiguration />
		</CTab>
		<CTab v-if='powerUser' :title='$t("config.components.title")'>
			<ComponentList />
		</CTab>
		<CTab v-else :title='$t("config.selectedComponents.title")'>
			<ComponentList />
		</CTab>
		<CTab :title='$t("config.iqrfSpi.title")'>
			<IqrfSpi />
		</CTab>
		<CTab :title='$t("config.iqrfCdc.title")'>
			<IqrfCdc />
		</CTab>
		<CTab :title='$t("config.iqrfUart.title")'>
			<IqrfUart />
		</CTab>
		<CTab :title='$t("config.iqrfDpa.title")'>
			<IqrfDpa />
		</CTab>
		<CTab :title='$t("config.iqrfRepository.title")'>
			<IqrfRepository />
		</CTab>
		<CTab :title='$t("config.iqrfInfo.title")'>
			<IqrfInfo />
		</CTab>
		<CTab :title='$t("config.iqmesh.title")'>
			<IqmeshServices />
		</CTab>
		<CTab :title='$t("config.mqtt.title")'>
			<MqttMessagingTable />
		</CTab>
		<CTab :title='$t("config.websocket.title")'>
			<WebsocketList />
		</CTab>
		<CTab :title='$t("config.mq.title")'>
			<MqMessagingTable />
		</CTab>
		<CTab :title='$t("config.udp.title")'>
			<UdpMessagingTable />
		</CTab>
		<CTab v-if='powerUser' :title='$t("config.jsonRawApi.title")'>
			<JsonRawApi />
		</CTab>
		<CTab :title='$t("config.jsonMngMetaDataApi.title")'>
			<JsonMngMetaDataApi />
		</CTab>
		<CTab v-if='powerUser' :title='$t("config.jsonSplitter.title")'>
			<JsonSplitter />
		</CTab>
		<CTab :title='$t("config.scheduler.title")'>
			<SchedulerList />
		</CTab>
		<CTab :title='$t("config.tracer.title")'>
			<TracerList />
		</CTab>
		<CTab :title='$t("config.monitor.title")'>
			<MonitorList />
		</CTab>
		<CTab :title='$t("config.migration.title")'>
			<ConfigMigration />
		</CTab>
	</CTabs>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CTab, CTabs} from '@coreui/vue/src';
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

@Component({
	components: {
		CCard,
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

	created(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
	}
}
</script>
