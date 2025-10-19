<template>
    <div class="update-container">
        <div class="container-top">
            <div class="left">
                <div>
                    <h1>Edit Kios</h1>
                    <h2>Halaman untuk mengedit detail informasi kios yang terpilih</h2>
                </div>
            </div>
            <div class="container-form">
                <div class="form-profile">
                    <form @submit.prevent="updateKios">
                        <div class="top-form">
                            <div class="profile">
                                <div class="img-profile">
                                    <img 
                            :src="getImageUrl(profileImagePreview) || '../../img/profile.png'" 
                            alt="fotoprofile"
                            @error="handleImageError"
                                class="profile-img"
                                >
                        </div>
                        <div class="info-profile text">
                            <button type="button" class="button" 
                                    style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #e57373; --bg-hov: #f8d7da"
                                    @click="triggerProfilePhoto">
                                    <img src="/icons/change_file.svg" alt="Icon" width="auto" height="100%">
                                    <div class="text">
                                        
                                        Ganti Foto Profil
                                    </div>
                                </button>
                        </div>
                        <input 
                        type="file" 
                        ref="profilePhotoInput"
                        id="fotoprofile" 
                        name="fotoprofile" 
                        accept="image/*"
                        @change="handleProfileImageChange"
                        style="display: none;"
                        >
                    </div>
                    <button type="submit" :disabled="loading" class="button" 
                    style="--border-color: #16abd5; --bg-color: #19a7a7; --color: #fff; --border-hov: #16abd5; --bg-hov: #16abd5">
                    <img src="/icons/save_pixel.svg" alt="Icon" width="auto" height="100%">
                    <div class="text">
                        
                        <span v-if="loading">Menyimpan...</span>
                        <span v-else>Simpan</span>
                    </div>
                </button>
            </div>
            <div class="form-grid">
                            <div>
                                <label for="nama">Nama Kios <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="nama" 
                                    name="nama" 
                                    v-model="shopProfile.nama"
                                    :class="{ 'error': errors.nama }"
                                    required
                                >
                                <span v-if="errors.nama" class="error-message">{{ errors.nama }}</span>
                            </div>
                            <div>
                                <label for="deskripsi">Deskripsi <span class="required">*</span></label>
                                <textarea 
                                    id="deskripsi" 
                                    name="deskripsi" 
                                    v-model="shopProfile.deskripsi"
                                    :class="{ 'error': errors.deskripsi }"
                                    rows="3"
                                    required
                                ></textarea>
                                <span v-if="errors.deskripsi" class="error-message">{{ errors.deskripsi }}</span>
                            </div>
                            <div>
                                <label for="patokan">Patokan</label>
                                <input 
                                    type="text" 
                                    id="patokan" 
                                    name="patokan" 
                                    v-model="shopProfile.patokan"
                                >
                            </div>
                            <div>
                                <label for="produk">Produk <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="produk" 
                                    name="produk" 
                                    v-model="shopProfile.produk"
                                    :class="{ 'error': errors.produk }"
                                    required
                                >
                                <span v-if="errors.produk" class="error-message">{{ errors.produk }}</span>
                            </div>
                            <div>  
                                <label for="kontak">Kontak <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="kontak" 
                                    name="kontak" 
                                    v-model="shopProfile.kontak"
                                    :class="{ 'error': errors.kontak }"
                                    required
                                >
                                <span v-if="errors.kontak" class="error-message">{{ errors.kontak }}</span>
                            </div>
                            <div>  
                                <label for="lokasi">Lokasi <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="lokasi" 
                                    name="lokasi" 
                                    v-model="shopProfile.lokasi"
                                    :class="{ 'error': errors.lokasi }"
                                    required
                                >
                                <span v-if="errors.lokasi" class="error-message">{{ errors.lokasi }}</span>
                            </div>
                            <div class="full-width">
                                <label for="kiosimage">Foto Kios</label>
                                <div class="kios-photo-simple">
                                    <div v-if="kiosImagePreview" class="kios-preview-simple">
                                        <img 
                                            :src="getImageUrl(kiosImagePreview)" 
                                            alt="Kios Preview" 
                                            class="kios-img-simple"
                                            @error="handlePreviewError"
                                        >
                                    </div>
                                    <div v-else class="kios-placeholder-simple">
                                        <i class="placeholder-icon">🏪</i>
                                        <p>Belum ada foto kios</p>
                                    </div>
                                    <input 
                                    type="file" 
                                    ref="kiosPhotoInput"
                                    id="kiosimage" 
                                    name="kiosimage" 
                                    accept="image/*"
                                    @change="handleKiosImageChange"
                                    style="display: none;"
                                    >
                                    <button type="button" class="button" 
                                        style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #e57373; --bg-hov: #f8d7da"
                                        @click="triggerKiosPhoto">
                                        <img src="/icons/change_file.svg" alt="Icon" width="auto" height="100%">
                                        <div class="text">
                                            {{ kiosImagePreview ? 'Ganti Foto Kios' : 'Tambah Foto Kios' }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Success Message -->
                    <div v-if="message" class="success-message">
                        <i class="success-icon">✓</i>
                        {{ message }}
                    </div>
                    
                    <!-- Error Message -->
                    <div v-if="error" class="error-message-global">
                        <i class="error-icon">⚠</i>
                        {{ error }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { toPublicUrl } from "@/services/image";
import { getSellerProfile, updateMyKios } from "@/services/api";
import toast from "@/services/toast";

const shopProfile = ref({
  nama: "",
  deskripsi: "",
  produk: "",
  lokasi: "",
  patokan: "",
  kontak: "",
  foto_profil: null,
  foto_kios: null,
});

const message = ref("");
const loading = ref(false);
const error = ref("");
const errors = ref({});
const profileImagePreview = ref("");
const kiosImagePreview = ref("");
const profilePhotoInput = ref(null);
const kiosPhotoInput = ref(null);

const isFormValid = computed(() => {
  return shopProfile.value.nama && 
         shopProfile.value.deskripsi && 
         shopProfile.value.produk && 
         shopProfile.value.kontak && 
         shopProfile.value.lokasi;
});

const getImageUrl = (imagePath) => {
  if (!imagePath) return null;
  
  if (imagePath.startsWith('http') || imagePath.startsWith('data:')) {
    return imagePath;
  }
  
  if (imagePath instanceof File) {
    return URL.createObjectURL(imagePath);
  }
  
  if (imagePath.startsWith('/')) {
    return toPublicUrl(imagePath);
  }
  
  return toPublicUrl(imagePath);
};

const checkImageExists = (url) => {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = url;
  });
};

