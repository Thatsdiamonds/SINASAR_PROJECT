<template>
  <div class="auth-body">
    <main class="auth-main">
      <div class="auth-card" :class="{ animating: isAnimating }" :aria-busy="isAnimating">
        <transition name="auth-inner" mode="out-in" @before-enter="onBeforeEnter" @after-enter="onAfterEnter" @before-leave="onBeforeLeave" @after-leave="onAfterLeave">
          <!-- Login Content inside single card -->
          <div v-if="isSignIn" key="sign-in">
            <div class="card-title">
              <h2 :style="{ color: '#15a1cb' }">Login</h2>
              <p>Masukkan username dan password untuk masuk.</p>
            </div>
            <form @submit.prevent="handleLogin">
              <div class="upper">
                <label for="login-username" class="field-label">Username</label>
                <div class="input-group">
                  <input
                    v-model="login.username"
                    type="text"
                    id="login-username"
                    name="login-username"
                    class="form-control"
                    placeholder="Masukkan username/email"
                    required
                  />
                </div>

                <label for="login-password" class="field-label">Password</label>
                <div class="input-group">
                  <input
                    v-model="login.password"
                    :type="login.showPassword ? 'text' : 'password'"
                    id="login-password"
                    name="login-password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                  />
                </div>

                <label class="checkbox">
                  <input type="checkbox" id="login-show-pass" v-model="login.showPassword" />
                  <label for="login-show-pass" class="checkbox-label">Tampilkan password</label>
                </label>
              </div>

              <div class="actions bottom">
                <AppButton
                  label="Login"
                  :loading="login.loading"
                  :success="login.success"
                  type="buttonSubmit"
                />
              </div>

              <div class="card-footer">
                <p>Belum punya akun? <a href="#" @click.prevent="goAuthType('sign-up')">Daftar di sini.</a></p>
              </div>
            </form>
          </div>

          <!-- Register Content inside single card -->
          <div v-else key="sign-up">
            <div class="card-title">
              <h2 :style="{ color: '#00a846' }">Register</h2>
              <p>Buat akun baru untuk menjadi bagian dari kami.</p>
            </div>
            <form @submit.prevent="handleRegister">
              <label for="reg-username" class="field-label">Username</label>
              <div class="input-group">
                <input
                  v-model="register.username"
                  type="text"
                  id="reg-username"
                  name="reg-username"
                  class="form-control"
                  placeholder="Masukkan username"
                  required
                />
              </div>

              <label for="reg-email" class="field-label">Email</label>
              <div class="input-group">
                <input
                  v-model="register.email"
                  type="email"
                  id="reg-email"
                  name="reg-email"
                  class="form-control"
                  placeholder="Masukkan email"
                  required
                />
              </div>

              <label for="reg-password" class="field-label">Password</label>
              <div class="input-group">
                <input
                  v-model="register.password"
                  :type="register.showPassword ? 'text' : 'password'"
                  id="reg-password"
                  name="reg-password"
                  class="form-control"
                  placeholder="Masukkan password"
                  required
                />
              </div>

              <label class="checkbox">
                <input type="checkbox" id="reg-show-pass" v-model="register.showPassword" />
                <label for="reg-show-pass" class="checkbox-label">Tampilkan password</label>
              </label>

              <!-- Role hanya untuk admin -->
              <div v-if="currentUser?.role === 'admin'" style="margin-top: .4rem;">
                <label for="role" class="field-label">Role</label>
                <div class="input-group">
                  <select v-model="register.role" id="role" name="role" class="form-control" required>
                    <option disabled value="">Pilih role</option>
                    <option value="admin">Admin</option>
                    <option value="seller">Seller</option>
                  </select>
                </div>
              </div>

              <div class="actions">
                <AppButton
                  label="Daftar"
                  :loading="register.loading"
                  :success="register.success"
                  type="buttonSubmit2"
                />
              </div>

              <div class="card-footer">
                <p>Sudah punya akun? <a href="#" @click.prevent="goAuthType('sign-in')">Masuk di sini.</a></p>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </main>
  </div>
</template>

<script>
import api from "@/services/api";
import toast from "@/services/toast";
import Spinner from "@/components/Spinner.vue";
import AppButton from "@/components/AppButton.vue";
import { getUserProfile } from "@/services/api";

