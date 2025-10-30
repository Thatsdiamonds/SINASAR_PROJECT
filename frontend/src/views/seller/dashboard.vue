<template>
  <!-- Tombol Aksi - Header -->
  <div class="upperButtonMenu marginbttm">
    <div class="button" @click="this.$router.replace('/logout');">
        <img src="/icons/drought.svg" alt="Icon" width="auto" height="100%">
      <div class="text">
        <a>kembali</a>
        <a>halaman sebelumnya</a>
      </div>
    </div>

    <div style="display: inline-flex; gap: 1rem;">
      <div class="button" style="--border-color: #0D9C9C">
          <img src="/icons/sunglasses.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Edit Kios</a>
          <a>Klik di sini untuk edit kios</a>
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
        <div class="fotoPengguna" :style="{ backgroundImage: (fotoProfilSrc ? `url('${fotoProfilSrc}')` : `url('/icons/profile.svg')`)}"></div>
    </div>
</div>

  <!-- Panel Konfigurasi Kios (read-only) -->
  <div class="config-container">
    <div class="content-header marginbttm">
      <div class="content-header-text">
        <h2 class="content-title">Konfigurasi Kios</h2>
        <div class="content-sub">
          <span v-if="isLoading">Mengambil data...</span>
          <span v-else-if="kiosData && kiosData.lokasi">Informasi kios {{ kiosData.lokasi }}</span>
          <span v-else>Tidak ada data kios</span>
        </div>
      </div>
    </div>

    <div v-if="kiosData" class="config-grid">
      <div class="config-field">
        <label>Nama Kios</label>
        <div class="config-value">{{ kiosData.nama || '-' }}</div>
      </div>
      <div class="config-field">
        <label>Produk Utama</label>
        <div class="config-value">{{ kiosData.produk || '-' }}</div>
      </div>
      <div class="config-field">
        <label>Patokan Lokasi</label>
        <div class="config-value">{{ kiosData.patokan || '-' }}</div>
      </div>
      <div class="config-field full">
        <label>Deskripsi</label>
        <div class="config-value">{{ kiosData.deskripsi || '-' }}</div>
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

  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import api from '@/services/api';
  import { toPublicUrl } from '@/services/image';

  const route = useRoute();
  const isLoading = ref(false);
  const kiosData = ref(null);

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

  onMounted(fetchKiosDetail);
  </script>
  
  <style scoped>
  
  @import '@/styles/button.css';
  body {
      padding: 2.4rem;
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
        margin: 2rem 0;
        gap: 1rem;
    }
    .left {
        flex: 0 0 auto;
        background: #fff;
        border: 1px solid var(--container-border-color);
        border-radius: 8px;
        padding: 1rem;
    }
    .right {
        flex: 1 1 0%;
        min-width: 0;
        overflow: hidden;
        display: flex;
        align-items: end;
        background-repeat: no-repeat;
        background-position: center right;
        background-size: cover;
        border: 1px solid var(--container-border-color);
        border-radius: 8px;
    }
    .fotoPengguna {
        position: relative;
        bottom: 0;
        left: 0;
        width: 6rem;
        height: 6rem;
        margin: 2rem;
        background-repeat: no-repeat;
        background-position: center right;
        background-size: cover;
        box-shadow: 0px 0px 100px #000000;
        border-radius: 4px;
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
        color: #024196;
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
        margin-top: 1rem;
        border: 1px solid var(--container-border-color);
        background: #fff;
        padding: 1rem;
        border-radius: 8px;
    }
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    }
    .content-sub {
        all: unset;
        font-family: 'Pixel Operator', sans-serif;
        font-size: 1rem;
        opacity: .8;
    }
    .config-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 1rem;
        margin-top: .6rem;
    }
    .config-field {
        display: flex;
        flex-direction: column;
        gap: .3rem;
    }
    .config-field.full {
        grid-column: 1 / -1;
    }
    .config-field { grid-column: span 6; }
    .config-field.full { grid-column: 1 / -1; }
    .config-field label {
        font-family: 'Pixel Operator', sans-serif;
        font-size: .95rem;
        color: #222;
    }
    .config-value {
        font-family: 'Pixel Operator', sans-serif;
        font-size: 1rem;
        color: #000;
    }
    .photo-row {
        display: flex;
        gap: 1rem;
        align-items: stretch;
    }
    .photo-box {
        background: #f8f8f8;
        border: 1px solid var(--container-border-color);
        border-radius: 8px;
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