<template>
	<div>
		<h1>{{ $t('core.user.add.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-slot='{ invalid }'>
				<CForm @submit.prevent='handleSubmit'>
					<ValidationProvider
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "core.user.messages.missing.username",
						}'
					>
						<CInput
							v-model='username'
							:label='$t("core.user.username")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "core.user.messages.missing.password",
						}'
					>
						<CInput
							v-model='password'
							:label='$t("core.user.password")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							type='password'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-if='$store.getters["user/getRole"] === "power"'
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "core.user.messages.missing.role",
						}'
					>
						<CSelect
							:value.sync='role'
							:label='$t("core.user.role")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:placeholder='$t("core.user.messages.missing.role")'
							:options='[
								{value: "normal", label: $t("core.user.roles.normal")},
								{value: "power", label: $t("core.user.roles.power")},
							]'
						/>
					</ValidationProvider>
					<ValidationProvider
						v-if='$store.getters["user/getRole"] === "power"'
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "core.user.messages.missing.language",
						}'
					>
						<CSelect
							:value.sync='language'
							:label='$t("core.user.language")'
							:is-valid='touched ? valid : null'
							:invalid-feedback='$t(errors[0])'
							:placeholder='$t("core.user.messages.missing.language")'
							:options='[
								{value: "en", label: $t("core.user.languages.en")},
							]'
						/>
					</ValidationProvider>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.add') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Vue} from 'vue-property-decorator';
import {AxiosError} from 'axios';
import {CButton, CCard, CForm, CInput, CSelect} from '@coreui/vue/src';
import {required} from 'vee-validate/dist/rules';
import {extend, ValidationObserver, ValidationProvider} from 'vee-validate';
import UserService from '../../services/UserService';

@Component({
	components: {
		CButton,
		CCard,
		CForm,
		CInput,
		CSelect,
		ValidationObserver,
		ValidationProvider,
	},
	metaInfo: {
		title: 'core.user.add.title',
	}
})

export default class UserAdd extends Vue {
	private language = ''
	private password = ''
	private role = ''
	private username = ''

	created(): void {
		extend('required', required);
	}

	private handleSubmit(): void {
		const language = this.language ?? 'en';
		const role = this.role ?? 'normal';
		UserService.add(this.username, this.password, language, role)
			.then(() => {
				this.$router.push('/user/');
				this.$toast.success(
					this.$t('core.user.messages.add.success', {username: this.username})
						.toString());
			}).catch((error: AxiosError) => {
				if (error.response === undefined) {
					return;
				}
				if (error.response.status === 409) {
					this.$toast.error(
						this.$t('core.user.messages.conflict.username').toString()
					);
				}
			});
	}
}
</script>
