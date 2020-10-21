<template>
	<CCard>
		<CCardHeader><h3>{{ $t('config.daemon.other.title') }}</h3></CCardHeader>
		<CCardBody>
			<IqrfRepository />
			<IqrfInfo />
			<IqmeshServices v-if='powerUser' />
			<JsonRawApi v-if='powerUser' />
			<JsonMngMetaDataApi v-if='powerUser' />
			<JsonSplitter v-if='powerUser' />
			<JsonApi v-if='!powerUser' />
			<TracerList />
			<MonitorList />
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CCardBody, CCardHeader} from '@coreui/vue/src';
import JsonRawApi from '../../pages/Config/JsonRawApi.vue';
import JsonMngMetaDataApi from '../../pages/Config/JsonMngMetaDataApi.vue';
import JsonSplitter from '../../pages/Config/JsonSplitter.vue';
import JsonApi from '../../pages/Config/JsonApi.vue';
import SchedulerList from '../../pages/Config/SchedulerList.vue';
import TracerList from '../../pages/Config/TracerList.vue';
import MonitorList from '../../pages/Config/MonitorList.vue';
import IqrfRepository from '../../pages/Config/IqrfRepository.vue';
import IqrfInfo from '../../pages/Config/IqrfInfo.vue';
import IqmeshServices from '../../pages/Config/IqmeshServices.vue';

@Component({
	components: {
		CCard,
		CCardBody,
		CCardHeader,
		IqmeshServices,
		IqrfInfo,
		IqrfRepository,
		JsonApi,
		JsonMngMetaDataApi,
		JsonRawApi,
		JsonSplitter,
		MonitorList,
		SchedulerList,
		TracerList
	},
	metaInfo: {
		title: 'config.daemon.other.title'
	}
})

export default class OtherConfiguration extends Vue {
	private powerUser = false;

	created(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
		}
	}
}
</script>
