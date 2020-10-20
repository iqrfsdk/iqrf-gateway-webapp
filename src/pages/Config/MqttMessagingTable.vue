<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 class='float-left'>
					{{ $t('config.mqtt.title') }}
				</h3>
				<CButton
					color='success'
					to='/config/mqtt/add'
					size='sm'
					class='float-right'
				>
					<CIcon :content='icons.add' size='sm' />
					{{ $t('table.actions.add') }}
				</CButton>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:items='instances'
					:fields='fields'
					:column-filter='true'
					:items-per-page='20'
					:pagination='true'
					:striped='true'
					:sorter='{ external: false, resetable: true }'
				>
					<template #no-items-view='{}'>
						{{ $t('table.messages.noRecords') }}
					</template>
					<template #EnabledSSL='{item}'>
						<td>
							<CDropdown
								:color='item.EnabledSSL ? "success" : "danger"'
								:toggler-text='$t("table.enabled." + item.EnabledSSL)'
								placement='top-start'
								size='sm'
							>
								<CDropdownItem @click='changeEnabledSSL(item, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeEnabledSSL(item, false)'>
									{{ $t('table.enabled.false') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #acceptAsyncMsg='{item}'>
						<td>
							<CDropdown
								:color='item.acceptAsyncMsg ? "success" : "danger"'
								:toggler-text='$t("table.enabled." + item.acceptAsyncMsg)'
								placement='top-start'
								size='sm'
							>
								<CDropdownItem @click='changeAcceptAsyncMsg(item, true)'>
									{{ $t('table.enabled.true') }}
								</CDropdownItem>
								<CDropdownItem @click='changeAcceptAsyncMsg(item, false)'>
									{{ $t('table.enabled.false') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								:to='"/config/mqtt/edit/" + item.instance'
								size='sm'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton 
								color='danger' 
								size='sm'
								@click='confirmDelete(item)'
							>
								<CIcon :content='icons.delete' size='sm' />
								{{ $t('table.actions.delete') }}
							</CButton>
						</td>
					</template>
				</CDataTable>
			</CCardBody>
		</CCard>
		<CModal
			color='danger'
			:show='deleteInstance !== ""'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.mqtt.messages.delete.confirmTitle') }}
				</h5>
				<CButtonClose class='text-white' @click='deleteInstance = ""' />
			</template>
			<span v-if='deleteInstance !== ""'>
				{{ $t('config.mqtt.messages.delete.confirm', {instance: deleteInstance}) }}
			</span>
			<template #footer>
				<CButton color='danger' @click='deleteInstance = ""'>
					{{ $t('forms.no') }}
				</CButton>
				<CButton color='success' @click='performDelete'>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {
	CButton,
	CButtonClose,
	CCard,
	CCardBody,
	CCardHeader,
	CDataTable,
	CDropdown,
	CDropdownItem,
	CIcon,
	CModal
} from '@coreui/vue/src';
import {cilPencil, cilPlus, cilTrash} from '@coreui/icons';
import DaemonConfigurationService
	from '../../services/DaemonConfigurationService';
import { Dictionary } from 'vue-router/types/router';
import { IField } from '../../interfaces/coreui';
import { MqttInstance } from '../../interfaces/messagingInterfaces';
import { AxiosResponse } from 'axios';

@Component({
	components: {
		CButton,
		CButtonClose,
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

export default class MqttMessagingTable extends Vue {
	private componentName = 'iqrf::MqttMessaging'
	private deleteInstance = ''
	private fields: Array<IField> = [
		{
			key: 'instance',
			label: this.$t('config.mqtt.form.instance'),
		},
		{
			key: 'BrokerAddr',
			label: this.$t('config.mqtt.form.BrokerAddr'),
		},
		{
			key: 'ClientId',
			label: this.$t('config.mqtt.form.ClientId'),
		},
		{
			key: 'TopicRequest',
			label: this.$t('config.mqtt.form.TopicRequest'),
		},
		{
			key: 'TopicResponse',
			label: this.$t('config.mqtt.form.TopicResponse'),
		},
		{
			key: 'EnabledSSL',
			label: this.$t('config.mqtt.form.EnabledSSL'),
			filter: false,
		},
		{
			key: 'acceptAsyncMsg',
			label: this.$t('config.mqtt.form.acceptAsyncMsg'),
			filter: false,
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			sorter: false,
			filter: false,
		},
	]
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		delete: cilTrash,
		edit: cilPencil,
	}
	private instances: Array<MqttInstance> = []

	mounted(): void {
		this.$store.commit('spinner/SHOW');
		this.getInstances();
	}

	private confirmDelete(instance: MqttInstance): void {
		this.deleteInstance = instance.instance;
	}

	private changeAcceptAsyncMsg(instance: MqttInstance, acceptAsyncMsg: boolean): void {
		this.edit(instance, {acceptAsyncMsg: acceptAsyncMsg});
	}

	private changeEnabledSSL(instance: MqttInstance, enabledSsl: boolean) : void{
		this.edit(instance, {EnabledSSL: enabledSsl});
	}

	private edit(instance: MqttInstance, newSettings: Dictionary<boolean>): Promise<AxiosResponse|void> {
		this.$store.commit('spinner/SHOW');
		let settings = {
			...instance,
			...newSettings,
		};
		return DaemonConfigurationService.updateInstance(this.componentName, settings.instance, settings)
			.then(() => {
				this.getInstances().then(() => {
					this.$toast.success(
						this.$t('config.mqtt.messages.edit.success', {instance: settings.instance})
							.toString()
					);
				});
			});
	}

	private getInstances(): Promise<AxiosResponse|void> {
		return DaemonConfigurationService.getComponent(this.componentName)
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.instances = response.data.instances;
			})
			.catch(() => this.$store.commit('spinner/HIDE'));
	}

	private performDelete(): void {
		this.$store.commit('spinner/SHOW');
		const instance = this.deleteInstance;
		this.deleteInstance = '';
		DaemonConfigurationService.deleteInstance(this.componentName, instance)
			.then(() => {
				this.getInstances().then(() => {
					this.$toast.success(
						this.$t('config.mqtt.messages.delete.success', {instance: instance})
							.toString()
					);
				});
			})
			.catch(() => this.$store.commit('spinner/HIDE'));
	}
}
</script>
