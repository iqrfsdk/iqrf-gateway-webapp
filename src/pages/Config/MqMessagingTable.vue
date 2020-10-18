<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 class='float-left'>
					{{ $t('config.mq.title') }}
				</h3>
				<CButton
					color='success'
					to='/config/mq/add'
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
								:to='"/config/mq/edit/" + item.instance'
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
					{{ $t('config.mq.messages.delete.confirmTitle') }}
				</h5>
				<CButtonClose class='text-white' @click='deleteInstance = ""' />
			</template>
			<span v-if='deleteInstance !== ""'>
				{{ $t('config.mq.messages.delete.confirm', {instance: deleteInstance}) }}
			</span>
			<template #footer>
				<CButton 
					color='danger'
					@click='deleteInstance = ""'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='performDelete'
				>
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
import { MqInstance } from '../../interfaces/messagingInterfaces';
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
	},
	metaInfo: {
		title: 'config.mq.title',
	}
})

export default class MqMessagingTable extends Vue {
	private componentName = 'iqrf::MqMessaging'
	private deleteInstance = ''
	private fields: Array<IField> = [
		{
			key: 'instance',
			label: this.$t('config.mq.form.instance'),
		},
		{
			key: 'LocalMqName',
			label: this.$t('config.mq.form.LocalMqName'),
		},
		{
			key: 'RemoteMqName',
			label: this.$t('config.mq.form.RemoteMqName'),
		},
		{
			key: 'acceptAsyncMsg',
			label: this.$t('config.mq.form.acceptAsyncMsg'),
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
	private instances: Array<MqInstance> = []

	mounted(): void {
		this.$store.commit('spinner/SHOW');
		this.getInstances();
	}
	
	private confirmDelete(instance): void {
		this.deleteInstance = instance.instance;
	}

	private changeAcceptAsyncMsg(instance: MqInstance, acceptAsyncMsg: boolean): Promise<AxiosResponse|void> {
		this.$store.commit('spinner/SHOW');
		instance.acceptAsyncMsg = acceptAsyncMsg;
		return DaemonConfigurationService.updateInstance(this.componentName, instance.instance, instance)
			.then(() => {
				this.getInstances().then(() => {
					this.$toast.success(
						this.$t('config.mq.messages.edit.success', {instance: instance.instance})
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
						this.$t('config.mq.messages.delete.success', {instance: instance})
							.toString()
					);
				});
			})
			.catch(() => this.$store.commit('spinner/HIDE'));
	}	
}
</script>
