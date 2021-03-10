<template>
	<div>
		<h1 v-if='$route.path === "/config/daemon/scheduler/add"'>
			{{ $t('config.daemon.scheduler.add') }}
		</h1>
		<h1 v-else>
			{{ $t('config.daemon.scheduler.edit') }}
		</h1>
		<CCard>
			<CCardBody>
				<ValidationObserver v-slot='{ invalid }'>
					<CForm @submit.prevent='saveTask'>
						<ValidationProvider
							v-slot='{errors, touched, valid}'
							rules='integer|required'
							:custom-messages='{
								required: "config.daemon.scheduler.errors.nums",
								integer: "config.daemon.scheduler.errors.nums"
							}'
						>
							<CInput
								v-model.number='taskId'
								type='number'
								:label='$t("config.daemon.scheduler.form.task.taskId")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{errors, touched, valid}'
							rules='required'
							:custom-messages='{required: "config.daemon.scheduler.errors.service"}'
						>
							<CSelect
								:value.sync='clientId'
								:label='$t("config.daemon.scheduler.table.service")'
								:options='[
									{value: "SchedulerMessaging", label: "SchedulerMessaging"}
								]'
								placeholder='Select service'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<CSelect
							:value.sync='timeSpecSelected'
							:label='$t("config.daemon.scheduler.form.task.timeSpec")'
							:options='timeSpecOptions'
						/>
						<div
							v-if='timeSpecSelected === "cron"' 
							class='form-group'
						>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='cron|required'
								:custom-messages='{
									cron: "config.daemon.scheduler.errors.cron",
									required: "config.daemon.scheduler.errors.cron"
								}'
							>
								<label for='cronTime'>
									{{ $t("config.daemon.scheduler.form.task.cronTime") }}
								</label> <CBadge v-if='cronMessage !== null' :color='valid ? "info" : "danger"'>
									{{ cronMessage }}
								</CBadge>
								<CInput
									id='cronTime'
									v-model='timeSpec.cronTime'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
									@input='cronMessage = null'
								/>
							</ValidationProvider>
						</div>
						<ValidationProvider
							v-if='timeSpecSelected === "periodic"'
							v-slot='{errors, touched, valid}'
							rules='integer|required|min:0'
							:custom-messages='{
								required: "config.daemon.scheduler.errors.period",
								integer: "config.daemon.scheduler.errors.period",
								min: "config.daemon.scheduler.errors.period"
							}'
						>
							<CInput
								v-model.number='timeSpec.period'
								type='number'
								min='0'
								:label='$t("config.daemon.scheduler.form.task.period")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
							/>
						</ValidationProvider>
						<div
							v-if='timeSpecSelected === "exact"' 
							class='form-group'
						>
							<label for='exactTime'>
								{{ $t("config.daemon.scheduler.form.task.startTime") }}
							</label>
							<Datetime
								id='exactTime'
								v-model='timeSpec.startTime'
								type='datetime'
								:format='dateFormat'
								:min-datetime='new Date().toISOString()'
								input-class='form-control'
							/>
						</div>
						<h3>{{ $t('config.daemon.scheduler.form.messages.title') }}</h3><hr>
						<div v-for='i of tasks.length' :key='i' class='form-group'>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required'
								:custom-messages='{required: "config.daemon.scheduler.errors.service"}'
							>
								<CSelect
									:value.sync='tasks[i-1].messaging'
									:label='$t("config.daemon.scheduler.form.messages.messaging")'
									:placeholder='$t("config.daemon.scheduler.form.messages.messagingPlaceholder")'
									:options='messagings'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider
								v-slot='{errors, touched, valid}'
								rules='required|json|mType'
								:custom-messages='{
									required: "config.daemon.scheduler.errors.message",
									json: "iqrfnet.sendJson.form.messages.invalid",
									mType: "iqrfnet.sendJson.form.messages.mType"
								}'
							>
								<CTextarea
									v-model='tasks[i-1].message'
									v-autogrow
									:label='$t("config.daemon.scheduler.form.messages.label")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<CButton
								v-if='tasks.length > 1'
								color='danger'
								@click='removeMessage(i-1)'
							>
								{{ $t('config.daemon.scheduler.form.messages.remove') }}
							</CButton> <CButton
								v-if='i === tasks.length'
								color='success'
								@click='addMessage'
							>
								{{ $t('config.daemon.scheduler.form.messages.add') }}
							</CButton>
						</div>
						<CButton type='submit' color='primary' :disabled='invalid || (timeSpecSelected === "exact" && timeSpec.startTime === "")'>
							{{ $t('forms.save') }}
						</CButton>
					</CForm>
				</ValidationObserver>
			</CCardBody>
		</CCard>
	</div>