const handleImageError = (event) => {
  console.log("Image failed to load:", event.target.src);
  event.target.src = '../../img/profile.png';
};

const handlePreviewError = (event) => {
  console.log("Preview image failed to load:", event.target.src);
  event.target.style.display = 'none';
};

const triggerProfilePhoto = () => {
  profilePhotoInput.value?.click();
};

const triggerKiosPhoto = () => {
  kiosPhotoInput.value?.click();
};

onMounted(async () => {
  loading.value = true;
  try {
    const shop = await getSellerProfile();
    // gabungkan biar field foto tetap ada
    shopProfile.value = {
      ...shopProfile.value,
      ...shop,
    };
    
    // Set preview images jika ada
    if (shop.foto_profil) {
      profileImagePreview.value = shop.foto_profil;
    }
    if (shop.foto_kios) {
      kiosImagePreview.value = shop.foto_kios;
    }
    
    console.log("Profile image:", shop.foto_profil);
    console.log("Kios image:", shop.foto_kios);
    console.log("Profile image URL:", getImageUrl(shop.foto_profil));
    console.log("Kios image URL:", getImageUrl(shop.foto_kios));
    
    console.log("Shop profile loaded:", shop);
  } catch (err) {
    error.value = "Gagal memuat data kios";
    toast.error("Gagal memuat data kios");
    console.error("Error loading shop profile:", err);
  } finally {
    loading.value = false;
    JsLoadingOverlay.hide();
  }
});

// Handle profile image change (jika ada)
const handleProfileImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    shopProfile.value.foto_profil = file;
    
    // Create preview using FileReader (jika ada)
    const reader = new FileReader();
    reader.onload = (e) => {
      profileImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    
    console.log("Profile image changed:", file.name);
  } else {
    // Reset preview jika tidak ada file selected
    profileImagePreview.value = shopProfile.value.foto_profil || "";
  }
};

