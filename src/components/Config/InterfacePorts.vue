<template>
	<div v-if='ports !== []'>
		<h4>{{ $t('config.interfaceMapping.interfaces') }}</h4>
		<CButtonGroup>
			<CButton
				v-for='port of ports'
				:key='port'
				color='primary'
				@click='setPort(port)'
			>
				{{ port }}
			</CButton>
		</CButtonGroup>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CButtonGroup} from '@coreui/vue/src';
import IqrfService from '../../services/IqrfService';

@Component({
	components: {
		CButton,
		CButtonGroup,
	},
})

/**
 * Interface port mapping
 */
export default class InterfacePorts extends Vue {
	/**
	 * @var {Array<string>} ports device ports
	 */
	private ports: Array<string> = []
	
	/**
	 * @property {string} interfaceType communication interface type
	 */
	@Prop({ required: true }) interfaceType!: string;

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		IqrfService.getInterfacePorts(this.interfaceType)
			.then((ports: Array<string>) => (this.ports = ports))
			.catch(() => (this.ports = []));
	}

	/**
	 * Emits port to parent component to update port field
	 * @param {string} port port name
	 */
	private setPort(port: string): void {
		this.$emit('update-port', port);
	}
}
</script>
