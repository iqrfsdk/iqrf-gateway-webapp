<template>
	<div>
		<h1>{{ $t('core.apiKey.title') }}</h1>
		<CCard>
			<CCardHeader>
				<CButton
					color='success'
					size='sm'
					class='float-right'
					to='/api-key/add'
				>
					<CIcon :content='icons.add' size='sm' />
					{{ $t('table.actions.add') }}
				</CButton>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:fields='fields'
					:items='keys'
					:striped='true'
					:pagination='true'
					:items-per-page='20'
					:column-filter='true'
					:sorter='{external: false, resetable: true}'
				>
					<template #no-items-view='{}'>
						{{ $t('table.messages.noRecords') }}
					</template>
					<template #expiration='{item}'>
						<td v-if='item.expiration !== null'>
							{{ timeString(item) }}
						</td>
						<td v-else>
							never
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								size='sm'
								:to='"/api-key/edit/" + item.id'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='deleteKey = item.id'
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
			:show='deleteKey !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('core.apiKey.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('core.apiKey.messages.deletePrompt', {key: deleteKey}) }}
			<template #footer>
				<CButton
					color='danger'
					@click='deleteKey = null'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='removeKey'
				>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CDataTable, CIcon} from '@coreui/vue/src';
import {cilPencil, cilPlus, cilTrash} from '@coreui/icons';
import ApiKeyService from '../../services/ApiKeyService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {DateTime} from 'luxon';
import { Dictionary } from 'vue-router/types/router';
import { IField } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';

interface ApiKey {
	description: string
	expiration: string
	id: number
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CDataTable,
		CIcon
	},
	metaInfo: {
		title: 'core.apiKey.title'
	}
})

export default class ApiKeyList extends Vue {
	private dateFormat: Dictionary<string|boolean> = {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour12: false,
		hour: 'numeric',
		minute: 'numeric',
		second: 'numeric',
	}
	private deleteKey: number|null = null
	private fields: Array<IField> = [
		{
			key: 'id',
			label: this.$t('core.apiKey.form.id'),
		},
		{
			key: 'description',
			label: this.$t('core.apiKey.form.description'),
		},
		{
			key: 'expiration',
			label: this.$t('core.apiKey.form.expiration'),
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
	private keys: Array<ApiKey> = []

	created(): void {
		this.getKeys();
	}

	private getKeys(): Promise<void> {
		this.$store.commit('spinner/SHOW');
		return ApiKeyService.getApiKeys()
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				this.keys = response.data;
			})
			.catch((error: AxiosError) => FormErrorHandler.apiKeyError(error));
	}

	private removeKey(): void  {
		if (this.deleteKey === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		const key = this.deleteKey;
		this.deleteKey = null;
		ApiKeyService.deleteApiKey(key)
			.then(() => {
				this.getKeys().then(() => {
					this.$toast.success(this.$t('core.apiKey.messages.deleteSuccess', {key: key}).toString());
				});
			})
			.catch((error: AxiosError) => FormErrorHandler.apiKeyError(error));
	}

	private timeString(item: ApiKey): string {
		return DateTime.fromISO(item.expiration).toLocaleString(this.dateFormat);
	}

}
</script>
