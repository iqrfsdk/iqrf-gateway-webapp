import store from '../store';
import axios, {AxiosResponse} from 'axios';
import {authorizationHeader} from '../helpers/authorizationHeader';

/**
 * Scheduler service
 */
class SchedulerService {
	/**
	 * Adds a new task via the Daemon API
	 * @param taskId scheduler task ID
	 * @param clientId client ID
	 * @param task scheduler task
	 * @param timeSpec scheduler task time settings
	 */
	addTask(taskId: number, clientId: string, task: any, timeSpec: Record<string, unknown>) {
		const tasks = JSON.parse(JSON.stringify(task));
		tasks.forEach((item: any) => {
			item.message = JSON.parse(item.message);
		});
		return store.dispatch('sendRequest', {
			'mType': 'mngScheduler_AddTask',
			'data': {
				'req': {
					'clientId': clientId,
					'taskId': taskId,
					'task': tasks,
					'timeSpec': timeSpec,
					'persist': true,
				},
				'returnVerbose': true,
			},
		});
	}

	/**
	 * Adds a new task via the REST API
	 * @param taskId scheduler task ID
	 * @param clientId client ID
	 * @param task scheduler task
	 * @param timeSpec scheduler task time settings
	 */
	addTaskREST(taskId: number, clientId: string, task: any, timeSpec: Record<string, unknown>): Promise<AxiosResponse> {
		const tasks = JSON.parse(JSON.stringify(task));
		tasks.forEach((item: any) => {
			item.message = JSON.parse(item.message);
		});
		const newTask = {
			'taskId': taskId,
			'clientId': clientId,
			'task': tasks,
			'timeSpec': timeSpec
		};
		return axios.post('scheduler', newTask, {headers: authorizationHeader()});
	}

	/**
	 * Edits an existing task via the REST API
	 * @param oldTaskId existing task ID
	 * @param taskId new task ID
	 * @param clientId client ID
	 * @param task scheduler task
	 * @param timeSpec scheduler task time settings
	 */
	editTaskREST(oldTaskId: number, taskId: number, clientId: string, task: any, timeSpec: Record<string, unknown>): Promise<AxiosResponse> {
		const tasks = JSON.parse(JSON.stringify(task));
		tasks.forEach((item: any) => {
			item.message = JSON.parse(item.message);
		});
		const editTask = {
			'taskId': taskId,
			'clientId': clientId,
			'task': tasks,
			'timeSpec': timeSpec
		};
		return axios.put('scheduler/' + oldTaskId, editTask, {headers: authorizationHeader()});
	}

	/**
	 * Retrieves scheduler tasks via the Daemon API
	 */
	listTasks() {
		return store.dispatch('sendRequest', {
			'mType': 'mngScheduler_List',
			'data': {
				'req': {
					'clientId': 'SchedulerMessaging',
				},
				'returnVerbose': true,
			},
		});
	}

	/**
	 * Retrieves scheduler tasks via the REST API 
	 */
	listTasksREST(): Promise<AxiosResponse> {
		return axios.get('scheduler', {headers: authorizationHeader()});
	}

	/**
	 * Retrieves task specified by ID via the Daemon API
	 * @param taskId scheduler task ID
	 */
	getTask(taskId: number) {
		return store.dispatch('sendRequest', {
			'mType': 'mngScheduler_GetTask',
			'data': {
				'req': {
					'clientId': 'SchedulerMessaging',
					'taskId': taskId,
				},
				'returnVerbose': true,
			},
		});
	}

	/**
	 * Retrieves task specified by ID via the REST API
	 * @param taskId scheduler task ID
	 */
	getTaskREST(taskId: number): Promise<AxiosResponse> {
		return axios.get('scheduler/' + taskId, {headers: authorizationHeader()});
	}	

	/**
	 * Removes a task specified by ID via the Daemon API
	 * @param taskId scheduler task ID
	 */
	removeTask(taskId: number) {
		return store.dispatch('sendRequest', {
			'mType': 'mngScheduler_RemoveTask',
			'data': {
				'req': {
					'clientId': 'SchedulerMessaging',
					'taskId': taskId,
				},
				'returnVerbose': true,
			},
		});
	}

	/**
	 * Removes a task specified by ID via the REST API
	 * @param taskId scheduler ID
	 */
	removeTaskREST(taskId: number): Promise<AxiosResponse> {
		return axios.delete('/scheduler/' + taskId, {headers: authorizationHeader()});
	}
	
	/**
	 * Exports scheduler configuration
	 */
	exportConfig(): Promise<AxiosResponse> {
		return axios.get('scheduler/export', {headers: authorizationHeader(), responseType: 'arraybuffer'});
	}
	
	/**
	 * Import scheduler configuration
	 * @param config scheduler configuration
	 */
	importConfig(config: any): Promise<AxiosResponse> {
		return axios.post('scheduler/import', config, {headers: authorizationHeader()});
	}
}

export default new SchedulerService();
