<template>
	<CCard v-if='macros'>
		<CCardHeader>
			{{ $t('iqrfnet.sendPacket.macros') }}
		</CCardHeader>
		<CCardBody>
			<CButtonGroup class='flex-wrap'>
				<CDropdown
					v-for='group of macros'
					:key='group.id'
					:toggler-text='group.name'
					color='primary'
					placement='top-start'
				>
					<CDropdownItem
						v-for='packet of group.macros'
						:key='packet.name'
						@click='$emit("set-packet", packet.request)'
					>
						{{ packet.name }}
					</CDropdownItem>
				</CDropdown>
			</CButtonGroup>
		</CCardBody>
	</CCard>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosResponse} from 'axios';
import {CButtonGroup, CCard, CCardBody, CCardHeader, CDropdown, CDropdownItem} from '@coreui/vue/src';
import IqrfService from '../../services/IqrfService';

interface IDpaMacro {
	confirmation: boolean
	enabled: boolean
	name: string
	note: string
	request: string
}

interface IDpaMacros {
	enabled: boolean
	id: number
	macros: Array<IDpaMacro>
	name: string
}

@Component({
	components: {
		CButtonGroup,
		CCard,
		CCardBody,
		CCardHeader,
		CDropdown,
		CDropdownItem,
	}
})

export default class DpaMacros extends Vue {
	private macros: Array<IDpaMacros> = []

	created(): void {
		IqrfService.getMacros()
			.then((response: AxiosResponse) => {
				this.macros = response.data.filter((group: IDpaMacros) => {
					if (!group.enabled) {
						return null;
					}
					group.macros = group.macros.filter((packet: IDpaMacro) => {
						if (packet.enabled) {
							return packet;
						}
					});
					return group;
				});
			});
	}
}
</script>
