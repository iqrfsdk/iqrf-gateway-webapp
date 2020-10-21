import { compare } from 'compare-versions';
import Vue from 'vue';
import VueRouter, {RouteConfig} from 'vue-router';

import TheDashboard from '../components/TheDashboard.vue';

const CloudDisambiguation = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/CloudDisambiguation.vue');
const AzureCreator = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/AzureCreator.vue');
const AwsCreator = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/AwsCreator.vue');
const HexioCreator = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/HexioCreator.vue');
const IbmCreator = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/IbmCreator.vue');
const InteliGlueCreator = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/InteliGlueCreator.vue');
const PixlaControl = () => import(/* webpackChunkName: "cloud" */ '@/pages/Cloud/PixlaControl.vue');

const GatewayDisambiguation = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/GatewayDisambiguation.vue');
const GatewayInfo = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/GatewayInfo.vue');
const DaemonLogViewer = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/DaemonLogViewer.vue');
const DaemonMode = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/DaemonMode.vue');
const PowerControl = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/PowerControl.vue');
const ServiceControl = () => import(/* webpackChunkName: "gateway" */ '@/pages/Gateway/ServiceControl.vue');

const ApiKeyList = () => import(/* webpackChunkName: "core" */ '@/pages/Core/ApiKeyList.vue');
const ApiKeyForm = () => import(/* webpackChunkName: "core" */ '@/pages/Core/ApiKeyForm.vue');
const MainDisambiguation = () => import(/* webpackChunkName: "core" */ '@/pages/Core/MainDisambiguation.vue');
const NotFound = () => import(/* webpackChunkName: "core" */ '@/pages/Core/NotFound.vue');
const UserAdd = () => import(/* webpackChunkName: "core" */ '@/pages/Core/UserAdd.vue');
const UserEdit = () => import(/* webpackChunkName: "core" */ '@/pages/Core/UserEdit.vue');
const UserList = () => import(/* webpackChunkName: "core" */ '@/pages/Core/UserList.vue');
const SignIn = () => import(/* webpackChunkName: "core" */ '@/pages/Core/SignIn.vue');

const IqrfNetDisambiguation = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/IqrfNetDisambiguation.vue');
const DeviceEnumeration = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/DeviceEnumeration.vue');
const NetworkManager = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/NetworkManager.vue');
const SendJsonRequest = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/SendJsonRequest.vue');
const SendDpaPacket = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/SendDpaPacket.vue');
const StandardManager = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/StandardManager.vue');
const TrConfiguration = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/TrConfiguration.vue');
const TrUpload = () => import(/* webpackChunkName: "iqrfNet" */ '@/pages/IqrfNet/TrUpload.vue');

const InstallationBase = () => import(/* webpackChunkName: "install" */ '@/pages/Install/InstallationBase.vue');
const InstallCreateUser = () => import(/* webpackChunkName: "install" */ '@/pages/Install/InstallCreateUser.vue');
const InstallationDisambiguation = () => import(/* webpackChunkName: "install" */ '@/pages/Install/InstallationDisambiguation.vue');
const InstallGatewayInfo = () => import(/* webpackChunkName: "install" */ '@/pages/Install/InstallGatewayInfo.vue');
const MissingMigration = () => import(/* webpackChunkName: "install" */ '@/pages/Install/MissingMigration.vue');