</template>

<script lang='ts'>
// components
import {Component, Prop, Vue, Watch} from 'vue-property-decorator';
import {CBadge, CButton, CCard, CCardBody, CCardHeader, CForm, CInput, CInputCheckbox, CSelect, CTextarea} from '@coreui/vue/src';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import {integer, required, min_value} from 'vee-validate/dist/rules';
import {TextareaAutogrowDirective} from 'vue-textarea-autogrow-directive';
import {Datetime} from 'vue-datetime';

// functions and modules
import {mapGetters} from 'vuex';
import {MetaInfo} from 'vue-meta';
import cron from 'cron-validate';
import cronstrue from 'cronstrue';

// helpers
import {versionHigherEqual} from '../../helpers/versionChecker';
import FormErrorHandler from '../../helpers/FormErrorHandler';

// services
import DaemonConfigurationService from '../../services/DaemonConfigurationService';
import SchedulerService from '../../services/SchedulerService';

// interfaces
import {AxiosError, AxiosResponse} from 'axios';
import {Dictionary} from 'vue-router/types/router';
import {IOption} from '../../interfaces/coreui';
import {ITaskRest, ITaskDaemon, ITaskMessage, ITaskTimeSpec} from '../../interfaces/scheduler';
import {MutationPayload} from 'vuex';
import {WebSocketOptions} from '../../store/modules/webSocketClient.module';
import {WsMessaging} from '../../interfaces/messagingInterfaces';

interface ILocalTask {
	message: string
	messaging: string
}

enum TimeSpecTypes {
	CRON = 'cron',
	EXACT = 'exact',
	PERIODIC = 'periodic'
}

@Component({
	components: {
		CBadge,
		CButton,
		CCard,
		CCardBody,
		CCardHeader,
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
		'autogrow': TextareaAutogrowDirective,
	},
	computed: {
		...mapGetters({
			daemonVersion: 'daemonVersion',
		}),
	},
	metaInfo(): MetaInfo {
		return {
			title: (this as unknown as SchedulerForm).pageTitle
		};
	}
})

/**
 * Scheduler task form component
 */
export default class SchedulerForm extends Vue {
	/**
	 * @var {string} clientId Scheduler task client id
	 */
	private clientId = 'SchedulerMessaging'

	/**
	 * @constant {Dictionary<string>} components Names of messaging components
	 */
	private components: Dictionary<string> = {
		mq: 'iqrf::MqMessaging',
		mqtt: 'iqrf::MqttMessaging',
		websocket: 'iqrf::WebsocketMessaging'
	}

	/**
	 * @var {string|null} cronMessage Converted message from time setting in cron format
	 */
	private cronMessage: string|null = null

	/**
	 * @var {boolean} daemon230 Indicates whether Daemon version is 2.3.0 or higher
	 */
	private daemon230 = false
	
	/**
	 * @constant {Dictionary<string|boolean>} dateFormat Date formatting options
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
	 * @var {Array<IOption>} messagings Array of available messaging component instances
	 */
	private messagings: Array<IOption> = []

