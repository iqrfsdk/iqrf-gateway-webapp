import {RequiredInterface} from './requiredInterfaces';

/**
 * MqMessaging instance interface
 */
export interface MqInstance {
	acceptAsyncMsg: boolean
	component: string
	instance: string
	LocalMqName: string
	RemoteMqName: string
}

/**
 * MqttMessaging instance interface
 */
export interface MqttInstance {
	acceptAsyncMsg: boolean
	component: string
	instance: string
	BrokerAddr: string
	ClientId: string
	ConnectTimeout: number
	EnableServerCertAuth: boolean
	EnabledCipherSuites: string
	EnabledSSL: boolean
	KeepAliveInterval: number
	KeyStore: string
	MaxReconnect: number
	MinReconnect: number
	Password: string
	Persistence: number
	PrivateKey: string
	PrivateKeyPassword: string
	Qos: number
	TopicRequest: string
	TopicResponse: string
	TrustStore: string
	User: string
}

/**
 * WebsocketInterface interface
 */
export interface WsInterface {
	instanceMessaging: string
	instanceService: string
	acceptAsyncMsg: boolean
	port: number
	acceptOnlyLocalhost: boolean
	service: WsService
	messaging: WsMessaging
}

/**
 * WebsocketMessaging interface
 */
export interface WsMessaging {
	acceptAsyncMsg: boolean
	component: string
	instance: string
	RequiredInterfaces: Array<RequiredInterface>
}

/**
 * Messaging instance interface for modal windows
 */
export interface ModalInstance {
	messaging: string
	service: string
}

/**
 * WebsocketService interface
 */
export interface WsService {
	WebsocketPort: number
	acceptOnlyLocalhost: boolean
	component: string
	instance: string
}

/**
 * UdpMessaging instance interface
 */
export interface UdpInstance {
	component: string
	instance: string
	LocalPort: number
	RemotePort: number
}