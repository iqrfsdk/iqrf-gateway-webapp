<template>
	<CSidebar
		fixed
		:minimize='minimize'
		:show='show'
		@update:show='(value) => $store.commit("sidebar/set", ["show", value])'
	>
		<CSidebarBrand class='d-md-down-none' to='/'>
			<LogoBig class='c-sidebar-brand-full' :alt='$t("core.title")' />
			<LogoSmall class='c-sidebar-brand-minimized' :alt='$t("core.title")' />
		</CSidebarBrand>
		<CRenderFunction flat :content-to-render='getNav' />
		<CSidebarMinimizer
			class='d-md-down-none'
			@click.native='$store.commit("sidebar/set", ["minimize", !minimize])'
		/>
	</CSidebar>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {
	CRenderFunction,
	CSidebar,
	CSidebarBrand,
	CSidebarMinimizer,
} from '@coreui/vue/src';
import {
	cibGrafana,
	cibNodeRed,
	cilBook,
	cilCloud,
	cilLan,
	cilLockLocked,
	cilStorage,
	cilSettings,
	cilToggleOff,
	cilUser,
	cilWifiSignal4,
} from '@coreui/icons';
import LogoBig from '../assets/logo-big.svg';
import LogoSmall from '../assets/logo-small.svg';
import VueI18n from 'vue-i18n';

interface NavMemberItem {
	component?: string
	feature?: string
	href?: string
	name: VueI18n.TranslateResult
	roles: Array<string>
	target?: string
	to?: string
}

interface NavMemberIcon {
	content: Array<string>
}

interface NavMember {
	_name: string
	feature?: string
	href?: string
	icon: NavMemberIcon
	items?: Array<NavMemberItem>
	name: VueI18n.TranslateResult
	target?: string
	to?: string
}

interface NavData {
	_name: string
	_children: Array<NavMember>
}

@Component({
	components: {
		CRenderFunction,
		CSidebar,
		CSidebarBrand,
		CSidebarMinimizer,
		LogoBig,
		LogoSmall,
	}
})

export default class TheSidebar extends Vue {
	get show(): boolean {
		return this.$store.state.sidebar.show;
	}

	get minimize(): boolean {
		return this.$store.state.sidebar.minimize;
	}

