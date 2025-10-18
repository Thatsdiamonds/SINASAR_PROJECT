<template>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
            <h3 class="text-center mb-4">Login</h3>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <form @submit.prevent="handleLogin">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input
                        v-model="username"
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        required
                    />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input
                        v-model="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    />
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <small>Belum punya akun? <a href="/register">Daftar di sini</a></small>
            </div>
            <div class="text-center mt-3">
                <a href="#" @click.prevent="fillAdmin" class="me-2" onclick="return false">Isi Admin</a>
                <a href="#" @click.prevent="fillSeller" onclick="return false">Isi Seller</a>
            </div>
        </div>
    </div>
</template>

<script>
import api from "@/services/api";
import toast from "@/services/toast";

export default {
    name: "LoginView",
    data() {
        return {
            username: "",
            password: "",
            error: null,
        };
    },
    methods: {
        async handleLogin() {
            if (this.username === "" || this.password === "") {
                this.error = "Username dan password harus diisi.";
                toast.error(this.error);
                return;
            }

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
                    this.$router.push("/admin");
                } else if (response.data.role === "seller") {
                    this.$router.push("/detail-kios?lokasi=" + response.data.lokasi);
                }

            } catch (err) {
                this.error = err.response?.data?.message || "Login gagal. Silakan coba lagi.";
                toast.error(this.error);
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
@import "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
</style>
