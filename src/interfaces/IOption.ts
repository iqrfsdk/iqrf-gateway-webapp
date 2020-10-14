import VueI18n from 'vue-i18n';
import { FileFormat } from '../iqrfNet/fileFormat';
import { SecurityFormat } from '../iqrfNet/securityFormat';

/**
 * Coreui select component option interface
 */
export interface IOption {
	value: FileFormat|SecurityFormat|null
	label: VueI18n.TranslateResult
}