	/**
	 * @var {Array<string>} msgIds Array of daemon api message ids
	 */
	private msgIds: Array<string> = []

	/**
	 * @constant {number} taskId Scheduler task id in epoch seconds
	 */
	private taskId = Math.floor(Date.now() / 1000)

	/**
	 * @var {Array<ILocalTask>} tasks Array of scheduler task messages
	 */
	private tasks: Array<ILocalTask> = [{message: '', messaging: ''}]

	/**
	 * @var {ITaskTimeSpec} timeSpec Scheduler task time specification
	 */
	private timeSpec: ITaskTimeSpec = {
		cronTime: '',
		periodic: false,
		period: 1,
		exactTime: false,
		startTime: ''
	}

	/**
	 * @var {TimeSpecTypes} timeSpecSelected Selected task time specification type
	 */
	private timeSpecSelected = TimeSpecTypes.EXACT

	/**
	 * @constant {Array<IOption>} timeSpecOptions Scheduler task time specification options
	 */
	private timeSpecOptions: Array<IOption> = [
		{
			value: TimeSpecTypes.EXACT,
			label: this.$t('config.daemon.scheduler.form.task.timeSpecTypes.exact').toString(),
		},
		{
			value: TimeSpecTypes.PERIODIC,
			label: this.$t('config.daemon.scheduler.form.task.timeSpecTypes.periodic').toString(),
		},
		{
			value: TimeSpecTypes.CRON,
			label: this.$t('config.daemon.scheduler.form.task.timeSpecTypes.cron').toString(),
		},
	]

	/**
	 * Component unsubscribe function
	 */
	private unsubscribe: CallableFunction = () => {return;}
	
	/**
	 * @var {boolean} untouched Indicates whether props for creation of scheduler tasks have been retrieved
	 */
	private untouched = true

	/**
	 * @var {boolean} useRest Indicates whether the webapp should use REST API to retrieve scheduler task props
	 */
	private useRest = true

	/**
	 * @constant cronPreset Cron validate presets
	 */
	private cronPreset = {
		presetId: 'schedulerCron',
		useSeconds: true,
		useYears: true,
		useAliases: true,
		useBlankDay: true,
		allowOnlyOneBlankDayField: false,
		seconds: {
			minValue: 0,
			maxValue: 0,
		},
		minutes: {
			minValue: 0,
			maxValue: 59,
		},
		hours: {
			minValue: 0,
			maxValue: 23,
		},
		daysOfMonth: {
			minValue: 1,
			maxValue: 31,
		},
		months: {
			minValue: 0,
			maxValue: 12,
		},
		daysOfWeek: {
			minValue: 0,
			maxValue: 6,
		},
		years: {
			minValue: 1970,
			maxValue: 2099,
		},
	}

	/**
	 * @constant {Array<string>} dayAliases Array of day aliases
	 */
	private dayAliases = [
		'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'
	]

	/**
	 * @constant {Array<string>} monthAliases Array of month aliases
	 */
	private monthAliases = [
		'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'
	]

	/**
	 * @property {number} id Id of existing scheduler task
	 */
	@Prop({required: false, default: null}) id!: number

