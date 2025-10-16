<template>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
            <h3 class="text-center mb-4">Register</h3>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="success" class="alert alert-success">{{ success }}</div>

            <form @submit.prevent="handleRegister">
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
                    <label class="form-label">Email</label>
                    <input
                        v-model="email"
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Masukkan email"
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

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select v-model="role" class="form-select" required>
                        <option disabled value="">Pilih role</option>
                        <option value="admin">Admin</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>

            <!-- <div class="text-center mt-3">
                <small>Sudah punya akun? <router-link to="/login">Login di sini</router-link></small>
            </div> -->
        </div>
    </div>
</template>

<script>
import api from "@/services/api";
import toast from "@/services/toast"; // tambahkan ini

export default {
    name: "RegisterView",
    data() {
        return {
            username: "",
            email: "",
            password: "",
            role: "",
            error: null,
            success: null,
        };
    },
    methods: {
        async handleRegister() {
            if (!this.username || !this.email || !this.password || !this.role) {
                this.error = "Semua kolom wajib diisi.";
                toast.error(this.error); // tampilkan toast error
                return;
            }
            this.error = null;
            this.success = null;
            try {
                const response = await api.post("/register", {
                    username: this.username,
                    email: this.email,
                    password: this.password,
                    role: this.role,
                });
                this.success = response.data.message || "Registrasi berhasil!";
                toast.success(this.success); // tampilkan toast sukses
                // reset form
                this.username = "";
                this.email = "";
                this.password = "";
                this.role = "";
                // ke view login
                this.$router.back();
            } catch (err) {
                this.error = err.response?.data?.message || "Registrasi gagal. Silakan coba lagi.";
                toast.error(this.error); // tampilkan toast error
            }
        },
    },
};
</script>

<style scoped>
@import "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
</style>
