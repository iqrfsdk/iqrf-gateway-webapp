import VueI18n from 'vue-i18n';
import { FileFormat } from '../iqrfNet/fileFormat';
import { SecurityFormat } from '../iqrfNet/securityFormat';

/**
 * Coreui DataTable field interface
 */
export interface IField {
	key: string
	label: VueI18n.TranslateResult
	filter?: boolean
	sorter?: boolean
}

/**
 * Coreui select component option interface
 */
export interface IOption {
	value: FileFormat|SecurityFormat|string|null
	label: VueI18n.TranslateResult
}