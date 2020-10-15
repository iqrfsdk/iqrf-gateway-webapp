<template>
	<div>
		<CCard>
			<CCardHeader>
				{{ $t('config.websocket.service.title') }}
				<CButton
					color='success'
					size='sm'
					class='float-right'
					to='/config/websocket/add-service'
				>
					<CIcon :content='getIcon("add")' size='sm' />
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
								:to='"/config/websocket/edit-service/" + item.instance'
							>
								<CIcon :content='getIcon("edit")' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='service = item.instance'
							>
								<CIcon :content='getIcon("remove")' size='sm' />
								{{ $t('table.actions.delete') }}
							</CButton>
						</td>
					</template>
				</CDataTable>
			</CCardBody>
		</CCard>
		<CModal
			color='danger'
			:show='service !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.websocket.service.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('config.websocket.service.messages.deletePrompt', {service: service}) }}
			<template #footer>
				<CButton
					color='danger'
					@click='service = null'
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
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {getCoreIcon} from '../../helpers/icons';
import {IField} from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';
import { WsService } from '../../interfaces/messagingInterfaces';

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

export default class WebsocketServiceList extends Vue {
	private componentName = 'shape::WebsocketCppService'
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
	private instances: Array<WsService> = []
	private service: string|null = null

	created(): void {
		this.getConfig();
	}

	private getIcon(icon: string): string[]|void {
		return getCoreIcon(icon);
	}

	private getConfig(): Promise<void> {
		this.$store.commit('spinner/SHOW');
		return DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.instances = response.data.instances;
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

	private changeAccept(service, setting): void {
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

	private removeService(): void {
		if (this.service === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		const service = this.service;
		this.service = null;
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
