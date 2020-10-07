<template>
	<div>
		<h1>{{ $t('translatorConfig.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-if='config !== null' v-slot='{ invalid }'>
				<CForm @submit.prevent='processSubmit'>
					<CRow>
						<CCol md='6'>
							<h3>{{ $t("translatorConfig.form.mqtt.title") }}</h3>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }' 
								rules='required'
								:custom-messages='{required: "translatorConfig.form.messages.missing.maddr"}'
							>
								<CInput
									v-model='config.mqtt.addr'
									:label='$t("translatorConfig.form.mqtt.addr")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }' 
								rules='required|integer|between:1,49151'
								:custom-messages='{
									integer: "translatorConfig.form.messages.integer",
									required: "translatorConfig.form.messages.port",
									between: "translatorConfig.form.messages.port"
								}'
							>
								<CInput
									v-model.number='config.mqtt.port'
									type='number'
									min='1'
									max='49151'
									:label='$t("translatorConfig.form.mqtt.port")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }' 
								rules='required|client_id'
								:custom-messages='{
									required: "translatorConfig.form.messages.missing.mcid",
									client_id: "translatorConfig.form.messages.invalid.mcid",
								}'
							>
								<CInput
									v-model='config.mqtt.cid'
									:label='$t("translatorConfig.form.mqtt.cid")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'								
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }' 
								rules='topic|required'
								:custom-messages='{
									required: "translatorConfig.form.messages.missing.mtopic",
									topic: "translatorConfig.form.messages.invalid.mtopic",
								}'
							>
								<CInput
									v-model='config.mqtt.topic'
									:label='$t("translatorConfig.form.mqtt.topic")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }'
							>
								<CInput
									v-model='config.mqtt.user'
									autocomplete='off'
									:label='$t("translatorConfig.form.mqtt.user")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }'
							>
								<CInput
									v-model='config.mqtt.pw'
									autocomplete='off'
									:label='$t("translatorConfig.form.mqtt.pw")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
									:type='visibility'
								>
									<template #append-content>
										<span @click='changeVisibility'>
											<CIcon
												v-if='visibility ==="password"'
												:content='$options.icons.hidden'
											/>
											<CIcon v-else :content='$options.icons.shown' />
										</span>
									</template>
								</CInput>
							</ValidationProvider>
						</CCol>
						<CCol md='6'>
							<h3>{{ $t("translatorConfig.form.rest.title") }}</h3>
							<ValidationProvider
								v-slot='{ errors, touched, valid }'
								rules='required'
								:custom-messages='{required: "translatorConfig.form.messages.missing.raddr"}'
							>
								<CInput
									v-model='config.rest.addr' 
									:label='$t("translatorConfig.form.rest.addr")' 
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }'
								rules='required|integer|between:1,49151'
								:custom-messages='{
									integer: "translatorConfig.form.messages.integer",
									required: "translatorConfig.form.messages.port",
									between: "translatorConfig.form.messages.port"
								}'
							>
								<CInput
									v-model.number='config.rest.port'
									type='number'
									min='1'
									max='49151'
									:label='$t("translatorConfig.form.rest.port")' 
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
							<ValidationProvider 
								v-slot='{ errors, touched, valid }' 
								rules='api_key_r|required'
								:custom-messages='{
									required: "translatorConfig.form.messages.missing.rapi_key",
									api_key_r: "translatorConfig.form.messages.invalid.api_key"
								}'
							>
								<CInput
									v-model='config.rest.api_key' 
									:label='$t("translatorConfig.form.rest.api_key")'
									:is-valid='touched ? valid : null'
									:invalid-feedback='$t(errors[0])'
								/>
							</ValidationProvider>
						</CCol>
					</CRow>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script>
import {CButton, CCard, CForm, CIcon, CInput} from '@coreui/vue/src';
import {cilLockLocked, cilLockUnlocked} from '@coreui/icons';
import {between, integer, required} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import FormErrorHandler from '../../helpers/FormErrorHandler';
import FeatureConfigService from '../../services/FeatureConfigService';

export default {
	name: 'TranslatorConfig',
	components: {
		CButton,
		CCard,
		CForm,
		CIcon,
		CInput,
		ValidationObserver,
		ValidationProvider
	},
	beforeRouteEnter(to, from, next) {
		next(vm => {
			if (!vm.$store.getters['features/isEnabled']('iqrfGatewayTranslator')) {
				vm.$toast.error(
					vm.$t('translatorConfig.messages.disabled').toString()
				);
				vm.$router.push(from.path);
			}
		});
	},
	data() {
		return {
			name: 'translator',
			visibility: 'password',
			config: null,
		};
	},
	created() {
		extend('between', between);
		extend('integer', integer);
		extend('required', required);
		extend('api_key_r', (key) => {
			const regex = RegExp('^[./A-Za-z0-9]{22}\\.[A-Za-z0-9+/=]{44}$');
			return regex.test(key);
		});
		extend('client_id', (id) => {
			const regex = RegExp('^[A-F0-9]{16}$');
			return regex.test(id);
		});
		extend('topic', (topic) => {
			const regex = RegExp('^gateway\\/[A-F0-9]{16}\\/rest\\/requests\\/\\+\\/#$');
			return regex.test(topic);
		});
		this.getConfig();
	},
	methods: {
		getConfig() {
			this.$store.commit('spinner/SHOW');
			FeatureConfigService.getConfig(this.name)
				.then((response) => {
					this.$store.commit('spinner/HIDE');
					this.config = response.data;
				})
				.catch((error) => {
					FormErrorHandler.configError(error);
				});
		},
		processSubmit() {
			this.$store.commit('spinner/SHOW');
			FeatureConfigService.saveConfig(this.name, this.config)
				.then(() => {
					this.$store.commit('spinner/HIDE');
					this.$toast.success(this.$t('forms.messages.saveSuccess').toString());
				})
				.catch((error) => {
					FormErrorHandler.configError(error);
				});
		},
		changeVisibility() {
			this.visibility = this.visibility === 'password' ? 'text' : 'password';
		},
	},
	icons: {
		hidden: cilLockLocked,
		shown: cilLockUnlocked
	},
	metaInfo: {
		title: 'translatorConfig.description',
	},
};

</script>