const ConfigDisambiguation = () => import(/* webpackChunkName: "config" */ '@/pages/Config/ConfigDisambiguation.vue');
const DaemonDisambiguation = () => import(/* WebpackChunkName: "config" */ '@/pages/Config/DaemonDisambiguation.vue');
const Interfaces = () => import(/* WebpackChunkName: "config" */ '@/pages/Config/Interfaces.vue');
const Messagings = () => import (/* WebpackChunkName: "config" */ '@/pages/Config/Messagings.vue');
const OtherConfiguration = () => import (/* WebpackChunkName: "config" */ '@/pages/Config/OtherConfiguration.vue');
const ConfigMigration = () => import(/* webpackChunkName: "config" */ '@/pages/Config/ConfigMigration.vue');
const TranslatorConfig = () => import(/* webpackChunkName: "config" */ '@/pages/Config/TranslatorConfig.vue');
const ControllerConfig = () => import(/* webpackChunkName: "config" */ '@/pages/Config/ControllerConfig.vue');
const MenderConfig = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MenderConfig.vue');
const IqrfInfo = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfInfo.vue');
const IqrfRepository = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfRepository.vue');
const IqrfCdc = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfCdc.vue');
const IqrfDpa = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfDpa.vue');
const IqrfSpi = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfSpi.vue');
const IqrfUart = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqrfUart.vue');
const JsonMngMetaDataApi = () => import(/* webpackChunkName: "config" */ '@/pages/Config/JsonMngMetaDataApi.vue');
const JsonRawApi = () => import(/* webpackChunkName: "config" */ '@/pages/Config/JsonRawApi.vue');
const JsonSplitter = () => import(/* webpackChunkName: "config" */ '@/pages/Config/JsonSplitter.vue');
const MonitorForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MonitorForm.vue');
const MonitorList = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MonitorList.vue');
const MqMessagingForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MqMessagingForm.vue');
const MqMessagingTable = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MqMessagingTable.vue');
const MqttMessagingForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MqttMessagingForm.vue');
const MqttMessagingTable = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MqttMessagingTable.vue');
const UdpMessagingForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/UdpMessagingForm.vue');
const UdpMessagingTable = () => import(/* webpackChunkName: "config" */ '@/pages/Config/UdpMessagingTable.vue');
const TracerList = () => import(/* webpackChunkName: "config" */ '@/pages/Config/TracerList.vue');
const TracerForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/TracerForm.vue');
const MainConfiguration = () => import(/* webpackChunkName: "config" */ '@/pages/Config/MainConfiguration.vue');
const ComponentList = () => import(/* webpackChunkName: "config" */ '@/pages/Config/ComponentList.vue');
const ComponentForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/ComponentForm.vue');
const IqmeshServices = () => import(/* webpackChunkName: "config" */ '@/pages/Config/IqmeshServices.vue');
const WebsocketInterfaceForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/WebsocketInterfaceForm.vue');
const WebsocketMessagingForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/WebsocketMessagingForm.vue');
const WebsocketServiceForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/WebsocketServiceForm.vue');
const WebsocketList = () => import(/* webpackChunkName: "config" */ '@/pages/Config/WebsocketList.vue');
const SchedulerList = () => import(/* webpackChunkName: "config" */ '@/pages/Config/SchedulerList.vue');
const SchedulerForm = () => import(/* webpackChunkName: "config" */ '@/pages/Config/SchedulerForm.vue');

const NetworkDisambiguation = () => import(/* webpackChunkName: "network" */ '@/pages/Network/NetworkDisambiguation.vue');
const ConnectionForm = () => import(/* webpackChunkName: "network" */ '@/pages/Network/ConnectionForm.vue');
const EthernetInterfaces = () => import(/* webpackChunkName: "network" */ '@/pages/Network/EthernetInterfaces.vue');

import store from '../store';

Vue.use(VueRouter);

