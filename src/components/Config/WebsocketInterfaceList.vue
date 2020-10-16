<template>
	<div>
		<CCard>
			<CCardHeader>
				<CButton
					color='success'
					size='sm'
					class='float-right'
					to='/config/websocket/add'
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
					<template #acceptAsyncMsg='{item}'>
						<td>
							<CDropdown
								:color='item.acceptAsyncMsg ? "success": "danger"'
								:toggler-text='$t("table.enabled." + item.acceptAsyncMsg)'
								size='sm'
							>
								<CDropdownItem @click='changeAcceptAsyncMsg(item.messaging, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeAcceptAsyncMsg(item.messaging, false)'>
									{{ $t('table.enabled.false') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #acceptOnlyLocalhost='{item}'>
						<td>
							<CDropdown
								:color='item.acceptOnlyLocalhost ? "success": "danger"'
								:toggler-text='$t("table.enabled." + item.acceptOnlyLocalhost)'
								size='sm'
							>
								<CDropdownItem @click='changeAcceptOnlyLocalhost(item.service, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeAcceptOnlyLocalhost(item.service, false)'>
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
								:to='"/config/websocket/edit/" + item.instanceMessaging'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='deleteInstance = {messaging: item.messaging.instance, service: item.service.instance}'
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
					{{ $t('config.websocket.messages.delete.confirmTitle') }}
				</h5>
			</template>
			<div v-if='deleteInstance !== null'>
				{{ $t('config.websocket.messages.delete.confirm', {instance: deleteInstance.messaging}) }}
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
import {cilPlus, cilPencil, cilTrash} from '@coreui/icons';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import { AxiosError, AxiosResponse } from 'axios';
import {IField} from '../../interfaces/coreui';
import {WsInterface, ModalInstance, WsService, WsMessaging} from '../../interfaces/messagingInterfaces';
import {Dictionary} from 'vue-router/types/router';

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

export default class WebsocketInterfaceList extends Vue {
	private componentNames: ModalInstance = {
		messaging: 'iqrf::WebsocketMessaging',
		service: 'shape::WebsocketCppService',
	}
	private deleteInstance: ModalInstance|null = null
	private fields: Array<IField> = [
		{
			key: 'instanceMessaging',
			label: this.$t('config.websocket.form.instance'),
		},
		{
			key: 'port',
			label: this.$t('config.websocket.form.WebsocketPort'),
		},
		{
			key: 'acceptAsyncMsg',
			label: this.$t('config.websocket.form.acceptAsyncMsg'),
			filter: false,
		},
		{
			key: 'acceptOnlyLocalhost',
			label: this.$t('config.websocket.form.acceptOnlyLocalhost'),
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
		remove: cilTrash
	}
	private instances: Array<WsInterface> = [];

	created(): void {
		this.$store.commit('spinner/SHOW');
		this.getConfig();
	}

	private getConfig(): Promise<void> {
		this.instances = [];
		return Promise.all([
			DaemonConfigurationService.getComponent(this.componentNames.messaging),
			DaemonConfigurationService.getComponent(this.componentNames.service),
		])
			.then((responses: Array<AxiosResponse>) => {
				const messagings = responses[0].data.instances;
				const services = responses[1].data.instances;
				for (const messaging of messagings) {
					if (messaging.RequiredInterfaces === undefined ||
							messaging.RequiredInterfaces === [] ||
							messaging.RequiredInterfaces[0].name !== 'shape::IWebsocketService' ||
							messaging.RequiredInterfaces[0].target.instance === undefined) {
						continue;
					}
					const serviceInstance = messaging.RequiredInterfaces[0].target.instance;
					for (const service of services) {
						if (service.instance !== serviceInstance) {
							continue;
						}
						this.instances.push({
							messaging: messaging,
							service: service,
							instanceMessaging: messaging.instance,
							instanceService: service.instance,
							acceptAsyncMsg: messaging.acceptAsyncMsg,
							port: service.WebsocketPort,
							acceptOnlyLocalhost: service.acceptOnlyLocalhost,
						});
					}
				}
				this.$store.commit('spinner/HIDE');
			});
		// TODO: add error message
	}

	private changeAcceptOnlyLocalhost(service: WsService, setting: boolean): void {
		this.$store.commit('spinner/SHOW');
		service.acceptOnlyLocalhost = setting;
		DaemonConfigurationService.updateInstance(this.componentNames.service, service.instance, service)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.websocket.service.messages.editSuccess', {service: service.instance})
							.toString()
					);
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	private changeAcceptAsyncMsg(instance: WsMessaging, setting: boolean): void {
		this.$store.commit('spinner/SHOW');
		instance.acceptAsyncMsg = setting;
		DaemonConfigurationService.updateInstance(this.componentNames.messaging, instance.instance, instance)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.websocket.messaging.messages.editSuccess', {messaging: instance.instance})
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
			DaemonConfigurationService.deleteInstance(this.componentNames.messaging, this.deleteInstance.messaging),
			DaemonConfigurationService.deleteInstance(this.componentNames.service, this.deleteInstance.service),
		])
			.then(() => {
				this.$toast.success(
					this.$t('config.websocket.messages.delete.success', {instance: this.deleteInstance?.messaging})
						.toString()
				);
				this.deleteInstance = null;
			});
		// TODO: add error message
	}
}
</script>

