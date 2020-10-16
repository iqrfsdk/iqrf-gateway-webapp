<template>
	<div>
		<h1>{{ $t('core.user.edit.title') }}</h1>
		<CCard body-wrapper>
			<ValidationObserver v-if='loaded' v-slot='{ invalid }'>
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
							required: "core.user.messages.missing.role",
						}'
					>
						<CSelect
							v-if='$store.getters["user/getRole"] === "power"'
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
						v-slot='{ valid, touched, errors }'
						rules='required'
						:custom-messages='{
							required: "core.user.messages.missing.language",
						}'
					>
						<CSelect
							v-if='$store.getters["user/getRole"] === "power"'
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
					<div v-if='$store.getters["user/getId"] === userId'>
						<ValidationProvider
							v-slot='{ valid, touched, errors }'
							:rules='newPassword !== null ? "required" : ""'
							:custom-messages='{
								required: "core.user.messages.missing.oldPassword",
							}'
						>
							<CInput
								v-model='oldPassword'
								:label='$t("core.user.oldPassword")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								type='password'
								autocomplete='current-password'
							/>
						</ValidationProvider>
						<ValidationProvider
							v-slot='{ valid, touched, errors }'
							:rules='oldPassword !== null ? "required" : ""'
							:custom-messages='{
								required: "core.user.messages.missing.newPassword",
							}'
						>
							<CInput
								v-model='newPassword'
								:label='$t("core.user.newPassword")'
								:is-valid='touched ? valid : null'
								:invalid-feedback='$t(errors[0])'
								type='password'
								autocomplete='new-password'
							/>
						</ValidationProvider>
					</div>
					<CButton color='primary' type='submit' :disabled='invalid'>
						{{ $t('forms.save') }}
					</CButton>
				</CForm>
			</ValidationObserver>
		</CCard>
	</div>
</template>

<script lang='ts'>
import {Component, Prop, Vue} from 'vue-property-decorator';
import {AxiosError, AxiosResponse} from 'axios';
import {CButton, CCard, CForm, CInput, CSelect} from '@coreui/vue/src';
import {required,} from 'vee-validate/dist/rules';
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
		title: 'core.user.edit.title',
	}
})

export default class UserEdit extends Vue {
	private language = ''
	private loaded = false
	private newPassword = ''
	private oldPassword = ''
	private role = ''
	private username = ''

	@Prop({required: true}) userId!: number

	created(): void {
		extend('required', required);
		this.$store.commit('spinner/SHOW');
		UserService.get(this.userId)
			.then((response: AxiosResponse) => {
				this.loaded = true;
				this.username = response.data.username;
				this.language = response.data.language;
				this.role = response.data.role;
				this.$store.commit('spinner/HIDE');
			})
			.catch((error: AxiosError) => {
				this.loaded = false;
				this.$store.commit('spinner/HIDE');
				if (error.response === undefined) {
					return;
				}
				if (error.response.status === 404) {
					this.$router.push('/user/');
					this.$toast.error(this.$t('core.user.messages.notFound').toString());
				}
			});
	}

	private handleSubmit(): void {
		if (this.$store.getters['user/getId'] === this.userId &&
				this.oldPassword !== null && this.newPassword !== null) {
			UserService.changePassword(this.oldPassword, this.newPassword)
				.then(() => {
					this.performEdit();
					this.signOut();
				})
				.catch(() => {
					this.$toast.error(
						this.$t('core.user.messages.invalid.oldPassword').toString()
					);
				});
		} else {
			this.performEdit();
			if (this.$store.getters['user/getId'] === this.userId) {
				this.signOut();
			}
		}

	}

	private performEdit(): Promise<void> {
		return UserService.edit(this.userId, {
			username: this.username,
			language: this.language,
			role: this.role,
		})
			.then(() => {
				this.$router.push('/user/');
				this.$toast.success(
					this.$t('core.user.messages.edit.success', {username: this.username})
						.toString()
				);
			})
			.catch((error: AxiosError) => {
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

	private signOut(): void {
		this.$store.dispatch('user/signOut')
			.then(() => {
				this.$router.push('/sign/in');
			});
	}
}
</script>
