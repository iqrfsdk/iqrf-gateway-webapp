<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 
					v-if='$store.getters["user/getRole"] === "power"'
					class='float-left'
				>
					{{ $t('config.components.title') }}
				</h3>
				<h3 v-else class='float-left'>
					{{ $t('config.selectedComponents.title') }}
				</h3>
				<CButton
					v-if='$store.getters["user/getRole"] === "power"'
					color='success'
					size='sm'
					class='float-right'
					to='/config/daemon/component/add'
				>
					<CIcon :content='icons.add' size='sm' />
					{{ $t('table.actions.add') }}
				</CButton>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:fields='fields'
					:items='components'
					:column-filter='true'
					:items-per-page='20'
					:pagination='true'
					:striped='true'
					:sorter='{ external: false, resetable: true }'
				>
					<template #no-items-view='{}'>
						{{ $t('table.messages.noRecords') }}
					</template>
					<template #enabled='{item}'>
						<td>
							<CDropdown
								:color='item.enabled ? "success" : "danger"'
								:toggler-text='item.enabled ? $t("config.components.form.enabled") : $t("config.components.form.disabled")'
								size='sm'
							>
								<CDropdownItem @click='changeEnabled(item, true)'>
									{{ $t('config.components.form.enabled') }}
								</CDropdownItem>
								<CDropdownItem @click='changeEnabled(item, false)'>
									{{ $t('config.components.form.disabled') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								size='sm'
								:to='"/config/daemon/component/edit/" + item.name'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='component = item.name'
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
			:show='component !== ""'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.components.form.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('config.components.form.messages.deletePrompt', {component: component}) }}
			<template #footer>
				<CButton color='danger' @click='component = ""'>
					{{ $t('forms.no') }}
				</CButton>
				<CButton color='success' @click='removeComponent'>
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
import { IField } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';
import { Dictionary } from 'vue-router/types/router';

interface ComponentItem {
	enabled: boolean
	libraryName: string
	libraryPath: string
	name: string
	startLevel: number
}

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

export default class ComponentList extends Vue {
	private component = ''
	private components: Array<ComponentItem>|null = null
	private fields: Array<IField> = [
		{
			key: 'name',
			label: this.$t('config.components.form.name'),
		},
		{
			key: 'startlevel',
			label: this.$t('config.components.form.startLevel'),
		},
		{
			key: 'libraryPath',
			label: this.$t('config.components.form.libraryPath'),
		},
		{
			key: 'libraryName',
			label: this.$t('config.components.form.libraryName'),
		},
		{
			key: 'enabled',
			label: this.$t('config.components.form.enabled'),
			filter: false,
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		}
	]
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		edit: cilPencil,
		remove: cilTrash
	}
	
	get pageTitle(): string {
		return this.$store.getters['user/getRole'] === 'power' ? 
			this.$t('config.components.title').toString() : this.$t('config.selectedComponents.title').toString();
	}

	created(): void {
		this.getConfig();
	}

	mounted(): void {
		const titleEl = document.getElementById('title');
		if (titleEl !== null) {
			titleEl.innerText = this.pageTitle;
		}
	}

	private getConfig(): Promise<AxiosResponse|void> {
		this.$store.commit('spinner/SHOW');
		return DaemonConfigurationService.getComponent('')
			.then((response) => {
				this.$store.commit('spinner/HIDE');
				if (this.$store.getters['user/getRole'] === 'power') {
					this.components = response.data.components;
				} else {
					const whitelistedComponents = ['iqrf::IqrfCdc', 'iqrf::IqrfSpi', 'iqrf::IqrfUart'];
					this.components = response.data.components.filter((component: ComponentItem) => {
						if (whitelistedComponents.includes(component.name)) {
							return component;
						}
					});
				}
			})
			.catch((error) => FormErrorHandler.configError(error));
	}

	private multipleIqrfInterfaces(enabledComponent: string): boolean|void {
		if (this.components === null) {
			return;
		}
		let interfaceCount = 0;
		this.components.forEach((item: ComponentItem) => {
			if (item.name === 'iqrf::IqrfCdc' || item.name === 'iqrf::IqrfSpi' || item.name === 'iqrf::IqrfUart') {
				if (item.enabled && item.name !== enabledComponent) {
					interfaceCount += 1;
				}
			}
		});
		if (interfaceCount > 0) {
			return false;
		}
		return true;
	}

	private changeEnabled(component: ComponentItem, enabled: boolean): void {
		if (component.enabled !== enabled) {
			if (!this.multipleIqrfInterfaces(component.name)) {
				this.$toast.info(
					this.$t('config.components.form.messages.multipleInterfaces').toString()
				);
				return;
			}
			component.enabled = enabled;
			DaemonConfigurationService.updateComponent(component.name, component)
				.then(() => {
					this.getConfig().then(() => {
						this.$toast.success(this.$t('config.components.form.messages.editSuccess', {component: component.name}).toString());
						if (component.name === 'iqrf::IqrfCdc' || component.name === 'iqrf::IqrfSpi' || component.name === 'iqrf::IqrfUart') {
							this.$emit('update-interface');
						}
					});
				})
				.catch((error: AxiosError) => FormErrorHandler.configError(error));
		}
	}
	
	private removeComponent(): void {
		this.$store.commit('spinner/SHOW');
		const component = this.component;
		this.component = '';
		DaemonConfigurationService.deleteComponent(component)
			.then(() => {
				this.getConfig().then(() => {
					this.$toast.success(this.$t('config.components.form.messages.deleteSuccess', {component: component}).toString());
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.configError(error));
	}

}
</script>
