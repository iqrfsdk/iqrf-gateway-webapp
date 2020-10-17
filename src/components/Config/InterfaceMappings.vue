<template>
	<div>
		<h4>{{ $t('config.interfaceMapping.boards') }}</h4>
		<CButtonGroup>
			<CButton
				v-for='board in Object.keys(mappings[interfaceType])'
				:key='board'
				color='primary'
				@click='setMapping(board)'
			>
				{{ board }}
			</CButton>
		</CButtonGroup>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CButton, CButtonGroup} from '@coreui/vue/src';
import {IMappings} from '../../interfaces/mappings';

@Component({
	components: {
		CButton,
		CButtonGroup,
	},
})

/**
 * Interface configuration mapping
 */
export default class InterfaceMappings extends Vue {
	/**
	 * @var {IMappings} mappings dictionary of spi and uart mappings
	 */
	private mappings: IMappings = {
		'spi': require('../../../app/ConfigModule/json/SpiPins.json'),
		'uart': require('../../../app/ConfigModule/json/UartPins.json'),
	}

	/**
	 * @property {string} interfaceType communication interface type
	 */
	@Prop({ required: true }) interfaceType!: string;
	
	/**
	 * Emits selected mapping to parent component to update form fields
	 * @param {string} board board name
	 */
	private setMapping(board: string): void {
		const mapping = this.mappings[this.interfaceType][board];
		this.$emit('update-mapping', mapping);
	}

}
</script>
