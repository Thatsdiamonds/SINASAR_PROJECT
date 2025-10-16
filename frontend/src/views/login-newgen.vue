<template>
<div class="login-body">
    <main class="login-main">
        <div class="login-card">
            <div class="card-title">
                <h2>Login</h2>
            </div>
            <div class="login-form">
                <form @submit.prevent="handleLogin">
                        <label for="username">Username</label>
                        <input
                        v-model="username"
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username/email"
                        required
                    />
                    <br>

                        <label for="password">Password</label>
                        <input
                        v-model="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    />
                    <br>
                    <div class="login-button">
                        <input type="submit" value="Login">
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <p>Belum punya akun?</p>
                <a href="">Daftar</a>
            </div>
        </div>
    </main>
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
@font-face {
  font-family: 'Pixel Operator 8';
  src: url('@/fonts/PIXELOPERATOR8.ttf') format('truetype');
}

@font-face {
    font-family: "Minecraft";
    src: url("@/fonts/MINECRAFTSTANDARD.OTF") format("opentype");
}

@font-face {
    font-family: "Minecraft Medium";
    src: url("@/fonts/MINECRAFT.TTF") format("opentype");
}

@font-face {
  font-family: 'Title';
  src: url('@/fonts/PressStart2P-Regular.ttf') format('truetype');
}

@font-face {
    font-family: "Minecraft Bold";
    src: url("@/fonts/MINECRAFTSTANDARDBOLD.OTF") format("opentype");
}

@font-face {
    font-family: "Minecraft Bold Oblique";
    src: url("@/fonts/MINECRAFTSTANDARDBOLDOBLIQUE.OTF") format("opentype");
}

@font-face {
    font-family: "Minecraft Oblique";
    src: url("@/fonts/MINECRAFTSTANDARDOBLIQUE.OTF") format("opentype");
}


@font-face {
  font-family: 'Pixel Operator';
  src: url('@/fonts/PixelOperator.ttf') format('truetype');
}

*{
    margin: 0;
    padding: 0;
    color: black;
}

.form-control {
    all: unset;
    border: 1.4px solid black;
    font-family: "Pixel Operator";
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

.login-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
    border: 1px solid black;
    border-radius: 10px;
    width: min-content;
    gap: 2rem;
    padding: 2.8rem;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.5));
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}

.login-card h2 {
    font-family: "Title";
    font-size: 34px;
    color: #15a1cb;
    -webkit-text-stroke: .8px rgba(0, 0, 0, 0.634);
}

select {
    width: 100%;
}

.login-form form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: stretch;
    gap: 5px;
    font-family: "Minecraft Medium", sans-serif;
}

form div p {
    font-size: 24px;
}

.login-form input {
    background-color: #15a0cb00;
    border-radius: 5px;
    box-sizing: border-box;
    padding: 5px;
    font-size: 20px;
}

.login-button {
    margin-top: 1rem;
    width: 100%;
}

.login-button input {
    width: 100%;
    padding: 10px;
    background-color: #15a1cb;
    border-radius: 5px;
    box-sizing: border-box;
    color: white;
    font-family: "Minecraft Medium", sans-serif;
    font-size: 16px;
}

.card-footer {
    display: flex;
    justify-content: center;
    align-items: center;
    width: max-content;
    gap: 20px;
    font-family: "Minecraft Medium", sans-serif;
}

.card-footer p {
    font-family: "Pixel Operator 8";
    font-size: 16px;
}

.card-footer a {
    font-size: 20px;
}
</style>