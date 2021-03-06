import { Dictionary } from 'vue-router/types/router';

/**
 * Scheduler task time interface
 */
export interface ITaskTimeSpec {
	cronTime: Array<string>|string
	exactTime: boolean
	period: number
	periodic: boolean
	startTime: string
}

/**
 * Scheduler task dpa message interface
 */
export interface ITaskDpaMessage {
	mType: string
	data: Dictionary<unknown>
}

/**
 * Scheduler task message interface
 */
export interface ITaskMessage {
	message: ITaskDpaMessage
	messaging: string
}

/**
 * Scheduler task interface
 */
export interface ITaskBase {
	clientId: string
	taskId: number
	timeSpec: ITaskTimeSpec
}

/**
 * Scheduler task from REST interface
 */
export interface ITaskRest extends ITaskBase {
	task: Array<ITaskMessage>
}

/**
 * Scheduler task from Daemon interface
 */
export interface ITaskDaemon extends ITaskBase {
	task: Array<ITaskMessage>|ITaskMessage
}