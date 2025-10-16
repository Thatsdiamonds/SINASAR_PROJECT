<template>
    <div class="main-container">
  <!-- Tombol Aksi - Header -->
  <div class="upperButtonMenu marginbttm">
    <div class="button" @click="$router.go(-1);">
        <img src="/icons/drought.svg" alt="Icon" width="auto" height="100%">
      <div class="text">
        <a>kembali</a>
        <a>halaman sebelumnya</a>
      </div>
    </div>

    <div style="display: inline-flex; gap: 1rem;">
      <!-- Tampilkan Login jika belum login -->
      <div 
        v-if="!isAuthenticated"
        class="button"
        @click="$router.push('/login')"
        style="--border-color: #0D9C9C; --bg-color: #19A7A7; --bg-hov: #49CCCC; --border-hov: #49CCCC; color: #fff;">
        <img src="/icons/heart.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Login</a>
          <a style="color: #fff;">Masuk ke akun Anda</a>
        </div>
      </div>

      <!-- Seller: tombol Edit Kios jika pemilik kios -->
      <div 
        v-else-if="currentUser?.role === 'seller' && kiosData && Number(currentUser?.id) === Number(kiosData?.user_id)"
        class="button"
        style="--border-color: #0D9C9C"
        @click="$router.push('/my-kios/update')">
        <img src="/icons/sunglasses.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Edit Kios</a>
          <a>Klik di sini untuk edit kios Anda</a>
        </div>
      </div>

      <!-- Admin: tombol ke dashboard dengan query linked dan hash #konfigurasi-kios -->
      <div 
        v-else-if="currentUser?.role === 'admin' && kiosData?.lokasi"
        class="button"
        style="--border-color: #0D9C9C"
        @click="$router.push(`/admin?linked=${encodeURIComponent(kiosData.lokasi)}#konfigurasi-kios`)">
        <img src="/icons/rocket.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Buka Dashboard</a>
          <a>Kelola kios ini</a>
        </div>
      </div>
    </div>
  </div>

  <div class="upper-container">
    <div class="left">
        <div>
            <h1>Info Kios</h1>
            <h2>Halaman untuk melihat detail informasi kios yang terpilih</h2>
        </div>
    </div>
    <div class="right" :style="{ backgroundImage: (fotoKiosSrc ? `url('${fotoKiosSrc}')` : `url('/whitevan.jpeg')`) }">
        <div class="kios-overlay">
            <div class="overlay-row">
                <div class="fotoPengguna" v-if="kiosData && kiosData.lokasi" :style="{ backgroundImage: (fotoProfilSrc ? `url('${fotoProfilSrc}')` : `url('/icons/profile.svg')`)}"></div>
                <!-- read-only -->
                <div class="config-container">
                    <div class="content-header marginbttm">
                        <div class="content-header-text">
                            <div class="content-sub">
                                <span v-if="isLoading">Mengambil data...</span>
                                <h2 class="content-title" v-else-if="kiosData && kiosData.lokasi">Informasi kios {{ kiosData.lokasi }}</h2>
                                <span v-else>
                                  <template v-if="isSellerNoLinkedKios">
                                    Anda belum memiliki kios terkait. Hubungi pihak admin untuk membantu membuatkan.
                                  </template>
                                  <template v-else>
                                    Tidak ada data kios
                                  </template>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="kiosData" class="config-grid">
                        <div class="config-field" style="margin-right: 1rem;">
                            <label>Nama Kios</label>
                            <div class="config-value">
                              {{ kiosData.nama || '-' }}
                            </div>
                        </div>
                        <div class="config-field" v-if="kiosData && (kiosData.owner_name || kiosData.user?.name || kiosData.user?.username)">
                            <label>Pemilik Kios</label>
                            <div class="config-value">
                                {{ kiosData.owner_name || kiosData.user?.name || kiosData.user?.username }}
                                <a style="font-size: .8rem;" v-if="currentUser?.role === 'seller' && kiosData && Number(currentUser?.id) === Number(kiosData?.user_id)">( Anda )</a>
                            </div>
                        </div>
                        
                        <div class="config-field full">
                            <label>Produk Utama</label>
                            <div class="config-value">{{ kiosData.produk || '-' }}</div>
                        </div>
                        <div class="config-field full">
                            <label>Deskripsi</label>
                            <div class="config-value">{{ kiosData.deskripsi || '-' }}</div>
                        </div>
                        <div class="config-field full">
                            <label>Patokan Lokasi</label>
                            <div class="config-value">{{ kiosData.patokan || '-' }}</div>
                        </div>

                        <div class="config-field">
                            <label>Kontak</label>
                            <div class="config-value">{{ kiosData.kontak || '-' }}</div>
                        </div>

                        <!-- <div class="config-field full">
                            <label>Foto</label>
                            <div class="photo-row">
                                <div class="photo-box small">
                                    <img :src="fotoProfilSrc || '/icons/profile.svg'" alt="Foto Profil" />
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import api from '@/services/api';
  import { toPublicUrl } from '@/services/image';
  import { getUserProfile } from '@/services/api';

  const route = useRoute();
  const isLoading = ref(false);
  const kiosData = ref(null);
  const currentUser = ref(null);
  const isAuthenticated = computed(() => !!localStorage.getItem('token'));
  const isSellerNoLinkedKios = computed(() => {
    const qLok = route.params?.lokasi || route.query?.lokasi || '';
    const noLok = qLok === '' || qLok === 'null' || qLok === null || qLok === undefined;
    return isAuthenticated.value && currentUser.value?.role === 'seller' && noLok;
  });

  const fotoProfilSrc = computed(() => {
    const v = kiosData.value?.foto_profil;
    if (typeof v === 'string' && v.trim() !== '') return toPublicUrl(v);
    return null;
  });
  const fotoKiosSrc = computed(() => {
    const v = kiosData.value?.foto_kios;
    if (typeof v === 'string' && v.trim() !== '') return toPublicUrl(v);
    return null;
  });

  async function fetchKiosDetail() {
    try {
      isLoading.value = true;
      const lokasi = route.params?.lokasi || route.query?.lokasi || '';
      if (!lokasi) return;
      const res = await api.get(`/penjual/detail/${lokasi}`, { silent: true });
      kiosData.value = res?.data?.data || res?.data || null;
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchCurrentUser() {
    try {
      if (!isAuthenticated.value) {
        currentUser.value = null;
        return;
      }
      const res = await getUserProfile();
      currentUser.value = res;
    } catch (e) {
      currentUser.value = null;
    }
  }

  onMounted(async () => {
    await Promise.all([
      fetchKiosDetail(),
      fetchCurrentUser()
    ]);
  });
  </script>
  
  <style scoped>
  
  @import '@/styles/button.css';
  .main-container {
      padding: 2.4rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 96dvh;
    }
    
    :root {
        --container-border-color: #00000020;
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
    
    @font-face {
      font-family: 'Pixel Operator';
      src: url('@/fonts/PixelOperator.ttf') format('truetype');
      font-weight: 400;
      font-style: normal;
    }

    .upperButtonMenu {
        display: flex;
        justify-content: space-between;
      }

    .upper-container {
        display: flex;
        justify-content: flex-start;
        align-items: stretch;
        margin: 2rem 0 0 0;
        gap: 1rem;
        height: 100%;
    }
    .left {
        flex: 0 0 auto;
        background: #fff;
        border: 1px solid var(--container-border-color);
        padding: 1rem;
    }
    .right {
        flex: 1 1 0%;
        min-width: 0;
        overflow: hidden;
        display: flex;
        position: relative;
        background-repeat: no-repeat;
        background-position: center right;
        background-size: cover;
        border: 1px solid var(--container-border-color);
    }
    .kios-overlay {
        width: 100%;
        display: flex;
        justify-content: stretch;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.504) 20%, rgba(0,0,0,0) 100%);
        padding: 2rem;
        color: white;
    }
    .overlay-row {
        width: 100%;
        display: flex;
        gap: 2rem;
    }
    .fotoPengguna {
        position: relative;
        bottom: 0;
        left: 0;
        width: 6rem;
        height: 6rem;
        background-repeat: no-repeat;
        background-position: center right;
        background-size: cover;
        box-shadow: 0px 0px 100px #000000;
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
    .config-container {
        color: white;
    }
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }
    .content-header-text {
        display: flex;
        flex-direction: column;
        gap: .2rem;
    }
    .content-title {
        all: unset;
        font-family: 'Minecraft', sans-serif;
        font-size: 1.4rem;
        color: white;
    }
    .content-sub {
        all: unset;
        font-family: 'Pixel Operator', sans-serif;
        font-size: 1rem;
        opacity: .8;
        color: white;
    }
    .config-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 1rem;
        margin-top: .6rem;
        color: white;
    }
    .config-field {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        color: white;
    }
    .config-field.full {
        grid-column: 1 / -1;
    }
    .config-field { grid-column: span 6; }
    .config-field.full { grid-column: 1 / -1; }
    .config-field label {
        font-family: 'Pixel Operator', sans-serif;
        font-size: .95rem;
        opacity: 70%;
    }
    .config-value {
        font-family: 'Minecraft', sans-serif;
        font-size: 1rem;
    }
    .photo-row {
        display: flex;
        gap: 1rem;
        align-items: stretch;
    }
    .photo-box {
        background: #f8f8f8;
        border: 1px solid var(--container-border-color);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .photo-box.small {
        width: 140px;
        height: 140px;
        flex: 0 0 auto;
    }
    .photo-box.large {
        flex: 1 1 0%;
        min-height: 220px;
        max-height: 320px;
    }
    .photo-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        display: block;
    }
  </style>