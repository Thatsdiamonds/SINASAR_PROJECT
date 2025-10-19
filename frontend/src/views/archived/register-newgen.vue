<template>
<div class="register-body">
    <main class="register-main">
        <div class="register-card">
            <div class="card-title">
                <h2>Register</h2>
                <p>Buat akun baru untuk mengakses semua fitur.</p>
            </div>
            <div class="register-form">
                <form @submit.prevent="handleRegister">
                    <!-- Username Field -->
                    <label for="username" class="field-label">Username</label>
                    <div class="input-group">
                        <input
                            v-model="username"
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            required
                        />
                    </div>

                    <!-- Email Field -->
                    <label for="email" class="field-label">Email</label>
                    <div class="input-group">
                        <input
                            v-model="email"
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Masukkan email"
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

                    <!-- Role Field -->
                    <div v-if="currentUser?.role === 'admin'">
                    <label for="role" class="field-label">Role</label>
                    <div class="input-group">
                        <select v-model="role" name="role" class="form-control" required>
                            <option disabled value="">Pilih role</option>
                            <option value="admin">Admin</option>
                            <option value="seller">Seller</option>
                        </select>
                    </div>
                    </div>
                    <!-- Actions -->
                    <div class="actions">
                        <AppButton
                            label="Daftar"
                            :loading="loading"
                            :success="success"
                            style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #333; --bg-hov: #cfecff"
                            type="buttonSubmit"
                        />
                        <AppButton
                            label="Login"
                            type="secondary"
                            @click="gologin"
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
    name: "registerView",
    components: {
        Spinner,
        AppButton,
    },
    data() {
        return {
            username: "",
            email: "",
            password: "",
            role: "seller",
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
        gologin() {
            this.$router.push("/login");
        },
        async handleRegister() {
            // Validasi kolom wajib isi
            if (!this.username || !this.email || !this.password || !this.role) {
                this.error = "Semua kolom wajib diisi.";
                toast.error(this.error);
                return;
            }

            this.error = null;
            this.success = false;
            this.loading = true;

            try {
                const response = await api.post("/register", {
                    username: this.username,
                    email: this.email,
                    password: this.password,
                    role: this.role,
                });

                // Tampilkan sukses
                toast.success(response.data.message || "Registrasi berhasil!");
                this.success = true;

                // Reset form
                this.username = "";
                this.email = "";
                this.password = "";
                this.role = "";

                // Arahkan ke halaman admin (mengikuti sistem admin/register.vue)
                this.$router.push('/admin/dashboard');
            } catch (err) {
                this.error = err.response?.data?.message || "Registrasi gagal. Silakan coba lagi.";
                toast.error(this.error);
            } finally {
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

.register-body {
    background-image: url("/bg-login.png");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    min-height: 100dvh;
    display: flex;
    justify-content: center;
    align-items: center;

    &::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(131, 255, 129, 0.119);
    }
}

.register-main {
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1;
}

.form-control {
    all: unset;
    width: 100%;
    padding: 8px 12px;
    font-family: "Pixel Operator";
}

.register-card {
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

.register-card h2 {
    font-family: "Title";
    font-size: 34px;
    color: #15a1cb;
    -webkit-text-stroke: .8px rgba(0, 0, 0, 0.634);
}
.register-card p {
    font-family: "Minecraft";
    font-size: 10px;
    color: black;
}

/* Accent gradient strip */
.register-card::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(1200px 200px at -20% 110%, rgba(21,161,203,0.18), transparent 60%),
                radial-gradient(1000px 180px at 120% -10%, rgba(21,161,203,0.18), transparent 60%);
}

select { width: 100%; }

.register-form form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: stretch;
    gap: 5px;
    font-family: "Minecraft Medium", sans-serif;
}

form div p { font-size: 24px; }

.register-form input {
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