<template>
	<div>
		<h1>{{ $t('config.daemon.misc.title') }}</h1>
		<CCard>
			<CTabs variant='tabs' :active-tab='activeTab'>
				<CTab :title='$t("config.daemon.misc.jsonApi.title")'>
					<JsonApi 
						v-if='!powerUser'
						@fetched='configFetched'
					/>
					<div v-else>
						<JsonMngMetaDataApi @fetched='configFetched' />
						<JsonRawApi @fetched='configFetched' />
						<JsonSplitter @fetched='configFetched' />
					</div>
				</CTab>
				<CTab :title='$t("config.daemon.misc.iqrfRepository.title")'>
					<IqrfRepository @fetched='configFetched' />
				</CTab>
				<CTab :title='$t("config.daemon.misc.iqrfInfo.title")'>
					<IqrfInfo @fetched='configFetched' />
				</CTab>
				<CTab v-if='powerUser' :title='$t("config.daemon.misc.iqmesh.title")'>
					<IqmeshServices @fetched='configFetched' />
				</CTab>
				<CTab :title='$t("config.daemon.misc.monitor.title")'>
					<MonitorList @fetched='configFetched' />
				</CTab>
				<CTab :title='$t("config.daemon.misc.tracer.title")'>
					<TracerList @fetched='configFetched' />
				</CTab>
			</CTabs>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CCardBody, CCardHeader, CTab, CTabs} from '@coreui/vue/src';
import IqrfInfo from '../../components/Config/IqrfInfo.vue';
import IqrfRepository from '../../components/Config/IqrfRepository.vue';
import IqmeshServices from '../../components/Config/IqmeshServices.vue';
import JsonApi from '../../components/Config/JsonApi.vue';
import JsonMngMetaDataApi from '../../components/Config/JsonMngMetaDataApi.vue';
import JsonRawApi from '../../components/Config/JsonRawApi.vue';
import JsonSplitter from '../../components/Config/JsonSplitter.vue';
import MonitorList from '../../components/Config/MonitorList.vue';
import TracerList from '../../components/Config/TracerList.vue';

@Component({
	components: {
		CCard,
		CCardBody,
		CCardHeader,
		CTab,
		CTabs,
		IqmeshServices,
		IqrfInfo,
		IqrfRepository,
		JsonApi,
		JsonMngMetaDataApi,
		JsonRawApi,
		JsonSplitter,
		MonitorList,
		TracerList
	},
	metaInfo: {
		title: 'config.daemon.misc.title'
	}
})

/**
 * Miscellaneous configuration page component
 */
export default class MiscConfiguration extends Vue {
	/**
	 * @var {number} activeTab Index of active tab in CoreUI tabs
	 */
	private activeTab = 0;

	/**
	 * @var {Array<string>} endpoints Array of misc tab endpoints
	 */
	private endpoints: Array<string> = [
		'json',
		'repository',
		'db',
		'monitor',
		'tracer'
	]

	/**
	 * @var {boolean} powerUser Indicates whether user profile is power user
	 */
	private powerUser = false;

	/**
	 * @var {Array<string>} childrenLoading Children components
	 */
	private childrenLoading = [
		'repository',
		'info',
		'monitor',
		'tracer',
	]

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		if (this.$store.getters['user/getRole'] === 'power') {
			this.powerUser = true;
			this.childrenLoading.unshift('jsonMng', 'jsonRaw', 'jsonSplitter');
		} else {
			this.childrenLoading.unshift('jsonApi');
		}
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		if (this.powerUser) {
			this.endpoints.splice(3, 0, 'iqmesh');
		}
		if (this.$attrs.tabName !== undefined) {
			this.activeTab = this.endpoints.indexOf(this.$attrs.tabName);
		}
		this.$store.commit('spinner/SHOW');
	}

	/**
	 * Handles successful child component config fetch event
	 * @param {string} name Component name
	 */
	private configFetched(name: string): void {
		this.childrenLoading = this.childrenLoading.filter((item: string) => {
			return item !== name;
		});
		if (this.childrenLoading.length === 0) {
			this.$store.commit('spinner/HIDE');
		}
	}
}
</script>
