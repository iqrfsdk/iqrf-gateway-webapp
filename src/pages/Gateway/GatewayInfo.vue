<template>
	<div>
		<h1>{{ $t('gateway.info.title') }}</h1>
		<CCard body-wrapper>
			<div class='table-responsive'>
				<table v-if='info !== null' class='table table-striped'>
					<tbody>
						<tr>
							<th>{{ $t('gateway.info.board') }}</th>
							<td>{{ info.board }}</td>
						</tr>
						<tr v-if='info.gwId'>
							<th>{{ $t('gateway.info.gwId') }}</th>
							<td>{{ info.gwId }}</td>
						</tr>
						<tr v-if='info.pixla'>
							<th>
								<a href='https://www.pixla.online/'>
									{{ $t('gateway.info.gwmonId') }}
								</a>
							</th>
							<td>{{ info.pixla }}</td>
						</tr>
						<tr v-if='info.versions.controller'>
							<th>{{ $t('gateway.info.version.iqrfGatewayController') }}</th>
							<td>{{ info.versions.controller }}</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.version.iqrfGatewayDaemon') }}</th>
							<td>{{ info.versions.daemon }}</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.version.iqrfGatewayWebapp') }}</th>
							<td>{{ info.versions.webapp }}</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.hostname') }}</th>
							<td>{{ info.hostname }}</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.addresses.ip') }}</th>
							<td>
								<span v-for='{iface, addresses} of getIpAddresses' :key='iface'>
									<strong>{{ iface }}: </strong> {{ addresses }}<br>
								</span>
							</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.addresses.mac') }}</th>
							<td>
								<span v-for='{iface, address} of getMacAddresses' :key='iface'>
									<strong>{{ iface }}: </strong> {{ address }}<br>
								</span>
							</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.usages.disks') }}</th>
							<td>
								<div v-for='usage of info.diskUsages' :key='usage.fsName'>
									<strong>{{ usage.fsName }} ({{ usage.fsType }}):</strong>
									<resource-usage :usage='usage' /><br>
								</div>
							</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.usages.memory') }}</th>
							<td><resource-usage :usage='info.memoryUsage' /></td>
						</tr>
						<tr v-if='info.swapUsage'>
							<th>{{ $t('gateway.info.usages.swap') }}</th>
							<td><resource-usage :usage='info.swapUsage' /></td>
						</tr>
						<tr v-if='showCoordinator'>
							<th>{{ $t('gateway.info.tr.title') }}</th>
							<td>
								<coordinator-info />
							</td>
						</tr>
						<tr>
							<th>{{ $t('gateway.info.gwMode') }}</th>
							<td>
								<DaemonModeInfo @notify-cinfo='showCoordinator = true' />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<CButton color='primary' @click='downloadDiagnostics()'>
				{{ $t('gateway.diagnostics.download') }}
			</CButton>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard} from '@coreui/vue/src';
import CoordinatorInfo from '../../components/Gateway/CoordinatorInfo.vue';
import DaemonModeInfo from '../../components/Gateway/DaemonModeInfo.vue';
import ResourceUsage from '../../components/Gateway/ResourceUsage.vue';
import GatewayService from '../../services/GatewayService';
import {fileDownloader} from '../../helpers/fileDownloader';
import {IGatewayInfo, IpAddress, MacAddress} from '../../interfaces/gatewayInfo';
import { AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CCard,
		CoordinatorInfo,
		DaemonModeInfo,
		ResourceUsage
	},
	metaInfo: {
		title: 'gateway.info.title',
	},
})

export default class GatewayInfo extends Vue {
	private info: IGatewayInfo|null = null
	private mode = 'unknown'
	private showCoordinator = false
	
	get getIpAddresses(): Array<IpAddress> {
		if (this.info === null) {
			return [];
		}
		let addresses: Array<IpAddress> = [];
		for (const nInterface of this.info.interfaces) {
			if (nInterface.ipAddresses === null) {
				continue;
			}
			addresses.push({
				iface: nInterface.name,
				addresses: nInterface.ipAddresses.join(', ')
			});
		}
		return addresses;
	}

	get getMacAddresses(): Array<MacAddress> {
		if (this.info === null) {
			return [];
		}
		let addresses: Array<MacAddress> = [];
		for (const nInterface of this.info.interfaces) {
			if (nInterface.macAddress === null) {
				continue;
			}
			addresses.push({
				iface: nInterface.name,
				address: nInterface.macAddress
			});
		}
		return addresses;
	}

	private created(): void {
		this.$store.commit('spinner/SHOW');
		GatewayService.getInfo()
			.then(
				(response: AxiosResponse) => {
					this.info = response.data;
					this.$store.commit('spinner/HIDE');
				}
			)
			.catch(() => this.$store.commit('spinner/HIDE'));
	}

	private downloadDiagnostics(): void {
		this.$store.commit('spinner/SHOW');
		GatewayService.getDiagnosticsArchive().then(
			(response: AxiosResponse) => {
				const file = fileDownloader(response, 'application/zip', 'iqrf-gateway-diagnostics.zip');
				this.$store.commit('spinner/HIDE');
				file.click();
			}
		).catch(() => (this.$store.commit('spinner/HIDE')));
	}
}
</script>
