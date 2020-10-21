<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 class='float-left'>
					{{ $t('config.websocket.service.title') }}
				</h3>
				<CButton
					color='success'
					size='sm'
					class='float-right'
					to='/config/daemon/websocket/add-service'
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
								<CDropdownItem @click='changeAccept(item, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeAccept(item, false)'>
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
								:to='"/config/daemon/websocket/edit-service/" + item.instance'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='deleteService = item.instance'
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
			:show='deleteService !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.websocket.service.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('config.websocket.service.messages.deletePrompt', {service: deleteService}) }}
			<template #footer>
				<CButton
					color='danger'
					@click='deleteService = null'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='removeService'
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
import {IField} from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';
import { WsService } from '../../interfaces/messagingInterfaces';
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

/**
 * Websocket service list card for normal user
 */
export default class WebsocketServiceList extends Vue {
	/**
	 * @constant {string} componentName Websocket service component name
	 */
	private componentName = 'shape::WebsocketCppService'

	/**
	 * @var {string|null} deleteService Websocket service instance used in remove modal
	 */
	private deleteService: string|null = null

	/**
	 * @constant {Array<IField>} fields CoreUI datatable columns
	 */
	private fields: Array<IField> = [
		{
			key: 'instance',
			label: this.$t('config.websocket.form.instance'),
		},
		{
			key: 'WebsocketPort',
			label: this.$t('config.websocket.form.WebsocketPort'),
		},
		{
			key: 'acceptOnlyLocalhost',
			label: this.$t('config.websocket.form.acceptOnlyLocalhost'),
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		},
	]

	/**
	 * @constant {Dictionary<Array<string>>} icons Dictionary of CoreUI icons
	 */
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		edit: cilPencil,
		remove: cilTrash
	}

	/**
	 * @var {Array<WsService>} instances Array of Websocket service instances
	 */
	private instances: Array<WsService> = []

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		this.getConfig();
	}

	/**
	 * Retrieves instances of Websocket service component
	 */
	private getConfig(): Promise<void> {
		this.$store.commit('spinner/SHOW');
		return DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.instances = response.data.instances;
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	/**
	 * Updates accepted message source of Websocket service component instance
	 * @param {WsService} service Websocket service instance
	 * @param {boolean} setting new setting
	 */
	private changeAccept(service: WsService, setting: boolean): void {
		if (service.acceptOnlyLocalhost !== setting) {
			service.acceptOnlyLocalhost = setting;
			DaemonConfigurationService.updateInstance(this.componentName, service.instance, service)
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
	}

	/**
	 * Removes an existing instance of Websocket service component
	 */
	private removeService(): void {
		if (this.deleteService === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		const service = this.deleteService;
		this.deleteService = null;
		DaemonConfigurationService.deleteInstance(this.componentName, service)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.websocket.service.messages.deleteSuccess', {service: service})
							.toString()
					);
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}
}
</script>
