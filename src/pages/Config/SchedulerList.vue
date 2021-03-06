<template>
	<div>
		<h1>
			{{ $t('config.daemon.scheduler.title') }}
		</h1>
		<CCard>
			<CCardHeader class='border-0'>
				<div class='float-right'>
					<CButton
						color='success'
						size='sm'
						to='/config/daemon/scheduler/add'
					>
						<CIcon :content='icons.add' size='sm' />
						{{ $t('table.actions.add') }}
					</CButton> <CButton
						color='primary'
						size='sm'
						@click='importConfig.modal = true'
					>
						<CIcon :content='icons.import' size='sm' />
						{{ $t('forms.import') }}
					</CButton> <CButton
						color='secondary'
						size='sm'
						@click='exportScheduler'
					>
						<CIcon :content='icons.export' size='sm' />
						{{ $t('forms.export') }}
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
								:to='"/config/daemon/scheduler/edit/" + (retrieved === "daemon" ? item.taskId : item.id)'
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
					{{ $t('config.daemon.scheduler.import.title') }}
				</h5>
			</template>
			<CForm>
				<div class='form-group'>
					<CInputFile
						ref='schedulerImport'
						accept='application/json,.zip'
						:label='$t("config.daemon.scheduler.import.file")'
						@input='isEmpty'
						@click='isEmpty'
					/>
					<p v-if='importConfig.empty && !importConfig.first' style='color: red'>
						{{ $t('config.daemon.scheduler.import.errors.fileEmpty') }}
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
					{{ $t('forms.import') }}
				</CButton>
			</template>
		</CModal>
		<CModal
			color='danger'
			:show='deleteTask !== null'
		>
			<template #header>
				<h5 class='modal-title'>
					{{ $t('config.daemon.scheduler.messages.deleteTitle') }}
				</h5>
			</template>
			{{ $t('config.daemon.scheduler.messages.deletePrompt', {task: deleteTask}) }}
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
import {Component, Vue, Watch} from 'vue-property-decorator';
import {CButton, CCard, CCardBody, CCardHeader, CForm, CIcon, CInputFile, CModal} from '@coreui/vue/src';
import {cilPencil, cilPlus, cilTrash, cilArrowTop, cilArrowBottom} from '@coreui/icons';
import {fileDownloader} from '../../helpers/fileDownloader';
import {TextareaAutogrowDirective} from 'vue-textarea-autogrow-directive/src/VueTextareaAutogrowDirective';
import SchedulerService from '../../services/SchedulerService';
import ServiceService from '../../services/ServiceService';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import {DateTime, Duration} from 'luxon';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';
import {Dictionary} from 'vue-router/types/router';
import {IField} from '../../interfaces/coreui';
import {AxiosError, AxiosResponse} from 'axios';
import {ITaskDaemon, ITaskRest, ITaskTimeSpec} from '../../interfaces/scheduler';
import {MutationPayload} from 'vuex';
import {mapGetters} from 'vuex';
import {versionHigherEqual} from '../../helpers/versionChecker';

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
	computed: {
		...mapGetters({
			daemonVersion: 'daemonVersion',
		}),
	},
	directives: {
		'autogrow': TextareaAutogrowDirective
	},
	metaInfo: {
		title: 'config.daemon.scheduler.title',
	}
})

/**
 * List of Daemon scheduler tasks
 */
export default class SchedulerList extends Vue {
	/**
	 * @var {boolean} daemon230 Indicates that Daemon is version 2.3.0 or higher
	 */
	private daemon230 = false

	/**
	 * @constant {Diction<string|boolean>} dateFormat Date formatting options
	 */
	private dateFormat: Dictionary<string|boolean> = {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour12: false,
		hour: 'numeric',
		minute: 'numeric',
		second: 'numeric',
	}

	/**
	 * @var {number|null} deleteTask Id of scheduler task used in remove modal
	 */
	private deleteTask: number|null = null

	/**
	 * @constant {Array<IField>} fields Array of CoreUI data table columns
	 */
	private fields: Array<IField> = [
		{
			key: 'taskId',
			label: this.$t('config.daemon.scheduler.form.task.taskId'),
		},
		{
			key: 'timeSpec',
			label: this.$t('config.daemon.scheduler.table.time'),
			filter: false,
			sorter: false,
		},
		{
			key: 'clientId',
			label: this.$t('config.daemon.scheduler.table.service'),
		},
		{
			key: 'task',
			label: this.$t('config.daemon.scheduler.table.mType'),
		},
		{
			key: 'actions',
			label: this.$t('table.actions.title'),
			filter: false,
			sorter: false,
		},
	]

