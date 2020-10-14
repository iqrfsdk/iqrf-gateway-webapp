<template>
	<router-link
		v-if='device.hasLink()'
		:to='"/iqrfnet/enumeration/" + device.address'
	>
		<CIcon :class='device.getIconColor()' :content='device.getIcon()' />
	</router-link>
	<CIcon v-else :class='device.getIconColor()' :content='device.getIcon()' />
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import Device from '../../helpers/Device';
import {CIcon} from '@coreui/vue/src';

@Component({
	components: {
		CIcon
	}
})

export default class DeviceIcon extends Vue {
	@Prop({required: true}) device!: Device
}
</script>