	get getNav(): Array<NavData> {
		const data = [
			{
				_name: 'CSidebarNav',
				_children: [
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('gateway.title'),
						to: '/gateway/',
						route: '/gateway/',
						icon: {content: cilStorage},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('gateway.info.title'),
								to: '/gateway/info/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('gateway.log.title'),
								to: '/gateway/log/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('gateway.mode.title'),
								to: '/gateway/change-mode/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('service.iqrf-gateway-daemon.title'),
								to: '/service/iqrf-gateway-daemon/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('service.ssh.title'),
								to: '/service/ssh/',
								feature: 'ssh',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('service.unattended-upgrades.title'),
								to: '/service/unattended-upgrades/',
								feature: 'unattendedUpgrades',
								roles: ['power', 'normal'],
							},
							/*{
								name: this.$t('gateway.updater.title'),
								to: '/gateway/updater/',
								feature: 'updater',
								roles: ['power', 'normal'],
							},*/ 
							{
								name: this.$t('gateway.power.title'),
								to: '/gateway/power/',
								roles: ['power', 'normal'],
							},
						],
					},
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('config.title'),
						to: '/config/',
						route: '/config/',
						icon: {content: cilSettings},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('config.main.title'),
								to: '/config/main/',
								roles: ['power'],
							},
							{
								name: this.$t('config.components.title'),
								to: '/config/component/',
								roles: ['power'],
							},
							{
								name: this.$t('config.selectedComponents.title'),
								to: '/config/component/',
								roles: ['normal'],
							},
							{
								name: this.$t('config.iqrfSpi.title'),
								to: '/config/iqrf-spi/',
								component: 'iqrf::IqrfSpi',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqrfCdc.title'),
								to: '/config/iqrf-cdc/',
								component: 'iqrf::IqrfCdc',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqrfUart.title'),
								to: '/config/iqrf-uart/',
								component: 'iqrf::IqrfUart',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqrfDpa.title'),
								to: '/config/iqrf-dpa/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqrfRepository.title'),
								to: '/config/iqrf-repository/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqrfInfo.title'),
								to: '/config/iqrf-info/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.iqmesh.title'),
								to: '/config/iqmesh/',
								roles: ['power'],
							},
							{
								name: this.$t('config.mqtt.title'),
								to: '/config/mqtt/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.websocket.title'),
								to: '/config/websocket/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.mq.title'),
								to: '/config/mq/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.udp.title'),
								to: '/config/udp/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.jsonRawApi.title'),
								to: '/config/json-raw-api/',
								roles: ['power'],
							},
							{
								name: this.$t('config.jsonMngMetaDataApi.title'),
								to: '/config/json-mng-meta-data-api/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.jsonSplitter.title'),
								to: '/config/json-splitter/',
								roles: ['power'],
							},
							{
								name: this.$t('config.scheduler.title'),
								to: '/config/scheduler/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.tracer.title'),
								to: '/config/tracer/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.monitor.title'),
								to: '/config/monitor/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.migration.title'),
								to: '/config/migration/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('translatorConfig.title'),
								to: '/config/translator/',
								feature: 'iqrfGatewayTranslator',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('controllerConfig.title'),
								to: '/config/controller/',
								feature: 'iqrfGatewayController',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('config.mender.title'),
								to: '/config/mender/',
								feature: 'mender',
								roles: ['power', 'normal']
							},
						],
					},
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('iqrfnet.title'),
						to: '/iqrfnet/',
						route: '/iqrfnet/',
						icon: {content: cilWifiSignal4},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('iqrfnet.sendPacket.title'),
								to: '/iqrfnet/send-raw/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('iqrfnet.sendJson.title'),
								to: '/iqrfnet/send-json/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('iqrfnet.trUpload.title'),
								to: '/iqrfnet/tr-upload/',
								feature: 'trUpload',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('iqrfnet.trConfiguration.title'),
								to: '/iqrfnet/tr-config/0',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('iqrfnet.networkManager.title'),
								to: '/iqrfnet/network/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('iqrfnet.standard.title'),
								to: '/iqrfnet/standard/',
								roles: ['power', 'normal'],
							},
						],
					},
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('network.title'),
						to: '/network/',
						route: '/network/',
						feature: 'networkManager',
						icon: {content: cilLan},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('network.ethernet.title'),
								to: '/network/ethernet',
								roles: ['power', 'normal'],
							},
						],
					},
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('cloud.title'),
						to: '/cloud/',
						route: '/cloud/',
						icon: {content: cilCloud},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('cloud.ibmCloud.title'),
								to: '/cloud/ibm-cloud/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('cloud.msAzure.title'),
								to: '/cloud/azure/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('cloud.amazonAws.title'),
								to: '/cloud/aws/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('cloud.hexio.title'),
								to: '/cloud/hexio/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('cloud.intelimentsInteliGlue.title'),
								to: '/cloud/inteli-glue/',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('cloud.pixla.title'),
								to: '/cloud/pixla/',
								feature: 'pixla',
								roles: ['power', 'normal'],
							},
						],
					},
					{
						_name: 'CSidebarNavItem',
						name: this.$t('core.grafana.title'),
						href: this.$store.getters['features/configuration']('grafana').url,
						target: '_blank',
						feature: 'grafana',
						icon: {content: cibGrafana},
					},
					{
						_name: 'CSidebarNavDropdown',
						name: this.$t('core.nodeRed.title'),
						feature: 'nodeRed',
						icon: {content: cibNodeRed},
						roles: ['power', 'normal'],
						items: [
							{
								name: this.$t('core.nodeRed.workflow.title'),
								href: this.$store.getters['features/configuration']('nodeRed').url,
								target: '_blank',
								roles: ['power', 'normal'],
							},
							{
								name: this.$t('core.nodeRed.dashboard.title'),
								href: this.$store.getters['features/configuration']('nodeRed').url + 'ui/',
								target: '_blank',
								roles: ['power', 'normal'],
							},
						],
					},
					{
						_name: 'CSidebarNavItem',
						name: this.$t('core.supervisor.title'),
						href: this.$store.getters['features/configuration']('supervisord').url,
						target: '_blank',
						feature: 'supervisord',
						icon: {content: cilToggleOff},
					},
					{
						_name: 'CSidebarNavItem',
						name: this.$t('core.user.title'),
						to: '/user/',
						icon: {content: cilUser},
						roles: ['power', 'normal'],
					},
					{
						_name: 'CSidebarNavItem',
						name: this.$t('core.apiKey.title'),
						to: '/api-key/',
						icon: {content: cilLockLocked},
						roles: ['power', 'normal']
					},
					{
						_name: 'CSidebarNavItem',
						name: this.$t('core.documentation.title'),
						href: this.$store.getters['features/configuration']('docs').url,
						target: '_blank',
						feature: 'docs',
						icon: {content: cilBook},
					},
				],
			},
		];
		return data.filter((element) => {
			element._children = element._children.filter((element) => {
				if (element.roles !== undefined &&
						!element.roles.includes(this.$store.getters['user/getRole'])) {
					return null;
				}
				if (element.feature !== undefined &&
						!this.$store.getters['features/isEnabled'](element.feature)) {
					return null;
				}
				if (element.items) {
					element.items = element.items.filter((element: NavMemberItem) => {
						if (element.roles !== undefined &&
								!element.roles.includes(this.$store.getters['user/getRole'])) {
							return null;
						}
						if (element.feature !== undefined &&
								!this.$store.getters['features/isEnabled'](element.feature)) {
							return null;
						}
						return element;
					});
				}
				return element;
			});
			return element;
		});
	}

}
</script>
