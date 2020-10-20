<template>
	<div>
		<CCard>
			<CCardHeader>
				<h3 class='float-left'>
					{{ $t('config.scheduler.title') }}
				</h3>
				<div class='float-right'>
					<CButton
						color='success'
						size='sm'
						to='/config/scheduler/add'
					>
						<CIcon :content='icons.add' size='sm' />
						{{ $t('table.actions.add') }}
					</CButton> <CButton
						color='primary'
						size='sm'
						@click='importConfig.modal = true'
					>
						<CIcon :content='icons.import' size='sm' />
						{{ $t('config.scheduler.buttons.import') }}
					</CButton> <CButton
						color='secondary'
						size='sm'
						@click='exportScheduler'
					>
						<CIcon :content='icons.export' size='sm' />
						{{ $t('config.scheduler.buttons.export') }}
					</CButton>
				</div>
			</CCardHeader>
			<CCardBody>
				<CDataTable
					:fields='fields'
					:items='tasks'
					:column-filter='true'
					:items-per-page='20'
					:pagination='true'
					:striped='true'
					:sorter='{external: false, resetable: true}'
				>
					<template #no-items-view='{}'>
						{{ $t('table.messages.noRecords') }}
					</template>
					<template v-if='retrieved === "rest"' #taskId='{item}'>
						<td>
							{{ item.id }}
						</td>
					</template>
					<template v-if='retrieved === "rest"' #clientId='{item}'>
						<td>
							{{ item.service }}
						</td>
					</template>
					<template #timeSpec='{item}'>
						<td>
							{{ timeString(item.timeSpec) }}
						</td>
					</template>
					<template #task='{item}'>
						<td v-if='retrieved === "daemon"'>
							{{ displayMTypes(item.task) }}
						</td>
						<td v-else>
							{{ displayMTypes(item.mTypes) }}
						</td>
					</template>
					<template #actions='{item}'>
						<td class='col-actions'>
							<CButton
								color='info'
								size='sm'
								:to='"/config/scheduler/edit/" + (retrieved === "daemon" ? item.taskId : item.id)'
							>
								<CIcon :content='icons.edit' size='sm' />
								{{ $t('table.actions.edit') }}
							</CButton> <CButton
								color='danger'
								size='sm'
								@click='deleteTask = retrieved === "daemon" ? item.taskId : item.id'
							>
								<CIcon :content='icons.remove' size='sm' />
								{{ $t('table.actions.delete') }}
							</CButton>
						</td>
					</template>
				</CDataTable>
			</CCardBody>
		</CCard>
		<CModal
			:show.sync='importConfig.modal'
			color='primary'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.scheduler.form.import.title') }}
				</h5>
			</template>
			<CForm>
				<div class='form-group'>
					<CInputFile
						ref='schedulerImport'
						accept='application/json,.zip'
						:label='$t("config.scheduler.form.import.file")'
						@input='isEmpty'
						@click='isEmpty'
					/>
					<p v-if='importConfig.empty && !importConfig.first' style='color: red'>
						{{ $t('config.scheduler.form.import.fileEmpty') }}
					</p>
				</div>
			</CForm>
			<template #footer>
				<CButton color='secondary' @click='closeImport'>
					{{ $t('forms.cancel') }}
				</CButton>
				<CButton
					color='primary'
					:disabled='importConfig.empty'
					@click='importScheduler'
				>
					{{ $t('config.scheduler.buttons.import') }}
				</CButton>
			</template>
		</CModal>
		<CModal
			color='danger'
			:show='deleteTask !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.scheduler.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('config.scheduler.messages.deletePrompt', {task: deleteTask}) }}
			<template #footer>
				<CButton
					color='danger'
					@click='deleteTask = null'
				>
					{{ $t('forms.no') }}
				</CButton> <CButton
					color='success'
					@click='removeTask'
				>
					{{ $t('forms.yes') }}
				</CButton>
			</template>
		</CModal>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CIcon, CInputFile, CModal} from '@coreui/vue/src';