	/**
	 * @constant {Dictionary<Array<string>>} icons Dictionary of CoreUI Icons
	 */
	private icons: Dictionary<Array<string>> = {
		add: cilPlus,
		edit: cilPencil,
		remove: cilTrash,
		import: cilArrowTop,
		export: cilArrowBottom,
	}

	/**
	 * @var {Dictionary<boolean>} importConfig Import modal auxiliary variables
	 */
	private importConfig: Dictionary<boolean> = {
		modal: false,
		first: true,
		empty: false,
	}

	/**
	 * @var {Array<string>} msgIds Array of message ids used for response handling
	 */
	private msgIds: Array<string> = []

	/**
	 * @var {string|null} retrieved Specifies where the message was retrieved from
	 */
	private retrieved: string|null = null

	/**
	 * @var {Array<number>} taskIds Array of scheduler task ids
	 */
	private taskIds: Array<number> = []

	/**
	 * @var {Array<Task>} tasks Array of scheduler tasks
	 */
	private tasks: Array<ITaskRest>|null = null

	/**
	 * @var {boolean} untouched Indicates whether or not scheduler tasks have been retrieved
	 */
	private untouched = true

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}

	/**
	 * @var {boolean} useRest Indicates whether the webapp should use REST API to retrieve scheduler tasks
	 */
	private useRest = true

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		this.$store.commit('spinner/SHOW');
		this.unsubscribe = this.$store.subscribe((mutation: MutationPayload) => {
			if (mutation.type === 'SOCKET_ONOPEN') { // websocket connection with daemon established
				this.useRest = false; 
				if (this.untouched) { // retrieve task only when the page is loaded
					this.getTasks();
				}
			} else if (mutation.type === 'SOCKET_ONCLOSE' ||
				mutation.type === 'SOCKET_ONERROR') { // websocket connection with daemon terminated, REST fallback
				this.useRest = true;
			} else if (mutation.type === 'SOCKET_ONSEND') { // cleanup before tasks are retrieved
				if (mutation.payload.mType === 'mngScheduler_List') {
					if (this.taskIds !== []) {
						this.taskIds = [];
						this.tasks = [];
					}
				}
			} else if (mutation.type === 'SOCKET_ONMESSAGE') {
				if (mutation.payload.mType === 'mngScheduler_List' &&
					this.msgIds.includes(mutation.payload.data.msgId)) { // caught scheduler list was sent from this component
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						this.taskIds = mutation.payload.data.rsp.tasks;
						this.taskIds.forEach(item => { // fetch each task from list of task ids
							this.getTask(item);
						});
						this.retrieved = 'daemon';
					} else {
						this.$toast.error(
							this.$t('config.daemon.scheduler.messages.listFailed').toString()
						);
					}
				} else if (mutation.payload.mType === 'mngScheduler_GetTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) { // caught get task message was sent from this component
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						if (this.tasks === null) {
							return;
						}
						let rsp = mutation.payload.data.rsp;
						const day = Number.parseInt(rsp.timeSpec.cronTime[5]);
						if (!isNaN(day)) {
							rsp.timeSpec.cronTime[5] = (day + 1).toString();
						}
						this.tasks.push(rsp);
					}
				} else if (mutation.payload.mType === 'mngScheduler_RemoveTask' &&
							this.msgIds.includes(mutation.payload.data.msgId)) {
					this.$store.commit('spinner/HIDE');
					this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
					if (mutation.payload.data.status === 0) {
						this.$toast.success(
							this.$t('config.daemon.scheduler.messages.deleteSuccess').toString()
						);
						this.getTasks();
					} else {
						this.$toast.error(
							this.$t('config.daemon.scheduler.messages.deleteFail').toString()
						);
					}
				} else if (mutation.payload.mType === 'messageError') {
					this.$store.commit('spinner/HIDE');
					if (mutation.payload.data.rsp.errorStr.includes('daemon overload')) {
						this.$toast.error(
							this.$t('iqrfnet.daemon.sendJson.form.messages.error.messageQueueFull').toString()
						);
					} else {
						this.$toast.error(
							this.$t('config.daemon.scheduler.messages.processError').toString()
						);
					}
				}
			}
		});
	}

	/**
	 * Daemon version computed property watcher to re-render elements dependent on version
	 */
	@Watch('daemonVersion')
	private updateDaemonVersion(): void {
		if (versionHigherEqual('2.3.0')) {
			this.daemon230 = true;
		}
	}

	/**
	 * Vue lifecycle hook mounted
	 */
	mounted(): void {
		this.updateDaemonVersion();
		setTimeout(() => {
			if (this.$store.state.webSocketClient.socket.isConnected) {
				this.useRest = false;
			}
			if (this.untouched) {
				this.getTasks();
			}
		}, 1000);
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.msgIds.forEach((item) => this.$store.dispatch('removeMessage', item));
		this.unsubscribe();
	}

	/**
	 * Closes scheduler task import modal
	 */
	private closeImport(): void {
		this.importConfig.modal = false;
		this.importConfig.first = true;
	}

	/**
	 * Creates a string of daemon api message types displayed in scheduler task table
	 * @param item Task message or task message object
	 * @returns {string} Task daemon api message types
	 */
	private displayMTypes(item): string {
		try {
			if (this.retrieved === 'rest') { // task retrieved from REST API, message types is array of strings or a string
				return Array.isArray(item) ? item.join(', ') : item;
			}
			if (Array.isArray(item)) { // task retrieved from daemon API and has multiple messages
				let message = '';
				item.forEach(item => {
					message += item.message.mType + ', ';
				});
				return message.slice(0, -2);
			} else { // task retrieved from daemon API and has only one message
				return item.message.mType;
			} 
		} catch(err) {
			return '';
		}
	}

	/**
	 * Retrieves list of scheduler tasks
	 */
	private getTasks(): void {
		this.untouched = false;
		this.$store.commit('spinner/SHOW');
		if (this.useRest) {
			SchedulerService.listTasksREST()
				.then((response: AxiosResponse) => {
					this.$store.commit('spinner/HIDE');
					let tasks = response.data;
					for (let idx in tasks) {
						let cronTime = tasks[idx].timeSpec.cronTime.split(' ');
						const day = Number.parseInt(cronTime[5]);
						if (!isNaN(day)) {
							cronTime[5] = (day + 1).toString();
							tasks[idx].timeSpec.cronTime = cronTime.join(' ');
						}
					}
					this.tasks = tasks;
					this.retrieved = 'rest';
				})
				.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
		} else {
			SchedulerService.listTasks(new WebSocketOptions(null, 30000, 'config.daemon.scheduler.messages.listFailed'))
				.then((msgId: string) => this.storeId(msgId));
		}
	}

	/**
	 * Retrieves a scheduler task specified by task id
	 * @param {number} taskId Scheduler task id
	 */
	private getTask(taskId: number): void {
		SchedulerService.getTask(taskId, new WebSocketOptions(null, 30000))
			.then((msgId: string) => this.storeId(msgId));
	}

	/**
	 * Removes a scheduler task
	 */
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
						this.$t('config.daemon.scheduler.messages.deleteSuccess').toString()
					);
					this.getTasks();
				})
				.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
		} else {
			SchedulerService.removeTask(task, new WebSocketOptions(null, 30000, 'config.daemon.scheduler.messages.deleteFail'))
				.then((msgId: string) => this.storeId(msgId));
		}
	}

	/**
	 * Stores a daemon api message id for response handling
	 */
	private storeId(msgId: string): void {
		this.msgIds.push(msgId);
		setTimeout(() => {
			if (this.msgIds.includes(msgId)) {
				this.msgIds.splice(this.msgIds.indexOf(msgId), 1);
			}
		}, 30000);
	}

	/**
	 * Exports scheduler tasks
	 */
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

	/**
	 * Imports scheduler tasks from zip file
	 */
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
							this.$t('config.daemon.scheduler.messages.importSuccess').toString()
						);
						this.$toast.info(
							this.$t('service.iqrf-gateway-daemon.messages.restart')
								.toString()
						);
						this.getTasks();
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
						this.$t('config.daemon.scheduler.messages.importInvalidFile').toString()
					);
				} else if (error.response.status === 415) {
					this.$toast.error(
						this.$t('config.daemon.scheduler.messages.importInvalidFormat')
							.toString()
					);
				}
			});
	}

	/**
	 * Extracts files from import modal file input
	 * @returns {FileList} List of uploaded files
	 */
	private getFile(): FileList {
		const input = ((this.$refs.schedulerImport as CInputFile).$el.children[1] as HTMLInputElement);
		return (input.files as FileList);
	}

	/**
	 * Checks if import modal file input is empty
	 */
	private isEmpty(): void {
		if (this.importConfig.first) {
			this.importConfig.first = false;
		}
		const file = this.getFile();
		this.importConfig.empty = file.length === 0;
	}

	/**
	 * Creates time string used in scheduler task data table from task time specification
	 * @param {TaskTimeSpec} item Scheduler task time specification
	 * @returns {string} Human readable time message
	 */
	private timeString(item: ITaskTimeSpec): string|undefined {
		try {
			if (item.exactTime) {
				return 'oneshot (' + DateTime.fromISO(item.startTime).toLocaleString(this.dateFormat) + ')';
			}
			if (item.periodic) { // human readable periodic time specification conversion
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
			if (item.cronTime.length > 0) { // time specification in cron, conversion from array to string
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

<style scoped>

.card-header {
	padding-bottom: 0;
}

</style>