// Handle kios image change
const handleKiosImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    shopProfile.value.foto_kios = file;
    
    // Create preview using FileReader
    const reader = new FileReader();
    reader.onload = (e) => {
      kiosImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    
    console.log("Kios image changed:", file.name);
  } else {
    // Reset preview if no file selected
    kiosImagePreview.value = shopProfile.value.foto_kios || "";
  }
};

const validateForm = () => {
  errors.value = {};
  
  if (!shopProfile.value.nama?.trim()) {
    errors.value.nama = "Nama kios harus diisi";
  }
  
  if (!shopProfile.value.deskripsi?.trim()) {
    errors.value.deskripsi = "Deskripsi harus diisi";
  }
  
  if (!shopProfile.value.produk?.trim()) {
    errors.value.produk = "Produk harus diisi";
  }
  
  if (!shopProfile.value.kontak?.trim()) {
    errors.value.kontak = "Kontak harus diisi";
  }
  
  if (!shopProfile.value.lokasi?.trim()) {
    errors.value.lokasi = "Lokasi harus diisi";
  }
  
  return Object.keys(errors.value).length === 0;
};

const updateKios = async () => {
  // Clear previous messages
  message.value = "";
  error.value = "";
  
  // Validate form
  if (!validateForm()) {
    error.value = "Mohon lengkapi semua field yang wajib diisi";
    return;
  }

  loading.value = true;
  
  try {
    const formData = new FormData();
    formData.append("nama", shopProfile.value.nama || "");
    formData.append("deskripsi", shopProfile.value.deskripsi || "");
    formData.append("produk", shopProfile.value.produk || "");
    formData.append("lokasi", shopProfile.value.lokasi || "");
    formData.append("patokan", shopProfile.value.patokan || "");
    formData.append("kontak", shopProfile.value.kontak || "");

    if (shopProfile.value.foto_profil instanceof File) {
      formData.append("foto_profil", shopProfile.value.foto_profil);
    }
    if (shopProfile.value.foto_kios instanceof File) {
      formData.append("foto_kios", shopProfile.value.foto_kios);
    }

    const res = await updateMyKios(formData);
    toast.success("Kios berhasil diupdate!");
    
    
    // Clear form after success
    setTimeout(() => {
      message.value = "";
    }, 3000);
    
  } catch (err) {
    console.error("Gagal update kios:", err.response?.data || err.message);
    error.value = err.response?.data?.message || "Terjadi kesalahan saat mengupdate kios";
    toast.error("Terjadi kesalahan saat mengupdate kios");
  } finally {
    loading.value = false;
  }
};
</script>

<script>
import 'js-loading-overlay'
export default {
  mounted() {
    // Auto scroll & highlight jika hash #konfig-kios
    if (typeof window !== 'undefined' && window.location.hash === '#konfigurasi-kios') {
      const el = document.getElementById('konfigurasi-kios');
      if (el) {
        // Smooth scroll ke panel
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Tambahkan kelas highlight sementara
        el.classList.add('hash-highlight');
        setTimeout(() => el.classList.remove('hash-highlight'), 1500);
      }
    }
    // Munculin overlay pas web mulai load
    JsLoadingOverlay.show({
      "overlayBackgroundColor": "#000000",
      "overlayOpacity": 0.6,
      "spinnerIcon": "ball-8bits",
      "spinnerColor": "#188AEB",
      "spinnerSize": "2x",
      "overlayIDName": "overlay",
      "spinnerIDName": "spinner",
      "offsetX": 0,
      "offsetY": 0,
      "lockScroll": true,
      "overlayZIndex": 9998,
      "spinnerZIndex": 9999
    });
  }
}
</script>

<style scoped>
@import '@/styles/button.css';

.update-container{
    padding: 10px;
    margin: 2rem;
}

.info-container{
    padding: 10px;
    margin: 2rem;
}

.preview-img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border: 1px solid #ccc;
    margin-top: 5px;
}

.container-top{
    display: flex;
    justify-content: center;
    align-items: stretch;
}

.left {
    flex: 0 0 auto;
    background: #fff;
    padding: 1rem;
}

