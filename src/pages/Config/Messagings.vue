<template>
	<CCard>
		<CCardHeader><h3>{{ $t('config.daemon.messagings.title') }}</h3></CCardHeader>
		<CCardBody>
			<CSelect
				:value.sync='messagingOption'
				:options='messagingSelect'
				:label='$t("config.daemon.form.messaging")'
			/>
			<MqttMessagingTable v-if='messagingOption === "mqtt"' />
			<WebsocketList v-if='messagingOption === "ws"' />
			<MqMessagingTable v-if='messagingOption === "mq"' />
			<UdpMessagingTable v-if='messagingOption === "udp"' />
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CCardBody, CCardHeader, CSelect} from '@coreui/vue/src';
import MqttMessagingTable from '../../pages/Config/MqttMessagingTable.vue';
import WebsocketList from '../../pages/Config/WebsocketList.vue';
import MqMessagingTable from '../../pages/Config/MqMessagingTable.vue';
import UdpMessagingTable from '../../pages/Config/UdpMessagingTable.vue';
import { IOption } from '../../interfaces/coreui';

@Component({
	components: {
		CCard,
		CCardBody,
		CCardHeader,
		CSelect,
		MqttMessagingTable,
		WebsocketList,
		MqMessagingTable,
		UdpMessagingTable,
	},
	metaInfo: {
		title: 'config.daemon.messagings.title'
	}
})

export default class Messagings extends Vue {
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
}
</script>
