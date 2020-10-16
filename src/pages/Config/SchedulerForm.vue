<template>
	<div>
		<h1 v-if='$route.path === "/config/scheduler/add"'>
			{{ $t('config.scheduler.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.scheduler.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveTask'>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required'
							:custom-messages='{
								required: "config.scheduler.form.messages.nums",
								integer: "config.scheduler.form.messages.nums"
							}'
						>
							<CInput
								v-model.number='taskId'
								type='number'
								:label='$t("config.scheduler.form.task.taskId")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ errors, touched, valid}'
							rules='required'
							:custom-messages='{required: "config.scheduler.form.messages.service"}'
						>
							<CSelect
								:value.sync='clientId'
								:label='$t("config.scheduler.table.service")'
								:options='[
									{value: "SchedulerMessaging", label: "SchedulerMessaging"}
								]'
								placeholder='Select service'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<div class='form-group'>
							<label for='cronTime'>
								{{ $t("config.scheduler.form.task.cronTime") }}
							</label>
							<CBadge v-if='cronMessage !== null' color='info'>
								{{ cronMessage }}
							</CBadge>
							<CInput
								id='cronTime'
								v-model='timeSpec.cronTime'
								:disabled='timeSpec.exactTime || timeSpec.periodic'
								@input='calculateCron'
							/>
						</div>
						<CInputCheckbox
							:checked.sync='timeSpec.exactTime'
							:label='$t("config.scheduler.form.task.exactTime")'
							:disabled='timeSpec.periodic'
						/>
						<CInputCheckbox
							:checked.sync='timeSpec.periodic'
							:label='$t("config.scheduler.form.task.periodic")'
							:disabled='timeSpec.exactTime'
						/>
						<ValidationProvider
							v-slot='{ errors, touched, valid }'
							rules='integer|required'
							:custom-messages='{
								required: "config.scheduler.form.messages.nums",
								integer: "config.scheduler.form.messages.nums"
							}'
						>
							<CInput
								v-model.number='timeSpec.period'
								type='number'
								min='0'
								:label='$t("config.scheduler.form.task.period")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								:disabled='!timeSpec.periodic || timeSpec.exactTime'
							/>
						</ValidationProvider>
						<div class='form-group'>
							<label for='exactTime'>
								{{ $t("config.scheduler.form.task.startTime") }}
							</label>
							<Datetime
								id='exactTime'
								v-model='timeSpec.startTime'
								type='datetime'
								:format='dateFormat'
								:min-datetime='new Date().toISOString()'
								input-class='form-control'
								:disabled='!timeSpec.exactTime || timeSpec.periodic'
							/>
						</div>
						<h3>{{ $t('config.scheduler.form.message.title') }}</h3><hr>
						<div v-for='i of task.length' :key='i' class='form-group'>
							<ValidationProvider
								v-slot='{ errors, touched, valid}'
								rules='required'
								:custom-messages='{required: "config.scheduler.form.messages.service"}'
							>
								<CSelect
									:value.sync='task[i-1].messaging'
									:placeholder='$t("config.scheduler.form.message.messagePlaceholder")'
									:options='messagings'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required|json|mType'
								:custom-messages='{
									required: "config.scheduler.form.messages.message",
									json: "iqrfnet.sendJson.form.messages.invalid",
									mType: "iqrfnet.sendJson.form.messages.mType"
								}'
							>
								<CTextarea
									v-model='task[i-1].message'
									v-autogrow
									:label='$t("config.scheduler.form.message.label")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								v-if='task.length > 1'
								color='danger'
								@click='removeMessage(i-1)'
							>
								{{ $t('config.scheduler.buttons.remove') }}
							</CButton>
							<CButton
								v-if='i === task.length'
								color='success'
								@click='addMessage'
							>
								{{ $t('config.scheduler.buttons.add') }}
							</CButton>
						</div>
						<CButton type='submit' color='primary' :disabled='invalid'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {CBadge, CButton, CCard, CCardBody, CForm, CInput, CInputCheckbox, CSelect, CTextarea} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required} from 'vee-validate/dist/rules';
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import SchedulerService from '../../services/SchedulerService';
import {TextareaAutogrowDirective} from 'vue-textarea-autogrow-directive/src/VueTextareaAutogrowDirective';
import cronstrue from 'cronstrue';
import {Datetime} from 'vue-datetime';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';
import { Dictionary } from 'vue-router/types/router';
import { IOption } from '../../interfaces/coreui';
import { MetaInfo } from 'vue-meta';
import { AxiosError, AxiosResponse } from 'axios';
import { WsMessaging } from '../../interfaces/messagingInterfaces';
import { TaskTimeSpec } from '../../interfaces/scheduler';
import { MutationPayload } from 'vuex';

interface LocalTask {
	message: string
	messaging: string
}

@Component({
	components: {
		CBadge,
		CButton,
		CCard,
		CCardBody,
		CForm,
		CInput,
		CInputCheckbox,
		CSelect,
		CTextarea,
		Datetime,
		ValidationObserver,
		ValidationProvider
	},
	directives: {
		'autogrow': TextareaAutogrowDirective
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as SchedulerForm).pageTitle
		};
	}
})

