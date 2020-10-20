<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 class='float-left'>
					{{ $t('config.monitor.title') }}
				</h3>
				<CButton
					color='success'
					size='sm'
					class='float-right'
					to='/config/monitor/add'
				>
					<CIcon :content='icons.add' size='sm' />
					{{ $t('table.actions.add') }}
				</CButton>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:fields='fields'
					:items='instances'
					:column-filter='true'
					:items-per-page='20'
					:pagination='true'
					:striped='true'
					:sorter='{ external: false, resetable: true }'
				>
					<template #no-items-view='{}'>
						{{ $t('table.messages.noRecords') }}
					</template>
					<template #acceptOnlyLocalhost='{item}'>
						<td>
							<CDropdown
								:color='item.acceptOnlyLocalhost ? "success": "danger"'
								:toggler-text='$t("table.enabled." + item.acceptOnlyLocalhost)'
								size='sm'
							>
								<CDropdownItem @click='changeAcceptOnlyLocalhost(item.webSocket, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeAcceptOnlyLocalhost(item.webSocket, false)'>
									{{ $t('table.enabled.false') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								size='sm'
								:to='"/config/monitor/edit/" + item.monitor.instance'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='deleteInstance = {monitor: item.monitor.instance, webSocket: item.webSocket.instance}'
							>
								<CIcon :content='icons.remove' size='sm' />
								{{ $t('table.actions.delete') }}
							</CButton>
						</td>
					</template>
				</CDataTable>
			</CCardBody>
		</CCard>
		<CModal
			color='danger'
			:show='deleteInstance !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.monitor.messages.delete.confirmTitle') }}
				</h5>
			</template>
			<div v-if='deleteInstance !== null'>
				{{ $t('config.monitor.messages.delete.confirm', {instance: deleteInstance.monitor}) }}
			</div>
			<template #footer>
				<CButton 
					color='danger'
					@click='deleteInstance = null'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='removeInterface()'
				>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CDataTable, CDropdown, CDropdownItem, CIcon, CModal} from '@coreui/vue/src';
import {cilPencil, cilPlus, cilTrash} from '@coreui/icons';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import { Dictionary } from 'vue-router/types/router';
import { IField } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CDataTable,
		CDropdown,
		CDropdownItem,
		CIcon,
		CModal,
	}
})

export default class MonitorList extends Vue {
	private componentNames: Dictionary<string> = {
		monitor: 'iqrf::MonitorService',
		webSocket: 'shape::WebsocketCppService'
	}
	private deleteInstance: Dictionary<string>|null = null
	private fields: Array<IField> =  [
		{
			key: 'instance',
			label: this.$t('config.monitor.form.instance'),
		},
		{
			key: 'reportPeriod',
			label: this.$t('config.monitor.form.reportPeriod'),
		},
		{
			key: 'port',
			label: this.$t('config.monitor.form.WebsocketPort'),
		},
		{
			key: 'acceptOnlyLocalhost',
			label: this.$t('config.monitor.form.acceptOnlyLocalhost'),
			filter: false,
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		},
	]
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		edit: cilPencil,
		remove: cilTrash,
	}
	private instances: Array<unknown>|null = null

	mounted(): void {
		this.$store.commit('spinner/SHOW');
		this.getConfig();
	}

	private getConfig(): Promise<AxiosResponse|void> {
		this.instances = [];
		return Promise.all([
			DaemonConfigurationService.getComponent(this.componentNames.monitor),
			DaemonConfigurationService.getComponent(this.componentNames.webSocket),
		])
			.then((responses: Array<AxiosResponse>) => {
				const monitors = responses[0].data.instances;
				const webSockets = responses[1].data.instances;
				for (const monitor of monitors) {
					if (monitor.RequiredInterfaces === undefined ||
							monitor.RequiredInterfaces === [] ||
							monitor.RequiredInterfaces[0].name !== 'shape::IWebsocketService' ||
							monitor.RequiredInterfaces[0].target.instance === undefined) {
						continue;
					}
					const webSocketInstance = monitor.RequiredInterfaces[0].target.instance;
					for (const webSocket of webSockets) {
						if (webSocket.instance !== webSocketInstance) {
							continue;
						}
						if (this.instances === null) {
							return;
						}
						this.instances.push({
							monitor: monitor,
							webSocket: webSocket,
							instance: monitor.instance,
							reportPeriod: monitor.reportPeriod,
							acceptAsyncMsg: monitor.acceptAsyncMsg,
							port: webSocket.WebsocketPort,
							acceptOnlyLocalhost: webSocket.acceptOnlyLocalhost,
						});
					}
				}
				this.$store.commit('spinner/HIDE');
			});
		// TODO: add error message
	}

	private changeAcceptOnlyLocalhost(service, setting: boolean): void {
		this.$store.commit('spinner/SHOW');
		service.acceptOnlyLocalhost = setting;
		DaemonConfigurationService.updateInstance(this.componentNames.webSocket, service.instance, service)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.monitor.service.messages.editSuccess', {service: service.instance})
							.toString()
					);
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	private removeInterface(): void {
		if (this.deleteInstance === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		Promise.all([
			DaemonConfigurationService.deleteInstance(this.componentNames.monitor, this.deleteInstance.monitor),
			DaemonConfigurationService.deleteInstance(this.componentNames.webSocket, this.deleteInstance.webSocket),
		])
			.then(() => {
				if (this.deleteInstance === null) {
					return;
				}
				const deleteInstance = this.deleteInstance;
				this.deleteInstance = null;
				this.getConfig()
					.then(() => this.$toast.success(
						this.$t('config.monitor.messages.delete.success', {instance: deleteInstance.monitor})
							.toString())
					);
			});
		// TODO: add error message
	}
}
</script>
