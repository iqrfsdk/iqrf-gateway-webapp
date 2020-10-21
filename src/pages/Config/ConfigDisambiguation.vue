<template>
	<div>
		<h1>{{ $t('config.title') }}</h1>
		<CCard body-wrapper>
			<CListGroup>
				<CListGroupItem to='/config/daemon/'>
					<header class='list-group-item-heading'>
						{{ $t('config.daemon.title') }}
					</header>
					<p class='list-group-item-text'>
						{{ $t('config.daemon.description') }}
					</p>
				</CListGroupItem>
				<CListGroupItem
					v-if='$store.getters["features/isEnabled"]("networkManager")'
					to='/network/'
				>
					<header class='list-group-item-heading'>
						{{ $t('translatorConfig.title') }}
					</header>
					<p class='list-group-item-text'>
						{{ $t('translatorConfig.description') }}
					</p>
				</CListGroupItem>
				<CListGroupItem
					v-if='$store.getters["features/isEnabled"]("iqrfGatewayController")'
					to='/config/controller/'
				>
					<header class='list-group-item-heading'>
						{{ $t('controllerConfig.title') }}
					</header>
					<p class='list-group-item-text'>
						{{ $t('controllerConfig.description') }}
					</p>
				</CListGroupItem>
				<CListGroupItem
					v-if='$store.getters["features/isEnabled"]("mender")'
					to='/config/mender/'
				>
					<header class='list-group-item-heading'>
						{{ $t('config.mender.title') }}
					</header>
					<p class='list-group-item-text'>
						{{ $t('config.mender.description') }}
					</p>
				</CListGroupItem>
				<CListGroupItem to='/config/migration/'>
					<header class='list-group-item-heading'>
						{{ $t('config.migration.title') }}
					</header>
					<p class='list-group-item-text'>
						{{ $t('config.migration.description') }}
					</p>
				</CListGroupItem>
			</CListGroup>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CCard, CListGroup, CListGroupItem} from '@coreui/vue/src';

@Component({
	components: {
		CCard,
		CListGroup,
		CListGroupItem
	},
	metaInfo: {
		title: 'config.title',
	},
})

export default class ConfigDisambiguation extends Vue {
}
</script>