export default class SchedulerForm extends Vue {
	private clientId = ''
	private components: Dictionary<string> = {
		mq: 'iqrf::MqMessaging',
		mqtt: 'iqrf::MqttMessaging',
		websocket: 'iqrf::WebsocketMessaging'
	}
	private cronMessage: string|null = null
	private dateFormat: Dictionary<string|boolean> = {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour12: false,
		hour: 'numeric',
		minute: 'numeric',
		second: 'numeric',
	}
	private messagings: Array<IOption> = []
	private msgIds: Array<string> = []
	private taskId = Math.floor(Date.now() / 1000)
	private task: Array<LocalTask> = [{message: '', messaging: ''}]
	private timeSpec: TaskTimeSpec = {
		cronTime: '',
		periodic: false,
		period: 0,
		exactTime: false,
		startTime: ''
	}
	private unsubscribe: CallableFunction = () => {return;}
	private untouched = true
	private useRest = true

	@Prop({required: false, default: null}) id!: number

	created(): void {
		this.$store.commit('spinner/SHOW');
		setTimeout(() => {
			if (this.$store.state.webSocketClient.socket.isConnected) {
				this.useRest = false;
			}
			if (this.id && this.untouched) {
				this.getTask(this.id);
			}
		}, 1000);
		extend('integer', integer);
		extend('required', required);
		extend('json', (json) => {
			try {
				JSON.parse(json);
				return true;
			} catch (error) {
				return false;
			}
		});
		extend('mType', (json) => {
			let object = JSON.parse(json);
			return {}.hasOwnProperty.call(object, 'mType');
		});
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONOPEN') {
				this.useRest = false;
				if (this.id && this.untouched) {
					this.getTask(this.id);
				}
			} else if (mutation.type === 'SOCKET_ONCLOSE' ||
				mutation.type === 'SOCKET_ONERROR') {
				this.useRest = true;
			} else if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'mngScheduler_GetTask' &&
					this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						let rsp = mutation.payload.data.rsp;
						this.taskId = rsp.taskId;
						this.clientId = rsp.clientId;
						this.timeSpec = rsp.timeSpec;
						if (Array.isArray(this.timeSpec.cronTime)) {
							this.timeSpec.cronTime = this.timeSpec.cronTime.join(' ');
						}
						if (Array.isArray(rsp.task)) {
							let tasks: Array<LocalTask> = [];
							rsp.task.forEach(item => {
								tasks.push({
									messaging: item.messaging,
									message: JSON.stringify(item.message, null, 2),
								});
							});
							this.task = tasks;
						} else {
							this.task = [
								{
									messaging: rsp.task.messaging,
									message: JSON.stringify(rsp.task.message, null, 2),
								}
							];
						}
					} else {
						this.$router.push('/config/scheduler/');
						this.$toast.error(
							this.$t('config.scheduler.messages.getFail', {task: this.id})
								.toString()
						);
					}
				} else if (mutation.payload.mType === 'mngScheduler_AddTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						this.successfulSave();
					} else {
						this.$toast.error(
							this.$t('config.scheduler.messagess.processError').toString()
						);
					}
				} else if (mutation.payload.mType === 'mngScheduler_RemoveTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
				} else if (mutation.payload.mType === 'messageError') {
					this.$store.commit('spinner/HIDE');
					this.$toast.error(
						this.$t('config.scheduler.messagess.processError').toString()
					);
				}
			}
		});
		this.getMessagings();
	}

	beforeDestroy(): void {
		this.msgIds.forEach((item: string) => this.$store.dispatch('removeMessage', item));
		this.unsubscribe();
	}

	get pageTitle(): string {
		return this.$route.path === '/config/scheduler/add' ?
			this.$t('config.scheduler.add').toString() : this.$t('config.scheduler.edit').toString();
	}

	private calculateCron(): void {
		const cronTime = (this.timeSpec.cronTime as string).split(' ');
		const len = cronTime.length;
		if (len === 1) {
			const alias = this.getCronAlias((this.timeSpec.cronTime as string));
			if (alias !== undefined) {
				this.cronMessage = cronstrue.toString(alias);
			} else {
				this.cronMessage = null;
			}
		} else if (len > 4 && len < 8) {
			this.cronMessage = cronstrue.toString((this.timeSpec.cronTime as string));
		} else {
			this.cronMessage = null;
		}
	}

	private addMessage(): void {
		this.task.push({message: '', messaging: ''});
	}

	private removeMessage(index): void {
		this.task.splice(index, 1);
	}

	private getTask(taskId: number): void {
		this.untouched = false;
		this.$store.commit('spinner/SHOW');
		if (this.useRest) {
			SchedulerService.getTaskREST(taskId)
				.then((response: AxiosResponse) => {
					this.$store.commit('spinner/HIDE');
					this.taskId = response.data.taskId;
					this.clientId = response.data.clientId;
					this.timeSpec = response.data.timeSpec;
					let tasks: Array<LocalTask> = [];
					response.data.task.forEach(item => {
						tasks.push({messaging: item.messaging, message: JSON.stringify(item.message, null, 2)});
					});
					this.task = tasks;
				})
				.catch(() => {
					this.$router.push('/config/scheduler/');
					this.$toast.error(
						this.$t('config.scheduler.messages.getFail', {task: this.id})
							.toString()
					);
				});
		} else {
			SchedulerService.getTask(taskId, new WebSocketOptions(null, 30000, null, () => this.$router.push('/config/scheduler/')))
				.then((msgId: string) => this.storeId(msgId));
		}
	}

	private storeId(msgId: string): void {
		this.msgIds.push(msgId);
		setTimeout(() => {
			if (this.msgIds.includes(msgId)) {
				this.msgIds.splice(this.msgIds.indexOf(msgId), 1);
			}
		}, 30000);
	}

	private getMessagings(): void {
		this.$store.commit('spinner/SHOW');
		Promise.all([
			DaemonConfigurationService.getComponent(this.components.mq),
			DaemonConfigurationService.getComponent(this.components.mqtt),
			DaemonConfigurationService.getComponent(this.components.websocket),
		])
			.then((responses: Array<AxiosResponse>) => {
				this.$store.commit('spinner/HIDE');
				responses.forEach((item: AxiosResponse) => {
					if (item.data.instances) {
						item.data.instances.forEach((messaging: WsMessaging) => {
							this.messagings.push({
								value: messaging.instance, label: messaging.instance,
							});
						});
					}
				});
			})
			.catch(() => {
				this.$store.commit('spinner/HIDE');
				this.$router.push('/config/scheduler/');
				this.$toast.error(
					this.$t('config.scheduler.messages.rest.messagingFail').toString()
				);
			});
	}

	private saveTask(): void {
		this.$store.commit('spinner/SHOW');
		let timeSpec = JSON.parse(JSON.stringify(this.timeSpec));
		timeSpec.cronTime = timeSpec.cronTime.replace('?', '*');
		timeSpec.cronTime = timeSpec.cronTime.split(' ');
		if (timeSpec.cronTime.length === 1) {
			const alias = this.getCronAlias(timeSpec.cronTime[0]);
			if (alias !== undefined) {
				timeSpec.cronTime = [alias, '', '', '', '', '', ''];
			}
		}
		switch(timeSpec.cronTime.length) {
			case 5:
				timeSpec.cronTime.unshift('0');
				timeSpec.cronTime.push('*');
				break;
			case 6:
				if (timeSpec.cronTime[5].length === 4) {
					timeSpec.cronTime.unshift('0');
				} else {
					timeSpec.cronTime.push('*');
				}
				break;
			case 7:
				break;
			default:
				timeSpec.cronTime = new Array(7).fill('');
		}
		if (timeSpec.exactTime) {
			let date = new Date(timeSpec.startTime);
			date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
			timeSpec.startTime = date.toISOString().split('.')[0];
		}
		if (this.$route.path === '/config/scheduler/add') {
			if (this.useRest) {
				SchedulerService.addTaskREST(this.taskId, this.clientId, this.task, timeSpec)
					.then(() => this.successfulSave())
					.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
			} else {
				SchedulerService.addTask(this.taskId, this.clientId, this.task, timeSpec, new WebSocketOptions(
					null, 30000, 'config.scheduler.messagess.processError'))
					.then((msgId: string) => this.storeId(msgId));
			}
		} else {
			if (this.useRest) {
				SchedulerService.editTaskREST(this.id, this.taskId, this.clientId, this.task, timeSpec)
					.then(() => this.successfulSave())
					.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
			} else {
				SchedulerService.removeTask(this.taskId, new WebSocketOptions(null, 30000, 'config.scheduler.messages.deleteFail'))
					.then((msgId: string) => this.storeId(msgId));
				SchedulerService.addTask(this.taskId, this.clientId, this.task, timeSpec, new WebSocketOptions(
					null, 30000, 'config.scheduler.messagess.processError'))
					.then((msgId: string) => this.storeId(msgId));
			}
		}
	}

	private getCronAlias(input: string): string {
		let aliases = new Map();
		aliases.set('@reboot', '');
		aliases.set('@yearly', '0 0 0 1 1 * *');
		aliases.set('@annually', '0 0 0 1 1 * *');
		aliases.set('@monthly', '0 0 0 1 * * *');
		aliases.set('@weekly', '0 0 0 * * 0 *');
		aliases.set('@daily', '0 0 0 * * * *');
		aliases.set('@hourly', '0 0 * * * * *');
		aliases.set('@minutely', '0 * * * * * *');
		return aliases.get(input);
	}

	private successfulSave(): void {
		this.$router.push('/config/scheduler/');
		this.$toast.success(
			this.$t('config.scheduler.messages.addSuccess').toString()
		);
	}
}
</script>
