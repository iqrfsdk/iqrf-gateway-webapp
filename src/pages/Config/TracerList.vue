<template>
	<div>
		<h1>{{ $t('config.tracer.title') }}</h1>
		<CCard>
			<CCardHeader>
				<CButton
					color='success'
					size='sm'
					to='/config/tracer/add'
					class='float-right'
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
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								size='sm'
								:to='"/config/tracer/edit/" + item.instance'
							>
								<CIcon :content='getIcon("edit")' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='modals.currentInstance = item.instance'
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
			:show='modals.currentInstance !== ""'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.tracer.messages.removeTitle') }}
				</h5>
			</template>
			{{ $t('config.tracer.messages.removeItem', {instance: modals.currentInstance}) }}
			<template #footer>
				<CButton
					color='danger'
					@click='modals.currentInstance = ""'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='removeInstance'
				>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CDataTable, CIcon, CModal} from '@coreui/vue/src';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {IField} from '../../interfaces/coreui';
import {getCoreIcon} from '../../helpers/icons';
import { AxiosResponse } from 'axios';
import { Dictionary } from 'vue-router/types/router';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CDataTable,
		CIcon,
		CModal,
	},
	metaInfo: {
		title: 'config.tracer.title',
	},
})

export default class TracerList extends Vue {
	private componentName = 'shape::TraceFileService'
	private fields: Array<IField> = [
		{key: 'instance', label: this.$t('config.tracer.form.instance')},
		{key: 'path', label: this.$t('config.tracer.form.path')},
		{key: 'filename', label: this.$t('config.tracer.form.filename')},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		},
	]
	private instances: Array<unknown> = []
	private modals: Dictionary<string> = {
		currentInstance: ''
	}
	private addModal = false
	private editModal = false

	private created(): void {
		this.getConfig();
	}

	private getIcon(icon: string): string[]|void {
		return getCoreIcon(icon);
	}

	private getConfig(): Promise<AxiosResponse|void> {
		this.$store.commit('spinner/SHOW');
		return DaemonConfigurationService.getComponent(this.componentName)
			.then((response) => {
				this.$store.commit('spinner/HIDE');
				this.instances = response.data.instances;
			})
			.catch((error) => FormErrorHandler.configError(error));
	}
	
	private removeInstance(): void {
		this.$store.commit('spinner/SHOW');
		const instance = this.modals.currentInstance;
		this.modals.currentInstance = '';
		DaemonConfigurationService.deleteInstance(this.componentName, instance)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(
						this.$t('config.tracer.messages.removeSuccess', {instance: instance})
							.toString()
					);
				});
			})
			.catch((error) => {
				FormErrorHandler.configError(error);
			});
	}
}
</script>
