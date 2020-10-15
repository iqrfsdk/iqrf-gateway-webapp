/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2019 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

import axios, {AxiosError, AxiosResponse} from 'axios';
import CoreuiVue from '@coreui/vue/src';
import * as Sentry from '@sentry/browser';
import {Vue as VueIntegration} from '@sentry/integrations/dist/vue';
import Vue from 'vue';
import VueMeta from 'vue-meta';
import VueNativeSock from 'vue-native-websocket';
import VueToast from 'vue-toast-notification';
import Clipboard from 'v-clipboard';
import InstallationService, {InstallationCheck} from './services/InstallationService';

import store from './store';
import router from './router';
import i18n from './i18n';

import App from './App.vue';

import './css/app.scss';
import 'vue-toast-notification/dist/theme-sugar.css';
import 'vue-datetime/dist/vue-datetime.css';

process.env.SETTINGS = process.env.SETTINGS || 'development';

if (process.env.NODE_ENV === 'production') {
	Sentry.init({
		dsn: 'https://435ee2b55f994e5f85e21a9ca93ea7a7@sentry.iqrf.org/5',
		integrations: [new VueIntegration({
			Vue: Vue,
			attachProps: true,
			logErrors: true,
		})],
	});
}

Vue.prototype.$appName = 'IQRF Gateway Webapp';

const isHttps: boolean = window.location.protocol === 'https:';
const hostname: string = window.location.hostname;
let port: string = window.location.port;
const isDev: boolean = port === '8081';
if (port !== '') {
	port = ':' + port;
}

const wsApi: string = (isHttps ? 'wss://' : 'ws://') + hostname + (isDev ? ':1338': port + '/ws');
//const wsApi = 'ws://tunnel.rehivetech.com:45117/ws';

Vue.use(VueNativeSock, wsApi, {
	store: store,
	format: 'json',
	reconnection: true,
});
Vue.use(CoreuiVue);
Vue.use(VueMeta);
Vue.use(VueToast,{
	position: 'top',
	duration: 10000
});
Vue.use(Clipboard);

axios.defaults.baseURL = '//' + hostname + (isDev ? ':8080' : port) + '/api/v0/';
axios.defaults.timeout = 30000; 

axios.interceptors.response.use(
	(response: AxiosResponse) => {
		return response;
	},
	(error: AxiosError) => {
		if (error.response === undefined) {
			// TODO: Add Network error toaster notification
			return Promise.reject(error);
		}
		if (error.response.status === 401) {
			store.dispatch('user/signOut')
				.then(() => {
					router.push('/sign/in');
				});
		}
		return Promise.reject(error);
	}
);

InstallationService.check()
	.then((check: InstallationCheck) => {
		const installUrl: boolean = router.currentRoute.path.startsWith('/install/');
		if (!check.allMigrationsExecuted) {
			router.push('/install/error/missing-migration');
			return;
		}
		if (!check.hasUsers && !installUrl) {
			router.push('/install/');
			return;
		}
		if (check.hasUsers && installUrl) {
			router.push('/sign/in/');
		}
	});

new Vue({
	router,
	store,
	i18n: i18n,
	render: h => h(App),
	metaInfo: {
		titleTemplate: (titleChunk: string): string => {
			return (titleChunk ? `${i18n.t(titleChunk).toString()} | ` : '') +
				i18n.t('core.title').toString();
		}
	},
}).$mount('#app');
