<template>
	<div class='d-inline'>
		{{ $t('gateway.info.usages.used') }}
		{{ usage.used }} / {{ usage.size }}
		<div class='progress'>
			<div
				class='progress-bar usage-progress-bar'
				role='progressbar'
				:style='{ width: usage.usage }'
			>
				{{ usage.used }} ({{ usage.usage }})
			</div>
		</div>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import { Dictionary } from 'vue-router/types/router';

@Component({})

/**
 * Resource usage component for gateway information
 */
export default class ResourceUsage extends Vue {
	/**
	 * @property {Dictionary<string>} usage Dictionary of gateway device resource usage
	 */
	@Prop({ required: true }) usage!: Dictionary<string>
}
</script>
