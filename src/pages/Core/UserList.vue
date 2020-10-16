<template>
	<div>
		<h1>{{ $t('core.user.title') }}</h1>
		<CCard>
			<CCardHeader>
				<CButton
					color='success'
					to='/user/add/'
					size='sm'
					class='float-right'
				>
					<CIcon :content='icons.add' size='sm' />
					{{ $t('table.actions.add') }}
				</CButton>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:items='users'
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
					<template #role='{item}'>
						<td>
							<CDropdown
								color='success'
								:toggler-text='$t("core.user.roles." + item.role)'
								size='sm'
							>
								<CDropdownItem @click='changeRole(item, "normal")'>
									{{ $t('core.user.roles.normal') }}
								</CDropdownItem>
								<CDropdownItem @click='changeRole(item, "power")'>
									{{ $t('core.user.roles.power') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #language='{item}'>
						<td>
							<CDropdown
								color='success'
								:toggler-text='$t("core.user.languages." + item.language)'
								size='sm'
							>
								<CDropdownItem @click='changeLanguage(item, "en")'>
									{{ $t('core.user.languages.en') }}
								</CDropdownItem>
							</CDropdown>
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								v-if='$store.getters["user/getRole"] === "power" || $store.getters["user/getName"] === item.username'
								color='primary'
								:to='"/user/edit/" + item.id'
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
			:show='deleteUser !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('core.user.messages.delete.confirmTitle') }}
				</h5>
				<CButtonClose class='text-white' @click='deleteUser = null' />
			</template>
			<span v-if='deleteUser !== null'>
				{{ $t('core.user.messages.delete.confirm', {username: deleteUser.username}) }}
			</span>
			<template #footer>
				<CButton
					color='danger'
					@click='deleteUser = null'
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
import UserService from '../../services/UserService';
import { Dictionary } from 'vue-router/types/router';
import { IField } from '../../interfaces/coreui';

interface User {
	id?: number
	language: string
	role: string
	username: string
}

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
		title: 'core.user.title',
	},
})

export default class UserList extends Vue {
	private deleteUser: User|null = null
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		delete: cilTrash,
		edit: cilPencil,
	}
	private fields: Array<IField> = []
	private users: Array<User> = []
	
	created(): void {
		if (this.$store.getters['user/getRole'] === 'normal') {
			this.fields = [
				{
					key: 'username',
					label: this.$t('core.user.username'),
				},
				{
					key: 'actions',
					label: this.$t('table.actions.title'),
					sorter: false,
					filter: false,
				},
			];
		} else {
			this.fields = [
				{
					key: 'id',
					label: this.$t('core.user.id'),
				},
				{
					key: 'username',
					label: this.$t('core.user.username'),
				},
				{
					key: 'role',
					label: this.$t('core.user.role'),
				},
				{
					key: 'language',
					label: this.$t('core.user.language'),
				},
				{
					key: 'actions',
					label: this.$t('table.actions.title'),
					sorter: false,
					filter: false,
				},
			];
		}
		this.$store.commit('spinner/SHOW');
		this.getUsers();
	}

	private getUsers() {
		return UserService.list()
			.then((response) => {
				this.$store.commit('spinner/HIDE');
				this.users = response.data;
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
			});
	}

	private changeRole(user: User, newRole: string): void {
		if (user.role === newRole) {
			return;
		}
		this.edit(user, {role: newRole});
	}

	private changeLanguage(user: User, newLanguage: string): void {
		if (user.language === newLanguage) {
			return;
		}
		this.edit(user, {language: newLanguage});
	}

	private edit(user: User, newSettings: Dictionary<string>) {
		if (user.id === undefined) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		let settings = {
			...user,
			...newSettings,
		};
		delete settings.id;
		return UserService.edit(user.id, settings)
			.then(() => {
				this.getUsers().then(() => {
					this.$toast.success(
						this.$t('core.user.messages.edit.success', {username: user.username})
							.toString()
					);
				});
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
			});
	}

	private confirmDelete(user: User): void {
		this.deleteUser = user;
	}

	private performDelete(): void {
		if (this.deleteUser === null) {
			return;
		}
		const user = this.deleteUser;
		this.deleteUser = null;
		if (user.id === undefined) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		UserService.delete(user.id)
			.then(() => {
				if (user.id === this.$store.getters['user/getId']) {
					this.$store.dispatch('user/signOut');
					this.$toast.success(
						this.$t('core.user.messages.delete.success', {username: user.username})
							.toString()
					);
					this.$store.commit('spinner/HIDE');
					if (this.users.length === 1) {
						this.$router.push('/install/');
						return;
					}
					this.$router.push('/sign/in');
					return;
				}
				this.getUsers().then(() => {
					this.$toast.success(
						this.$t('core.user.messages.delete.success', {username: user.username})
							.toString()
					);
				});
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
			});
	}
}
</script>
