<template>
<div class="login-body">
    <main class="login-main">
        <div class="login-card">
            <div class="card-title">
                <h2>Login</h2>
                <p>Masukkan username dan password untuk masuk.</p>
            </div>
            <div class="login-form">
                <form @submit.prevent="handleLogin">
                    <!-- Username Field -->
                    <label for="username" class="field-label">Username</label>
                    <div class="input-group">
                        <input
                            v-model="username"
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username/email"
                            required
                        />
                    </div>

                    <!-- Password Field -->
                    <label for="password" class="field-label">Password</label>
                    <div class="input-group">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                        />
                    </div>

                        <label class="checkbox">
                            <input type="checkbox" id="show-pass" v-model="showPassword" />
                            <label for="show-pass" class="checkbox-label">Tampilkan password</label>
                        </label>

                    <!-- Actions -->
                    <div class="actions">
                        <AppButton
                            label="Login"
                            :loading="loading"
                            :success="success"
                            style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #333; --bg-hov: #cfecff"
                            type="buttonSubmit"
                        />
                        <AppButton
                            label="Daftar"
                            type="secondary"
                            @click="goRegister"
                            style="width: fit-content;"
                        />
                    </div>
                </form>
            </div>
            <!-- <div class="card-footer">
                <p>Belum punya akun?</p>
                <a href="#" @click.prevent="goRegister">Daftar</a>
            </div> -->
        </div>
    </main>
</div>

</template>

<script>
import api from "@/services/api";
import toast from "@/services/toast";
import Spinner from "@/components/Spinner.vue";
import AppButton from "@/components/AppButton.vue";

export default {
    name: "LoginView",
    components: {
        Spinner,
        AppButton,
    },
    data() {
        return {
            username: "",
            password: "",
            loading: false,
            success: false,
            error: null,
            remember: false,
            showPassword: false,
        };
    },
    methods: {
        togglePassword() {
            this.showPassword = !this.showPassword;
        },
        goRegister() {
            this.$router.push("/register");
        },
        async handleLogin() {
            if (this.username === "" || this.password === "") {
                this.error = "Username dan password harus diisi.";
                toast.error(this.error);
                this.loading = false;
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                if (localStorage.getItem("token")) {
                    toast.info("Sesi lama diakhiri, login ulang...");

                    try {
                        await api.post("/logout", {}, {
                            headers: {
                                Authorization: `Bearer ${localStorage.getItem("token")}`
                            }
                        });
                    } catch (logoutErr) {
                        console.warn("Gagal revoke token lama:", logoutErr);
                        this.loading = false;
                    }

                    localStorage.removeItem("token");
                }

                const response = await api.post("/login", {
                    login: this.username,
                    password: this.password,
                });

                localStorage.setItem("token", response.data.token);

                toast.success("Login berhasil!");
                if (response.data.role === "admin") {
                    this.$router.push("/admin/dashboard");
                } else if (response.data.role === "seller") {
                    this.$router.push("/seller/dashboard/detail-kios?lokasi=" + response.data.lokasi);
                }

            } catch (err) {
                this.error = err.response?.data?.message || "Login gagal. Silakan coba lagi.";
                this.loading = false;
            }
        },

        fillAdmin() {
            this.username = "a@a.a";
            this.password = "123456";
        },
        fillSeller() {
            this.username = "mrwhite@s.s";
            this.password = "12345678";
        },
    },
};
</script>

<style scoped>
@keyframes floatUp {
  from { transform: translateY(12px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.login-body {
    background-image: url("/bg-login.png");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    min-height: 100dvh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-main {
    display: flex;
    justify-content: center;
    align-items: center;
}

.form-control {
    all: unset;
    width: 100%;
    padding: 8px 12px;
    font-family: "Pixel Operator";
}

.login-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
    border: 1px solid rgba(0,0,0,0.65);
    border-radius: 14px;
    width: min-content;
    min-width: 26dvw;
    gap: 1.6rem;
    padding: 2.4rem;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.62));
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.18);
    position: relative;
    overflow: hidden;
    animation: floatUp 420ms ease-out;
}

.login-card h2 {
    font-family: "Title";
    font-size: 34px;
    color: #024196;
    -webkit-text-stroke: .8px rgba(0, 0, 0, 0.634);
}
.login-card p {
    font-family: "Minecraft";
    font-size: 10px;
    color: black;
}

/* Accent gradient strip */
.login-card::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(1200px 200px at -20% 110%, rgba(21,161,203,0.18), transparent 60%),
                radial-gradient(1000px 180px at 120% -10%, rgba(21,161,203,0.18), transparent 60%);
}

select { width: 100%; }

.login-form form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: stretch;
    gap: 5px;
    font-family: "Minecraft Medium", sans-serif;
}

form div p { font-size: 24px; }

.login-form input {
    background-color: #15a0cb00;
    border-radius: 5px;
    box-sizing: border-box;
    padding: 5px;
    font-size: 20px;
}

.subtitle {
    font-family: "Pixel Operator";
    font-size: 14px;
    color: #333;
    opacity: 0.8;
    margin-top: 4px;
}

.field-label {
    font-family: "Minecraft Medium", sans-serif;
    font-size: 16px;
    margin-top: 10px;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
    border: 1.4px solid rgba(0,0,0,0.8);
    border-radius: 8px;
    background: rgba(255,255,255,0.75);
    -webkit-backdrop-filter: blur(6px);
    backdrop-filter: blur(6px);
    padding: 2px 8px;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;

    .checkbox-label {
        cursor: pointer;
        user-select: none;
        transform:translateY(-1px);
        font-family: "Minecraft";
        font-size: 10px;
        color: black;
    }
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 10px;
    width: 100%;
    flex-direction: row;
    margin-top: 2rem;
}

.actions :deep(.app-btn) {
    width: 100%;
    height: 2.8rem;
    min-width: unset;
    padding: 0 20px;
}

.card-footer {
    margin-top: 6px;
}
</style>