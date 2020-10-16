<template>
	<div>
		<h1>{{ $t('iqrfnet.trUpload.title') }}</h1>
		<FileUpload />
		<DpaUpdater />
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {NavigationGuardNext, Route} from 'vue-router';
import DpaUpdater from '../../components/IqrfNet/DpaUpdater.vue';
import FileUpload from '../../components/IqrfNet/FileUpload.vue';

@Component({
	components: {
		DpaUpdater,
		FileUpload
	},
	beforeRouteEnter(to: Route, from: Route, next: NavigationGuardNext): void {
		next((vm: Vue) => {
			if (!vm.$store.getters['features/isEnabled']('trUpload')) {
				vm.$toast.error(
					vm.$t('iqrfnet.trUpload.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	metaInfo: {
		title: 'iqrfnet.trUpload.title'
	}
})

export default class TrUpload extends Vue {
}
</script>
