<template>
	<div>
		<div v-if='powerUser'>
			<WebsocketMessagingList />
			<WebsocketServiceList />
		</div>
		<div v-else>
			<WebsocketInterfaceList />
		</div>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import WebsocketInterfaceList from '../../components/Config/WebsocketInterfaceList.vue';
import WebsocketMessagingList from '../../components/Config/WebsocketMessagingList.vue';
import WebsocketServiceList from '../../components/Config/WebsocketServiceList.vue';

@Component({
	components: {
		WebsocketInterfaceList,
		WebsocketMessagingList,
		WebsocketServiceList,
	},
	metaInfo: {
		title: 'config.websocket.title'
	}
})

export default class WebsocketList extends Vue {
	private powerUser = false

	mounted(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
	}
}
</script>
