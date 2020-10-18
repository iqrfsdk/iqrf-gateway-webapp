<template>
	<div>
		<CCard>
			<CCardHeader><h3>{{ $t('config.iqmesh.title') }}</h3></CCardHeader>
			<CCardBody>
				<OtaUpload />
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import OtaUpload from '../../components/Config/OtaUpload.vue';

@Component({
	components: {
		OtaUpload,
	}
})

export default class IqmeshServices extends Vue {}
</script>