	/**
	 * Vue lifecycle hook created
	 */
	created(): void {
		this.$store.commit('spinner/SHOW');
		extend('integer', integer);
		extend('min', min_value);
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
		extend('cron', (cronstring: string) => {
			if (cronstring[0] === '@') {
				const cronAlias = this.getCronAlias(cronstring);
				if (cronAlias !== undefined) {
					this.cronMessage = cronstrue.toString(cronAlias);
				}
				this.cronMessage = null;
				return false;
			}
			const cronResult = cron(cronstring, {preset: this.cronPreset});
			if (cronResult.isValid()) {
				this.calculateCron(cronstring);
				return true;
			} else {
				this.cronMessage = cronResult.getError().join(',');
				return false;
			}
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
				if (!this.msgIds.includes(mutation.payload.data.msgId)) {
					if (mutation.payload.mType === 'messageError') {
						this.$store.commit('spinner/HIDE');
						this.$toast.error(
							this.$t('config.daemon.scheduler.messagess.processError').toString()
						);
					}
					return;
				}
				this.$store.commit('spinner/HIDE');
				this.$store.dispatch('removeMessage', mutation.payload.data.msgId);
				if (mutation.payload.mType === 'mngScheduler_GetTask') {
					this.handleGetTask(mutation.payload.data);
				} else if (mutation.payload.mType === 'mngScheduler_AddTask') {
					this.handleAddTask(mutation.payload.data);
				} else if (mutation.payload.mType === 'mngScheduler_RemoveTask') {
					this.handleRemoveTask(mutation.payload.data);
				} else if (mutation.payload.mType === 'messageError') {
					this.$toast.error(
						this.$t('config.daemon.scheduler.messagess.processError').toString()
					);
				}
			}
		});
		this.getMessagings();
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
			if (this.id && this.untouched) {
				this.getTask(this.id);
			}
		}, 1000);
	}

	/**
	 * Vue lifecycle hook beforeDestroy
	 */
	beforeDestroy(): void {
		this.msgIds.forEach((item: string) => this.$store.dispatch('removeMessage', item));
		this.unsubscribe();
	}

	/**
	 * Computes page title depending on the action (add, edit)
	 * @returns {string} Page title
	 */
	get pageTitle(): string {
		return this.$route.path === '/config/daemon/scheduler/add' ?
			this.$t('config.daemon.scheduler.add').toString() : this.$t('config.daemon.scheduler.edit').toString();
	}

	/**
	 * Converts cron time string into a human readable message
	 * @param {string} cronstring cRon time string
	 */
	private calculateCron(cronstring: string): void {
		const cronTime = cronstring.trim().split(' ');
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

	/**
	 * Adds another scheduler task message object
	 */
	private addMessage(): void {
		this.tasks.push({message: '', messaging: ''});
	}

	/**
	 * Removes a scheduler task message object specified by index
	 * @param {number} index Index of scheduler task message
	 */
	private removeMessage(index): void {
		this.tasks.splice(index, 1);
	}

	/**
	 * Retrieves task specified by passed id
	 * @param {number} taskId Scheduler task id
	 */
	private getTask(taskId: number): void {
		this.untouched = false;
		this.$store.commit('spinner/SHOW');
		if (this.useRest || !this.daemon230) {
			SchedulerService.getTaskREST(taskId)
				.then((response: AxiosResponse) => {
					this.handleGetTaskRest(response.data);
					this.$store.commit('spinner/HIDE');
				})
				.catch(() => {
					this.$router.push('/config/daemon/scheduler/');
					this.$toast.error(
						this.$t('config.daemon.scheduler.messages.getFail', {task: this.id})
							.toString()
					);
				});
		} else {
			SchedulerService.getTask(taskId, new WebSocketOptions(null, 30000, null, () => this.$router.push('/config/scheduler/')))
				.then((msgId: string) => this.storeId(msgId));
		}
	}

	/**
	 * Handles GetTask response from Daemon API
	 * @param {any} response Task retrieved from Daemon API
	 */
	private handleGetTask(response: any): void {
		if (response.status !== 0) {
			this.$router.push('/config/daemon/scheduler/');
			this.$toast.error(
				this.$t('config.daemon.scheduler.messages.getFail', {task: this.id})
					.toString()
			);
			return;
		}
		let taskDaemon: ITaskDaemon = response.rsp;
		taskDaemon.timeSpec.cronTime = this.fixUpCron(taskDaemon.timeSpec.cronTime);
		this.taskId = taskDaemon.taskId;
		this.clientId = taskDaemon.clientId;
		if (!taskDaemon.timeSpec.exactTime && !taskDaemon.timeSpec.periodic) {
			this.timeSpecSelected = TimeSpecTypes.CRON;
		} else if (taskDaemon.timeSpec.exactTime && !taskDaemon.timeSpec.periodic) {
			this.timeSpecSelected = TimeSpecTypes.EXACT;
		} else {
			this.timeSpecSelected = TimeSpecTypes.PERIODIC;
		}
		this.timeSpec = taskDaemon.timeSpec;
		if (Array.isArray(taskDaemon.task)) {
			this.tasks = taskDaemon.task.map((item: ITaskMessage) => ({
				messaging: item.messaging,
				message: JSON.stringify(item.message, null, 2),
			}));
		} else {
			this.tasks = [
				{
					messaging: taskDaemon.task.messaging,
					message: JSON.stringify(taskDaemon.task.message, null, 2),
				}
			];
		}
	}

	/**
	 * Handles GetTask response from REST API
	 * @param {ITaskRest} taskRest Task retrieved from REST API
	 */
	private handleGetTaskRest(taskRest: ITaskRest): void {
		taskRest.timeSpec.cronTime = this.fixUpCron(taskRest.timeSpec.cronTime);
		this.taskId = taskRest.taskId;
		this.clientId = taskRest.clientId;
		if (!taskRest.timeSpec.exactTime && !taskRest.timeSpec.periodic) {
			this.timeSpecSelected = TimeSpecTypes.CRON;
		} else if (taskRest.timeSpec.exactTime && !taskRest.timeSpec.periodic) {
			this.timeSpecSelected = TimeSpecTypes.EXACT;
		} else {
			this.timeSpecSelected = TimeSpecTypes.PERIODIC;
		}
		this.timeSpec = taskRest.timeSpec;
		this.tasks = taskRest.task.map((item: ITaskMessage) => ({
			messaging: item.messaging,
			message: JSON.stringify(item.message, null, 2),
		}));
	}

	/**
	 * Fixes up cron time string to 1-7 day range
	 * @param {string|Array<string>} cron cron time string or array of cron time items
	 * @returns {string} cron time string with day in 1-7 range
	 */
	private fixUpCron(cron: string|Array<string>): string {
		let cronArray = Array.isArray(cron) ? cron : cron.split(' ');
		const cronDay = Number.parseInt(cronArray[5]);
		if (!isNaN(cronDay)) {
			cronArray[5] = (cronDay + 1).toString();
		}
		return cronArray.join(' ').trim();
	}

	/**
	 * Stores message id for later response handling
	 * @param {string} msgId Daemon api message id
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
	 * Retrieves instances of Daemon messaging components
	 */
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
				this.$router.push('/config/daemon/scheduler/');
				this.$toast.error(
					this.$t('config.daemon.scheduler.messages.rest.messagingFail').toString()
				);
			});
	}

	/**
	 * Prepares task time specification before submitting
	 * @returns {ITaskTimeSpec} Submission ready time specification object
	 */
	private prepareTaskToSubmit(): ITaskTimeSpec {
		let timeSpec = JSON.parse(JSON.stringify(this.timeSpec));
		if (this.timeSpecSelected === TimeSpecTypes.EXACT) { // exact time, reset others
			timeSpec.cronTime = Array(7).fill('');
			timeSpec.exactTime = true;
			timeSpec.periodic = false;
			timeSpec.period = 0;
			let date = new Date(timeSpec.startTime);
			date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
			timeSpec.startTime = date.toISOString().split('.')[0];
		} else if (this.timeSpecSelected === TimeSpecTypes.PERIODIC) { // periodic time, reset others
			timeSpec.cronTime = Array(7).fill('');
			timeSpec.exactTime = false;
			timeSpec.periodic = true;
			timeSpec.startTime = '';
		} else { // cron time, reset others
			timeSpec.cronTime = timeSpec.cronTime.replace('?', '*').split(' ');
			if (timeSpec.cronTime.length === 1) { // potentially using alias
				const alias = this.getCronAlias(timeSpec.cronTime[0]);
				if (alias !== undefined) {
					timeSpec.cronTime = [alias, '', '', '', '', '', ''];
				}
			}
			if (this.dayAliases.includes(timeSpec.cronTime[5])) { // day alias translation
				timeSpec.cronTime[5] = this.dayAliases.indexOf(timeSpec.cronTime[5]);
			}
			const cronDay = Number.parseInt(timeSpec.cronTime[5]);
			if (!isNaN(cronDay)) {
				timeSpec.cronTime[5] = (cronDay - 1).toString();
			}
			if (this.monthAliases.includes(timeSpec.cronTime[4])) {
				timeSpec.cronTime[4] = (this.monthAliases.indexOf(timeSpec.cronTime[4]) + 1).toString();
			}
			timeSpec.exactTime = timeSpec.periodic = false;
			timeSpec.period = 0;
			timeSpec.startTime = '';
		}
		return timeSpec;
	}

	/**
	 * Processes time specification for daemon api and then saves scheduler task
	 */
	private saveTask(): void {
		this.$store.commit('spinner/SHOW');
		if (this.$route.path === '/config/daemon/scheduler/add') {
			if (this.useRest) {
				SchedulerService.addTaskREST(this.taskId, this.clientId, this.tasks, this.prepareTaskToSubmit())
					.then(() => this.successfulSave())
					.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
			} else {
				SchedulerService.addTask(this.taskId, this.clientId, this.tasks, this.prepareTaskToSubmit(), new WebSocketOptions(
					null, 30000, 'config.daemon.scheduler.messages.processError'))
					.then((msgId: string) => this.storeId(msgId));
			}
		} else {
			if (this.useRest) {
				SchedulerService.editTaskREST(this.id, this.taskId, this.clientId, this.tasks, this.prepareTaskToSubmit())
					.then(() => this.successfulSave())
					.catch((error: AxiosError) => FormErrorHandler.schedulerError(error));
			} else {
				SchedulerService.removeTask(this.id, new WebSocketOptions(null, 30000, 'config.daemon.scheduler.messages.deleteFail'))
					.then((msgId: string) => this.storeId(msgId));
			}
		}
	}

	/**
	 * Handles Remove Task response from Daemon API
	 * @param {any} response Daemon API response
	 */
	private handleRemoveTask(response: any): void {
		if (response.status !== 0) {
			this.$toast.error(
				this.$t('config.daemon.scheduler.messages.deleteFail').toString()
			);
			return;
		}
		SchedulerService.addTask(
			this.taskId,
			this.clientId,
			this.tasks,
			this.prepareTaskToSubmit(),
			new WebSocketOptions(null, 30000, 'config.daemon.scheduler.messages.processError')
		).then((msgId: string) => this.storeId(msgId));
	}

	/**
	 * Retrieves cron alias from predefined strings
	 * @param {string} input Cron time string
	 * @returns {string|undefined} Cron time alias if one exists for received time string
	 */
	private getCronAlias(input: string): string|undefined {
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

	/**
	 * Handles Add task response from Daemon API
	 * @param {any} response Daemon API response
	 */
	private handleAddTask(response: any): void {
		if (response.status === 0) {
			this.successfulSave();
		} else {
			this.$toast.error(
				this.$t('config.daemon.scheduler.messages.processError').toString()
			);
		}
	}

	/**
	 * Handles successful REST API response
	 */
	private successfulSave(): void {
		this.$store.commit('spinner/HIDE');
		if (this.$route.path === '/config/daemon/scheduler/add') {
			this.$toast.success(
				this.$t('config.daemon.scheduler.messages.addSuccess').toString()
			);
		} else {
			this.$toast.success(
				this.$t('config.daemon.scheduler.messages.editSuccess', {task: this.id}).toString()
			);
		}
		this.$router.push('/config/daemon/scheduler/');
		
	}
}
</script>