import {cilPencil, cilPlus, cilTrash, cilArrowTop, cilArrowBottom} from '@coreui/icons';
import {fileDownloader} from '../../helpers/fileDownloader';
import {TextareaAutogrowDirective} from 'vue-textarea-autogrow-directive/src/VueTextareaAutogrowDirective';
import SchedulerService from '../../services/SchedulerService';
import ServiceService from '../../services/ServiceService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {DateTime, Duration} from 'luxon';
import { WebSocketOptions } from '../../store/modules/webSocketClient.module';
import { Dictionary } from 'vue-router/types/router';
import { IField } from '../../interfaces/coreui';
import { AxiosError, AxiosResponse } from 'axios';
import {Task, TaskTimeSpec} from '../../interfaces/scheduler';
import { MutationPayload } from 'vuex';

@Component({
	components: {
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
		CForm,
		CIcon,
		CInputFile,
		CModal,
	},
	directives: {
		'autogrow': TextareaAutogrowDirective
	}
})

export default class SchedulerList extends Vue {
	private dateFormat: Dictionary<string|boolean> = {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour12: false,
		hour: 'numeric',
		minute: 'numeric',
		second: 'numeric',
	}
	private deleteTask: number|null = null
	private fields: Array<IField> = [
		{
			key: 'taskId',
			label: this.$t('config.scheduler.table.id'),
		},
		{
			key: 'timeSpec',
			label: this.$t('config.scheduler.table.time'),
			filter: false,
			sorter: false,
		},
		{
			key: 'clientId',
			label: this.$t('config.scheduler.table.service'),
		},
		{
			key: 'task',
			label: this.$t('config.scheduler.table.mType'),
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		},
	]
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		edit: cilPencil,
		remove: cilTrash,
		import: cilArrowTop,
		export: cilArrowBottom,
	}
	private importConfig: Dictionary<boolean> = {
		modal: false,
		first: true,
		empty: false,
	}
	private msgIds: Array<string> = []
	private retrieved: string|null = null
	private taskIds: Array<number> = []
	private tasks: Array<Task>|null = null
	private untouched = true
	private unsubscribe: CallableFunction = () => {return;}
	private useRest = true

	mounted(): void {
		this.$store.commit('spinner/SHOW');
		setTimeout(() => {
			if (this.$store.state.webSocketClient.socket.isConnected) {
				this.useRest = false;
			}
			if (this.untouched) {
				this.getTasks();
			}
		}, 1000);
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONOPEN') {
				this.useRest = false;
				if (this.untouched) {
					this.getTasks();
				}
			} else if (mutation.type === 'SOCKET_ONCLOSE' ||
				mutation.type === 'SOCKET_ONERROR') {
				this.useRest = true;
			} else if (mutation.type === 'SOCKET_ONSEND') {
				if (mutation.payload.mType === 'mngScheduler_List') {
					if (this.taskIds !== []) {
						this.taskIds = [];
						this.tasks = [];
					}
				}
			} else if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'mngScheduler_List' &&
					this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						this.taskIds = mutation.payload.data.rsp.tasks;
						this.taskIds.forEach(item => {
							this.getTask(item);
						});
						this.retrieved = 'daemon';
					} else {
						this.$toast.error(
							this.$t('config.scheduler.messages.listFailed').toString()
						);
					}
				} else if (mutation.payload.mType === 'mngScheduler_GetTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						if (this.tasks === null) {
							return;
						}
						this.tasks.push(mutation.payload.data.rsp);
					}
				} else if (mutation.payload.mType === 'mngScheduler_RemoveTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						this.$toast.success(
							this.$t('config.scheduler.messages.deleteSuccess').toString()
						);
						this.getTasks();
					} else {
						this.$toast.error(
							this.$t('config.scheduler.messages.deleteFail').toString()
						);
					}
				}
			}
		});
	}

	beforeDestroy(): void {
		this.msgIds.forEach((item) => this.$store.dispatch('removeMessage', item));
		this.unsubscribe();
	}

	private closeImport(): void {
		this.importConfig.modal = false;
		this.importConfig.first = true;
	}

	private displayMTypes(item): string {
		try {
			if (this.retrieved === 'rest') {
				return Array.isArray(item) ? item.join(', ') : item;
			}
			if (Array.isArray(item)) {
				let message = '';
				item.forEach(item => {
					message += item.message.mType + ', ';
				});
				return message.slice(0, -2);
			} else {
				return item.message.mType;
			} 
		} catch(err) {
			return '';
		}
	}

	private getTasks(): void {
		this.untouched = false;
		this.$store.commit('spinner/SHOW');
		if (this.useRest) {
			SchedulerService.listTasksREST()
				.then((response: AxiosResponse) => {
					this.$store.commit('spinner/HIDE');
					this.tasks = response.data;
					this.retrieved = 'rest';
				})
				.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
		} else {
			SchedulerService.listTasks(new WebSocketOptions(null, 30000, 'config.scheduler.messages.listFailed'))
				.then((msgId: string) => this.storeId(msgId));
		}
	}

	private getTask(taskId: number): void {
		SchedulerService.getTask(taskId, new WebSocketOptions(null, 30000))
			.then((msgId: string) => this.storeId(msgId));
	}

	private removeTask(): void {
		if (this.deleteTask === null) {
			return;
		}
		this.$store.commit('spinner/SHOW');
		const task = this.deleteTask;
		this.deleteTask = null;
		if (this.useRest) {
			SchedulerService.removeTaskREST(task)
				.then(() => {
					this.$store.commit('spinner/HIDE');
					this.$toast.success(
						this.$t('config.scheduler.messages.deleteSuccess').toString()
					);
					this.getTasks();
				})
				.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
		} else {
			SchedulerService.removeTask(task, new WebSocketOptions(null, 30000, 'config.scheduler.messages.deleteFail'))
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

	private exportScheduler(): void {
		this.$store.commit('spinner/SHOW');
		SchedulerService.exportConfig()
			.then((response: AxiosResponse) => {
				this.$store.commit('spinner/HIDE');
				const fileName = 'iqrf-gateway-scheduler_' + + new Date().toISOString();
				const file = fileDownloader(response, 'application/zip', fileName);
				file.click();
			});
	}

	private importScheduler(): void {
		this.importConfig.modal = false;
		this.$store.commit('spinner/SHOW');
		const files = this.getFile();
		SchedulerService.importConfig(files[0])
			.then(() => {
				ServiceService.restart('iqrf-gateway-daemon')
					.then(() => {
						this.$store.commit('spinner/HIDE');
						this.$toast.success(
							this.$t('config.scheduler.messages.importSuccess').toString()
						);
						this.$toast.info(
							this.$t('service.iqrf-gateway-daemon.messages.restart')
								.toString()
						);
					})
					.catch((error: AxiosError) => FormErrorHandler.serviceError(error));
			})
			.catch((error: AxiosError) => {
				this.$store.commit('spinner/HIDE');
				if (error.response === undefined) {
					return;
				}
				if (error.response.status === 400) {
					this.$toast.error(
						this.$t('config.scheduler.messages.importInvalidFile').toString()
					);
				} else if (error.response.status === 415) {
					this.$toast.error(
						this.$t('config.scheduler.messages.importInvalidFormat')
							.toString()
					);
				}
			});
	}

	private getFile(): FileList {
		const input = ((this.$refs.schedulerImport as CInputFile).$el.children[1] as HTMLInputElement);
		return (input.files as FileList);
	}

	private isEmpty(): void {
		if (this.importConfig.first) {
			this.importConfig.first = false;
		}
		const file = this.getFile();
		this.importConfig.empty = file.length === 0;
	}

	private timeString(item: TaskTimeSpec): string|undefined {
		try {
			if (item.exactTime) {
				return 'oneshot (' + DateTime.fromISO(item.startTime).toLocaleString(this.dateFormat) + ')';
			}
			if (item.periodic) {
				let message = 'every ';
				const duration = Duration.fromMillis(item.period * 1000);
				if (item.period >= 0 && item.period < 60) {
					message += duration.toFormat('s') + ' s';
				} else if (item.period < 3600) {
					message += duration.toFormat('m:ss') + ' min';
				} else {
					message += duration.toFormat('h:mm:ss') + ' h';
				}
				return message;
			}
			if (item.cronTime.length > 0) {
				if (typeof item.cronTime === 'string') {
					return item.cronTime;
				}
				return item.cronTime.join(' ').trim();
			}
		} catch (err) {
			console.error(err);
			return '';
		}
	}
}
</script>
