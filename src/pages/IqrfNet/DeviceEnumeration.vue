<template>
	<div>
		<h1>{{ $t('iqrfnet.enumeration.title') }}</h1>
		<div v-if='response !== undefined'>
			<CCard>
				<CCardHeader>{{ $t('iqrfnet.enumeration.deviceInfo') }}</CCardHeader>
				<CCardBody>
					<table class='table table-striped'>
						<tbody>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.deviceAddr') }}</th>
								<td>{{ response.deviceAddr }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.hwpid') }}</th>
								<td>{{ peripheralData.hwpId }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.manufacturer') }}</th>
								<td>{{ response.manufacturer }}</td>
							</tr>
							<tr v-if='product !== undefined'>
								<th>{{ $t('iqrfnet.enumeration.product') }}</th>
								<td><a :href='product.homePage'>{{ product.name }}</a></td>
							</tr>
							<tr v-if='product !== undefined'>
								<th>{{ $t('iqrfnet.enumeration.picture') }}</th>
								<td>
									<img
										:alt='response.product'
										class='productPicture'
										:src='product.picture'
									>
								</td>
							</tr>
							<tr v-else>
								<th>{{ $t('iqrfnet.enumeration.product') }}</th>
								<td>{{ response.product }}</td>
							</tr>
						</tbody>
					</table>
				</CCardBody>
			</CCard>
			<CCard>
				<CCardHeader>{{ $t('iqrfnet.enumeration.trInfo') }}</CCardHeader>
				<CCardBody>
					<table class='table table-striped'>
						<tbody>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.trType') }}</th>
								<td>{{ osData.trMcuType.trType }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.mid') }}</th>
								<td>{{ osData.mid }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.osVersion') }}</th>
								<td>{{ osData.osVersion }} ({{ osData.osBuild }})</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.dpaVersion') }}</th>
								<td>{{ peripheralData.dpaVer }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.rfMode') }}</th>
								<td v-if='peripheralData.flags.rfModeStd'>
									<RfModeStd alt='STD' class='rfModeIcon' />
									<strong>{{ $t('iqrfnet.enumeration.rfModes.std') }}</strong>
								</td>
								<td v-else-if='peripheralData.flags.rfModeLp'>
									<RfModeLp alt='LP' class='rfModeIcon' />
									<strong>{{ $t('iqrfnet.enumeration.rfModes.lp') }}</strong>
								</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.rssi') }}</th>
								<td>{{ osData.rssi }}</td>
							</tr>
							<tr>
								<th>{{ $t('iqrfnet.enumeration.supplyVoltage') }}</th>
								<td>{{ osData.supplyVoltage }}</td>
							</tr>
						</tbody>
					</table>
					<CButton color='primary' to='/iqrfnet/network/'>
						{{ $t('iqrfnet.enumeration.back') }}
					</CButton>
				</CCardBody>
			</CCard>
		</div>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {MutationPayload} from 'vuex';
import {CButton, CCard, CCardBody, CCardHeader} from '@coreui/vue/src';
import IqrfNetService from '../../services/IqrfNetService';
import ProductService from '../../services/IqrfRepository/ProductService';
import RfModeLp from '../../assets/lp-black.svg';
import RfModeStd from '../../assets/std-black.svg';
import { WebSocketClientState } from '../../store/modules/webSocketClient.module';
import { AxiosError, AxiosResponse } from 'axios';
import { IDeviceEnumeration, OsInfo, PeripheralEnumeration } from '../../interfaces/dpa';

interface Product {
	companyName: string
	homePage: string
	hwpid: number
	manufacturerID: number
	name: string
	picture: string
	pictureOriginal: string
	rfMode: number
}

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		RfModeLp,
		RfModeStd,
	},
	metaInfo: {
		title: 'iqrfnet.enumeration.title',
	}
})

export default class DeviceEnumeration extends Vue {
	private msgId: string|null = null
	private osData: OsInfo|null = null
	private peripheralData: PeripheralEnumeration|null = null
	private product: Product|null = null
	private response: IDeviceEnumeration|null = null
	private unsubscribe: CallableFunction = () => {return;}
	private unwatch: CallableFunction = () => {return;}
	
	@Prop({required: false, default: 0}) address!: number

	created(): void {
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type !== 'SOCKET_ONMESSAGE' ||
				mutation.payload.data.msgId !== this.msgId) {
				return;
			}
			this.$store.dispatch('spinner/hide');
			this.$store.dispatch('removeMessage', this.msgId);
			const response = mutation.payload;
			if (response.data.status !== 0) {
				this.$router.push('/iqrfnet/network/');
				this.$toast.error(
					this.$t('iqrfnet.enumeration.messages.failure').toString()
				);
				return;
			}
			const data = response.data.rsp;
			if (data) {
				this.response = data;
				this.peripheralData = data.peripheralEnumeration;
				this.osData = data.osRead;
				if (this.peripheralData === null) {
					return;
				}
				ProductService.get(this.peripheralData.hwpId)
					.then((response: AxiosResponse) => {
						this.product = response.data;
					})
					.catch((error: AxiosError) => {
						if (error.response !== undefined && error.response.status === 404) {
							return;
						}
						this.$toast.error(
							this.$t('iqrfnet.enumeration.messages.repositoryUnavailable')
								.toString()
						);
					});
			}
		});
		if (this.$store.getters.isSocketConnected) {
			this.enumerate();
		} else {
			this.unwatch = this.$store.watch(
				(state: WebSocketClientState, getter: any) => getter.isSocketConnected,
				(newVal: boolean, oldVal: boolean) => {
					if (!oldVal && newVal) {
						this.enumerate();
						this.unwatch();
					}
				}
			);
		}
	}

	beforeDestroy(): void {
		this.$store.dispatch('removeMessage', this.msgId);
		if (this.unwatch !== undefined) {
			this.unwatch();
		}
		this.unsubscribe();
	}

	private enumerate(): void {
		this.$store.dispatch('spinner/show', {timeout: 30000});
		IqrfNetService.enumerateDevice(this.address, 30000, 'iqrfnet.enumeration.messages.failure', () => this.msgId = null)
			.then((msgId: string) => this.msgId = msgId);
	}
}
</script>

<style scoped>
@media (max-width: 768px) {
	.productPicture {
		max-height: 100%;
		max-width: 100%;
	}
}

@media (min-width: 768px) {
	.productPicture {
		max-height: 33%;
		max-width: 33%;
	}
}
</style>
