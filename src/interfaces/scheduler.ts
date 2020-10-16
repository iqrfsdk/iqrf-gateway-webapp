import { Dictionary } from 'vue-router/types/router';

/**
 * Scheduler task time interface
 */
export interface TaskTimeSpec {
	cronTime: Array<string>|string
	exactTime: boolean
	period: number
	periodic: boolean
	startTime: string
}

/**
 * Scheduler task dpa message interface
 */
export interface TaskDpaMessage {
	mType: string
	data: Dictionary<unknown>
}

/**
 * Scheduler task message interface
 */
export interface TaskMessage {
	message: TaskDpaMessage
	messaging: string
}

/**
 * Scheduler task interface
 */
export interface Task {
	clientId: string
	task: Array<TaskMessage>
	taskId: number
	timeSpec: TaskTimeSpec
}