<template>
	<div v-if='enabled' class='loading'>
		<div class='loading-group'>
			<div class='spinner' />
			<div class='loading-text'>
				{{ text }}
			</div>
		</div>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import { mapGetters } from 'vuex';

@Component({
	computed: {
		...mapGetters({
			enabled: 'spinner/isEnabled',
			text: 'spinner/text',
		}),
	}
})

export default class LoadingSpinner extends Vue {}
</script>

<style scoped lang='scss'>
.loading {
	position: fixed;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.75);
	z-index: 9999;
	cursor: wait;
	overflow: auto;
	text-align: center;
	&:before {
		content: '';
		display: inline flow-root;
		height: 100%;
		vertical-align: middle;
	}
}

.loading-group {
	display: inline flow-root;
	vertical-align: middle;
}

.loading-text {
	font-size: 20pt;
	position: relative;
	z-index: 10000;
	color: white;
	text-align: center;
	white-space: pre-wrap;
}

.spinner {
	display: block;
	position: relative;
	z-index: 10000;
	width: 150px;
	height: 150px;
	margin: auto;
	border: 16px solid #f3f3f3;
	border-radius: 50%;
	border-top: 16px solid #3498db;
	animation: spin 2s linear infinite;
}

@keyframes spin {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}
</style>