export default {
  name: "AuthNextGen",
  components: { Spinner, AppButton },
  data() {
    return {
      login: {
        username: "",
        password: "",
        loading: false,
        success: false,
        error: null,
        showPassword: false,
      },
      register: {
        username: "",
        email: "",
        password: "",
        role: "seller",
        loading: false,
        success: false,
        error: null,
        showPassword: false,
      },
      currentUser: null,
      isAnimating: false,
    };
  },
  computed: {
    isSignIn() {
      const t = (this.$route.query["auth-type"] || "sign-in").toString();
      return t === "sign-in";
    },
  },
  methods: {
    goAuthType(type) {
      const target = type === "sign-up" ? "sign-up" : "sign-in";
      if (this.isAnimating) return; // prevent toggling while animating
      this.$router.replace({ path: "/auth", query: { "auth-type": target } });
    },
    onBeforeEnter() { this.isAnimating = true; },
    onAfterEnter() { this.isAnimating = false; },
    onBeforeLeave() { this.isAnimating = true; },
    onAfterLeave() { /* keep non-interactable until enter completes */ },

    async fetchCurrentUser() {
      try {
        if (!localStorage.getItem("token")) {
          this.currentUser = null;
          return;
        }
        const res = await getUserProfile();
        this.currentUser = res;
      } catch (e) {
        this.currentUser = null;
      }
    },
    async handleLogin() {
      if (this.login.username === "" || this.login.password === "") {
        this.login.error = "Username dan password harus diisi.";
        toast.error(this.login.error);
        this.login.loading = false;
        return;
      }

      this.login.loading = true;
      this.login.error = null;

      try {
        if (localStorage.getItem("token")) {
          toast.info("Sesi lama diakhiri, login ulang...");

          try {
            await api.post("/logout", {}, {
              headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
            });
          } catch (logoutErr) {
            console.warn("Gagal revoke token lama:", logoutErr);
            this.login.loading = false;
          }

          localStorage.removeItem("token");
        }

        const response = await api.post("/login", {
          login: this.login.username,
          password: this.login.password,
        });

        localStorage.setItem("token", response.data.token);

        toast.success("Login berhasil!");
        if (response.data.role === "admin") {
          this.$router.push("/admin");
        } else if (response.data.role === "seller") {
          this.$router.push("/detail-kios?lokasi=" + response.data.lokasi);
        }
      } catch (err) {
        this.login.error = err.response?.data?.message || "Login gagal. Silakan coba lagi.";
        this.login.loading = false;
      }
    },

    async handleRegister() {
      if (!this.register.username || !this.register.email || !this.register.password || !this.register.role) {
        this.register.error = "Semua kolom wajib diisi.";
        toast.error(this.register.error);
        return;
      }

      this.register.error = null;
      this.register.success = false;
      this.register.loading = true;

      try {
        const response = await api.post("/register", {
          username: this.register.username,
          email: this.register.email,
          password: this.register.password,
          role: this.register.role,
        });

        toast.success(response.data.message || "Registrasi berhasil!");
        this.register.success = true;

        this.register.username = "";
        this.register.email = "";
        this.register.password = "";
        this.register.role = "";

        this.$router.push('/admin');
      } catch (err) {
        this.register.error = err.response?.data?.message || "Registrasi gagal. Silakan coba lagi.";
        toast.error(this.register.error);
      } finally {
        this.register.loading = false;
      }
    },
  },
  mounted() {
    if (!this.$route.query['auth-type']) {
      this.goAuthType('sign-in');
    }
    this.fetchCurrentUser();
  }
};
</script>

<style scoped>

