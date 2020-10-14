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

export default class ResourceUsage extends Vue {
	@Prop({ required: true }) usage!: Dictionary<string>
}
</script>