const routes: Array<RouteConfig> = [
	{
		component: SignIn,
		name: 'signIn',
		path: '/sign/in',
	},
	{
		path: '/install',
		component: InstallationBase,
		children: [
			{
				component: InstallationDisambiguation,
				path: '',
			},
			{
				component: InstallCreateUser,
				path: 'user',
			},
			{
				component: InstallGatewayInfo,
				path: 'gateway-info',
			},
			{
				component: MissingMigration,
				path: 'error/missing-migration'
			},
		]
	},
	{
		path: '/',
		component: TheDashboard,
		children: [
			{
				path: '/cloud',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: CloudDisambiguation,
						path: '',
					},
					{
						component: AzureCreator,
						path: 'azure',
					},
					{
						component: AwsCreator,
						path: 'aws',
					},
					{
						component: HexioCreator,
						path: 'hexio',
					},
					{
						component: IbmCreator,
						path: 'ibm-cloud',
					},
					{
						component: InteliGlueCreator,
						path: 'inteli-glue',
					},
					{
						component: PixlaControl,
						path: 'pixla',
					},
				]
			},
			{
				path: '/config',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: ConfigDisambiguation,
						path: '',
					},
					{
						path: 'daemon',
						component: {
							render(c) {
								return c('router-view');
							}
						},
						children: [
							{
								path: '',
								component: DaemonDisambiguation,
							},
							{
								component: MainConfiguration,
								path: 'main',
							},
							{
								path: 'component',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: ComponentList
									},
									{
										component: ComponentForm,
										path: 'add',
									},
									{
										component: ComponentForm,
										path: 'edit/:component',
										props: true,
									},
								],
							},
							{
								path: 'interfaces',
								component: Interfaces,
							},
							{
								path: 'messagings',
								component: Messagings,
							},
							{
								path: 'scheduler',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: SchedulerList,
									},
									{
										path: 'add',
										component: SchedulerForm,
									},
									{
										path: 'edit/:id',
										component: SchedulerForm,
										props: (route) => {
											const id = Number.parseInt(route.params.id, 10);
											if (Number.isNaN(id)) {
												return 0;
											}
											return {id};
										},
									}
								]
							},
							{
								path: 'other',
								component: OtherConfiguration,
							},
							{
								path: 'monitor',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: MonitorList,
									},
									{
										component: MonitorForm,
										path: 'add',
									},
									{
										component: MonitorForm,
										path: 'edit/:instance',
										props: true,
									},
								],
							},
							{
								path: 'mq',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: MqMessagingTable,
									},
									{
										component: MqMessagingForm,
										path: 'add',
									},
									{
										component: MqMessagingForm,
										path: 'edit/:instance',
										props: true,
									},
								],
							},
							{
								path: 'mqtt',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: MqttMessagingTable,
									},
									{
										component: MqttMessagingForm,
										path: 'add',
									},
									{
										component: MqttMessagingForm,
										path: 'edit/:instance',
										props: true,
									},
								],
							},
							{
								path: 'udp',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: UdpMessagingTable,
									},
									{
										component: UdpMessagingForm,
										path: 'add',
									},
									{
										component: UdpMessagingForm,
										path: 'edit/:instance',
										props: true,
									},
								],
							},
							{
								path: 'websocket',
								component: {
									render(c) {
										return c('router-view');
									}
								},
								children: [
									{
										path: '',
										component: WebsocketList,
									},
									{
										component: WebsocketInterfaceForm,
										path: 'add',
									},
									{
										component: WebsocketMessagingForm,
										path: 'add-messaging',
									},
									{
										component: WebsocketServiceForm,
										path: 'add-service',
									},
									{
										component: WebsocketInterfaceForm,
										path: 'edit/:instance',
										props: true,
									},
									{
										component: WebsocketMessagingForm,
										path: 'edit-messaging/:instance',
										props: true,
									},
									{
										component: WebsocketServiceForm,
										path: 'edit-service/:instance',
										props: true,
									},
								],
							},
							{
								path: 'tracer',
								component: {
									render(c) {
										return c('router-view');
									},
								},
								children: [
									{
										path: '',
										component: TracerList,
									},
									{
										component: TracerForm,
										path: 'add',
									},
									{
										component: TracerForm,
										path: 'edit/:instance',
										props: true,
									},
								],
							},
						]
					},
					{
						component: ConfigMigration,
						path: 'migration',
					},
					{
						component: MenderConfig,
						path: 'mender',
					},
					{
						component: TranslatorConfig,
						path: 'translator',
					},
					{
						component: ControllerConfig,
						path: 'controller',
					},
				]
			},
			{
				path: '/gateway',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: GatewayDisambiguation,
						path: '',
					},
					{
						component: GatewayInfo,
						path: 'info',
					},
					{
						component: DaemonLogViewer,
						path: 'log',
					},
					{
						component: DaemonMode,
						path: 'change-mode',
					},
					{
						component: PowerControl,
						path: 'power',
					},
				]
			},
			{
				component: ServiceControl,
				name: 'serviceControl',
				path: '/service/:serviceName',
				props: true,
			},
			{
				path: '/iqrfnet',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: IqrfNetDisambiguation,
						path: '',
					},
					{
						component: DeviceEnumeration,
						path: 'enumeration/:address',
						props: (route) => {
							const address = Number.parseInt(route.params.address, 10);
							if (Number.isNaN(address)) {
								return 0;
							}
							return {address};
						},
					},
					{
						component: NetworkManager,
						path: 'network',
					},
					{
						component: StandardManager,
						path: 'standard',
					},
					{
						component: SendDpaPacket,
						path: 'send-raw',
					},
					{
						component: SendJsonRequest,
						path: 'send-json',
					},
					{
						component: TrConfiguration,
						path: 'tr-config/:address',
						props: (route) => {
							const address = Number.parseInt(route.params.address, 10);
							if (Number.isNaN(address)) {
								return 0;
							}
							return {address};
						},
					},
					{
						component: TrUpload,
						path: 'tr-upload',
					},
				]
			},
			{
				path: '/network',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: NetworkDisambiguation,
						path: '',
					},
					{
						component: EthernetInterfaces,
						path: 'ethernet',
					},
					{
						component: ConnectionForm,
						path: 'edit/:uuid',
						props: true,
					},
				]
			},
			{
				path: '/user',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						component: UserList,
						path: '',
					},
					{
						component: UserAdd,
						path: 'add',
					},
					{
						component: UserEdit,
						path: 'edit/:userId',
						props: (route) => {
							const userId = Number.parseInt(route.params.userId, 10);
							if (Number.isNaN(userId)) {
								return 0;
							}
							return {userId};
						},
					},
				]
			},
			{
				path: '/api-key',
				component: {
					render(c) {
						return c('router-view');
					}
				},
				children: [
					{
						path: '',
						component: ApiKeyList,
					},
					{
						component: ApiKeyForm,
						path: 'add',
					},
					{
						component: ApiKeyForm,
						path: 'edit/:keyId',
						props: (route) => {
							const keyId = Number.parseInt(route.params.keyId, 10);
							if (Number.isNaN(keyId)) {
								return 0;
							}
							return {keyId};
						},
					},
				],
			},
			{
				component: MainDisambiguation,
				path: '',
			},
			{
				path: '*',
				component: NotFound,
			},
		],
	},
];

const router = new VueRouter({
	mode: 'history',
	routes: routes
});

router.beforeEach((to, from, next) => {
	if (!to.path.startsWith('/install/') && to.name !== 'signIn' &&
		!store.getters['user/isLoggedIn']) {
		store.dispatch('user/signOut').then(() => {
			next('/sign/in');
		});
		return;
	}
	next();
});

export default router;
