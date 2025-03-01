<template>
  <div class="auth-layout-wrap">
    <div class="auth-content" >
      <div class="card o-hidden" v-if="isLoading">
        <div class="row">
          <div class="col-md-12">
            <div class="p-4">
              <div class="auth-logo text-center mb-30">
                <img :src="'/images/logo.png'">
              </div>
              <h1 class="mb-3 text-18">{{$t('SignIn')}}</h1>
              <validation-observer ref="submit_login">
              <b-form @submit.prevent="Submit_Login">
                <validation-provider
                    name="Email Address"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Email_Address')" class="text-12">
                      <v-select 
                        autocomplete="off"
                        v-model="email"
                        :reduce="label => label.value"
                        :options="dataFromController.map(user => ({label: `${user.firstname} ${user.lastname}`, value: user.email}))"
                        :placeholder="$t('PleaseSelect')"
                      />
                      <b-form-invalid-feedback id="Email-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <validation-provider
                    name="Password"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('password')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Password-feedback"
                        class="form-control-rounded"
                        type="password"
                        v-model="password"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="Password-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <b-button
                    type="submit"
                    tag="button"
                    class="btn-rounded btn-block mt-2"
                    variant="primary mt-2"
                    :disabled="loading"
                  >{{$t('SignIn')}}</b-button>
                  <div v-once class="typo__p" v-if="loading">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
                </b-form>
              </validation-observer>

              <div class="mt-3 text-center">
                <a href="/password/reset"  class="text-muted">
                  <u>{{$t('Forgot_Password')}}</u>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="password-keypad">
        <div class="keypad">
          <!-- Render keypad buttons from 0 to 9 -->
          <div class="row">
          <button class="col-4 btn btn-secondary" v-for="num in 9" :key="num" @click="addDigit(num)">{{ num }}</button>
          </div>
          <!-- Add the Delete and Reset buttons -->
            <div class="row">
            <button class="col-4 btn btn-secondary" @click="resetPassword">Reset</button>
            <button class="col-4 btn btn-secondary" @click="addDigit(0)">0</button>
            <button class="col-4 btn btn-secondary" @click="deleteDigit">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapGetters, mapActions } from "vuex";
import NProgress from "nprogress";

export default {
  props: {
    dataFromController: {
      type: Array,
      required: true
    }
  },
  metaInfo: {
    title: "SignIn"
  },
  data() {
    return {
      email: "",
      password: "",
      isLoading: true,
      userId: "",
      users: [],
      passwordDigits: [],
      loading: false
    };
  },
  computed: {
    ...mapGetters(["isAuthenticated", "error"])
  },

  methods: {
    addDigit(num) {
      this.passwordDigits.push(num); // Add the digit to the password array
      this.updatePassword();
    },
    deleteDigit() {
      if (this.passwordDigits.length > 0) {
        this.passwordDigits.pop(); // Remove the last entered digit
        this.updatePassword();
      }
    },
    resetPassword() {
      this.passwordDigits = []; // Reset the password array
      this.updatePassword();
    },
    updatePassword() {
      const selectedDigits = this.passwordDigits.join('');
      this.password = selectedDigits;
    },
    //------------- Submit Form login
    Submit_Login() {
      this.$refs.submit_login.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Login();
        }
      });
    },

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    Login() {
      let self = this;
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      self.loading = true;
      axios
        .post("/login",{
          email: self.email,
          password: self.password
        },
        {
          baseURL: '',
        })
        .then(response => {

            this.makeToast(
              "success",
              this.$t("Successfully_Logged_In"),
              this.$t("Success")
            );

          window.location = '/';
           
          NProgress.done();
          this.loading = false;
        })
        .catch(error => {
          NProgress.done();
          this.loading = false;
          this.makeToast(
              "danger",
              this.$t("Incorrect_Login"),
              this.$t("Failed")
            );
        });
    },
    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    }
  },
};
</script>