.left div {
    display: flex;
    gap: .8rem;
    width: 100%;
    height: fit-content;
}
.left div h1 {
     all: unset;
    font-family: 'Press Start 2P', sans-serif;
    font-style: normal;
    width: fit-content;
    font-size: 2.4rem;
    display: block;
    color: #15A1C8;
    text-shadow: 4px 4px 0px #1E3445;
    writing-mode: vertical-rl;
    text-orientation: upright;
}
.left div h2 {
    all: unset;
    font-family: 'Pixel Operator', sans-serif;
    font-style: normal;
    font-size: 1.8rem;
    width: 12dvw;
}

.kios-image{
    flex: 1;
    width: auto;
    height: 450px;
    border: 1px solid black;
}

.container-bottom {
    display: flex;
    justify-content: center;
    font-family: "MyCustomFont", sans-serif;
    font-size: x-small;
}

.info-container .container-top {
    display: flex;
    margin-bottom: 2rem;
}

.container-form {
    border: 2px solid #b0bec5;
    padding: 20px;
    width: 100%;
}

.desc-page p {
    font-family: 'Pixel Operator', sans-serif;
    font-weight: bold;
}

.top-form {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile {
    display: flex;
    align-items: center;
    gap: 15px;
}

.img-profile {
    width: 80px;
    height: 80px;
    overflow: hidden;
    border: 2px solid #ddd;
}

.simple-replace-btn {
    background: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-bottom: 8px;
    display: block;
}

.simple-replace-btn:hover {
    background: #2980b9;
}

.info-profile p {
    margin: 0;
}

img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background-color: #f0f0f0;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 20px;
}

.form-grid label {
    font-family: 'Pixel Operator', sans-serif;
    font-size: 1rem;
  color: #222;
}

.required {
    color: #e74c3c;
    font-weight: bold;
}

.form-grid input,
.form-grid textarea {
    width: 100%;
    font-size: 14px;
    box-sizing: border-box;
    font-family: 'Pixel Operator', sans-serif;
    font-size: 1rem;
    border: 2px solid #b0bec5;
    padding: 0.5rem 0.7rem;
    background: #fff;
    resize: none;
    outline: none;
    transition: border 0.15s;
}

.form-grid input:focus,
.form-grid textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.form-grid input.error,
.form-grid textarea.error {
    border-color: #e74c3c;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
}

.error-message {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

/* Kios Photo Simple */
.kios-photo-simple {
    margin-top: 10px;
}

.kios-preview-simple {
    margin-bottom: 10px;
}

.kios-img-simple {
    width: 200px;
    height: 150px;
    object-fit: cover;
    border: 2px solid #ddd;
    display: block;
}

.kios-placeholder-simple {
    width: 200px;
    height: 150px;
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f9f9f9;
    margin-bottom: 10px;
    color: #666;
}

.placeholder-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.kios-placeholder-simple p {
    margin: 0;
    font-size: 14px;
}

.simple-kios-btn {
    background: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
    display: block;
}

.simple-kios-btn:hover {
    background: #2980b9;
}

.button-simpan {
    margin-top: 20px;
    text-align: center;
}

.button-simpan button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
    min-width: 120px;
}

.button-simpan button:hover:not(:disabled) {
    background-color: #2980b9;
}

.button-simpan button:disabled {
    background-color: #bdc3c7;
    cursor: not-allowed;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 12px;
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.success-icon {
    color: #28a745;
    font-weight: bold;
}

.error-message-global {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 12px;
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error-icon {
    color: #dc3545;
    font-weight: bold;
}

.full-width {
    grid-column: 1 / -1;
}

@media (max-width: 600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .container-top {
        flex-direction: column;
        align-items: center;
    }
    
    .title-page {
        writing-mode: horizontal-tb;
        text-orientation: initial;
        margin-bottom: 20px;
    }
    
    .desc-page {
        margin-right: 0;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .profile {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .simple-replace-btn {
        margin: 0 auto 10px auto;
    }
    
    .kios-img-simple {
        width: 100%;
        max-width: 300px;
        height: 200px;
    }
    
    .kios-placeholder-simple {
        width: 100%;
        max-width: 300px;
        height: 200px;
    }
}
</style>