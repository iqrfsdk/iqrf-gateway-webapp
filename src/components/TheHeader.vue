<template>
	<CHeader :fixed='false' color-scheme='dark'>
		<CToggler
			in-header
			class='ml-3 d-lg-none'
			@click='$store.commit("sidebar/toggleSidebarMobile")'
		/>
		<CToggler
			in-header
			class='ml-3 d-md-down-none'
			@click='$store.commit("sidebar/toggleSidebarDesktop")'
		/>
		<CHeaderBrand class='mx-auto d-lg-none' to='/'>
			<LogoBig :alt='$t("core.title")' />
		</CHeaderBrand>
		<CHeaderNav class='ml-auto mr-3'>
			<CDropdown
				v-if='$store.getters["user/isLoggedIn"]'
				:in-nav='true'
				class='c-header-nav-items'
				placement='bottom-end'
				add-menu-classes='pt-0'
			>
				<template #toggler>
					<CHeaderNavLink class='dropdown-toggle'>
						{{ $store.getters['user/getName'] }}
					</CHeaderNavLink>
				</template>
				<CDropdownItem @click='signOut'>
					<CIcon :content='$options.icons.cilLockLocked' />
					{{ $t('core.sign.out.title') }}
				</CDropdownItem>
			</CDropdown>
		</CHeaderNav>
	</CHeader>
</template>

<script>
import {
	CDropdown,
	CHeader,
	CHeaderBrand,
	CHeaderNav,
	CHeaderNavLink,
	CIcon,
	CToggler,
} from '@coreui/vue/src';

import LogoBig from '../assets/logo-big.svg';
import {cilLockLocked} from '@coreui/icons';

export default {
	name: 'TheHeader',
	components: {
		CDropdown,
		CHeader,
		CHeaderBrand,
		CHeaderNav,
		CHeaderNavLink,
		CIcon,
		CToggler,
		LogoBig,
	},
	icons: {
		cilLockLocked,
	},
	methods: {
		signOut() {
			this.$store.dispatch('user/signOut')
				.then(() => {
					this.$router.push('/sign/in');
					this.$toast.success(this.$t('core.sign.out.message').toString());
				});
		}
	}
};
</script>