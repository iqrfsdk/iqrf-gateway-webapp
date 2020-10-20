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
					<ComponentList @update-interface='getConfig' />
				</CCard>
			</CTab>
			<CTab :title='$t("config.daemon.tabs.interfaces")'>
				<CCard body-wrapper class='border-0'>
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
					<IqmeshServices v-if='powerUser' />
					<JsonRawApi v-if='powerUser' />
					<JsonMngMetaDataApi v-if='powerUser' />
					<JsonSplitter v-if='powerUser' />
					<JsonApi v-if='!powerUser' />
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
import JsonApi from '../../pages/Config/JsonApi.vue';
import SchedulerList from '../../pages/Config/SchedulerList.vue';
import TracerList from '../../pages/Config/TracerList.vue';
import MonitorList from '../../pages/Config/MonitorList.vue';
import ConfigMigration from '../../pages/Config/ConfigMigration.vue';
import { IOption } from '../../interfaces/coreui';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import { AxiosError, AxiosResponse } from 'axios';
import FormErrorHandler from '../../helpers/FormErrorHandler';

interface ComponentItem {
	enabled: boolean
	libraryName: string
	libraryPath: string
	name: string
	startLevel: number
}

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
		JsonApi,
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
		this.getConfig();
	}

	get getInterface(): string {
		return this.interfaceOption;
	}

	private getConfig(): void {
		this.$store.commit('spinner/SHOW');
		DaemonConfigurationService.getComponent('')
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				const whitelistedComponents = ['iqrf::IqrfCdc', 'iqrf::IqrfSpi', 'iqrf::IqrfUart'];
				const components = response.data.components.filter((component: ComponentItem) => {
					if (whitelistedComponents.includes(component.name)) {
						return component;
					}
				});
				for (let item of components) {
					if (item.enabled) {
						this.interfaceOption = item.name;
						return;
					}
				}
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}
}
</script>