@font-face {
  font-family: 'Pixel Operator';
  src: url('@/fonts/PixelOperator.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
}
@font-face {
  font-family: 'Press Start 2P';
  src: url('@/fonts/PressStart2P-Regular.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
}
@font-face {
  font-family: 'Minecraft Standard';
  src: url('@/fonts/MinecraftStandard.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
}
@font-face {
  font-family: 'Minecraft';
  src: url('@/fonts/minecraft.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
}


@keyframes floatUp {
  from { transform: translateY(30px); opacity: 0;}
  to { transform: translateY(0); opacity: 1;}
}

.auth-body {
  background-image: url("/bg-login.png");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  min-height: 100dvh;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}

.auth-main { display: flex; justify-content: center; align-items: center; z-index: 1; }

/* Modern switch transition (slide + scale + shadow bloom) */
.auth-switch-enter-active,
.auth-switch-leave-active {
  transition: opacity .28s ease, transform .36s cubic-bezier(.64,.14,.48,.92), box-shadow .36s ease;
}
.auth-switch-enter-from,
.auth-switch-leave-to {
  opacity: 0;
  transform: translateY(14px) scale(.80);
  box-shadow: 0 0 0 rgba(0,0,0,0);
}
.auth-switch-enter-to,
.auth-switch-leave-from {
  box-shadow: 0 8px 28px rgba(0,0,0,.18);
}

/* .auth-stack, .auth-card.is-active, .auth-card.is-inactive { } */
.form-control { all: unset; width: 100%; padding: 8px 12px; font-family: "Pixel Operator"; font-weight: 400; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }

.auth-card {
  display: flex;
  flex-direction: column;
  justify-content: center;
  border: 1px solid rgba(0,0,0,0.65);
  border-radius: 14px;
  width: min-content;
  min-width: 28dvw;
  max-width: 28dvw;
  overflow: hidden;
  gap: 1.2rem;
  padding: 2.4rem;
  background: linear-gradient(to top, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0.64));
  -webkit-backdrop-filter: blur(8px);
  backdrop-filter: blur(8px);
  position: relative;
  animation: floatUp .5s cubic-bezier(.90,.03,.69,.82);
}

.card-title h2 { font-family: "Press Start 2P"; font-size: 34px; color: #15a1cb; -webkit-text-stroke: .8px rgba(0, 0, 0, 0.634); transition: color .5s cubic-bezier(.64,.14,.48,.92); }
.card-title p { font-family: "Minecraft Standard"; font-size: 10px; color: black; margin-bottom: 2rem;}

/* Accent gradient strip */
.auth-card::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(1200px 200px at -20% 110%, rgba(21,161,203,0.18), transparent 60%),
              radial-gradient(1000px 180px at 120% -10%, rgba(21,161,203,0.18), transparent 60%);
  z-index: 0;
}
.auth-card > * { position: relative; z-index: 1; }

select { width: 100%; }

/* Shared form styles */
.field-label { font-family: "Minecraft", sans-serif; font-size: 16px; margin-top: 10px; }
.input-group {
  position: relative;
  display: flex;
  align-items: center;
  -webkit-backdrop-filter: blur(6px);
  backdrop-filter: blur(6px);
  margin: .4rem 0;
  border: 1.4px solid rgba(0,0,0,0.8);

  input {    
    background: rgba(255, 255, 255, 0.140);
    box-sizing: border-box;
    font-family: "Pixel Operator", sans-serif;
    padding: .6rem .8rem;
    transition: all .1s ease-in-out;
    opacity: 0.7;

    &:active, &:focus {
      background: rgba(255, 255, 255, 0.440);
      opacity: 1;
    }
  }
  select {    
    background: rgba(255, 255, 255, 0.140);
    box-sizing: border-box;
    font-family: "Pixel Operator", sans-serif;
    padding: .6rem .8rem;
    transition: all .1s ease-in-out;
    opacity: 0.7;

    option {
      background: rgba(255, 255, 255, 0.140);
      box-sizing: border-box;
      font-family: "Pixel Operator", sans-serif;
      padding: .6rem .8rem;
    }

    &:active, &:focus {
      background: rgba(255, 255, 255, 0.440);
      opacity: 1;
    }
  }
}

.checkbox { display: flex; align-items: center; gap: 6px; cursor: pointer; }
.checkbox .checkbox-label { cursor: pointer; user-select: none; transform: translateY(-1px); font-family: "Minecraft Standard"; font-size: 10px; color: black; }

.actions { display: flex; gap: 12px; margin-top: 2rem; width: 100%; flex-direction: row; }
.actions :deep(.app-btn) { width: 100%; height: 2.8rem; min-width: unset; padding: 0 20px; }

.card-footer { margin-top: .6rem; font-family: "Minecraft Standard"; font-size: 10px; }
.card-footer a { color: #15a1cb; text-decoration: underline; }

/* Transition styles */
.auth-card-enter-active,
.auth-card-leave-active {
  transition: opacity 0.3s ease, transform 0.4s cubic-bezier(.64,.14,.48,.92);
}

.auth-card-enter-from,
.auth-card-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Horizontal slide with scale-down -> move -> scale-up */
.auth-inner-enter-active {
  animation: slideScaleIn .42s cubic-bezier(.64,.14,.48,.92) both;
}
.auth-inner-leave-active {
  animation: slideScaleOut .42s cubic-bezier(.64,.14,.48,.92) both;
}
.animating {
  user-select: none;
  pointer-events: none;
  cursor: not-allowed;
}

@keyframes slideScaleIn {
  0% {
    opacity: 0;
    transform: translateX(28px) scale(0.94);
    filter: blur(8px);
  }
  40% {
    opacity: 0.6;
    transform: translateX(10px) scale(0.97);
    filter: blur(4px);
  }
  100% {
    opacity: 1;
    transform: translateX(0) scale(1);
    filter: blur(0);
  }
}

@keyframes slideScaleOut {
  0% {
    opacity: 1;
    transform: translateX(0) scale(1);
    filter: blur(0);
  }
  60% {
    opacity: 0.5;
    transform: translateX(-10px) scale(0.97);
    filter: blur(4px);
  }
  100% {
    opacity: 0;
    transform: translateX(-28px) scale(0.94);
    filter: blur(8px);
  }
}
</style>