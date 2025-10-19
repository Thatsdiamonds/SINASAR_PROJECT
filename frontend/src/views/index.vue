<template>
  <div class="relative" :class="{ 'notVisible': isVisible }">
    <nav>
        <div class="left">
            <h1>Pasar Owi</h1>
            <h2>Jl. Prabangsa No. 4 Pati</h2>
        </div>
        <div class="right">
            <div style="display: inline-flex; gap: 1rem;">
              <div class="button" 
                   @click="toggle"
                   style="
                    --border-color: #E3E8EF;
                    --bg-color: transparent;
                    --color: #024196;
                    --sub-color: #0241969a;
                    --border-hov: #247CFF;
                    --bg-hov: #F8FAFD;
                    "

                    >
                <img src="/icons/lampu_suar.svg" alt="Icon" width="auto" height="100%">
                  <div class="text">
                    <h3>Cari kios...</h3>
                    <h4>Klik di sini atau klik "/" untuk membuka menu </h4>
                  </div>
              </div>

              <div v-if="!isAuthenticated" class="button" @click="router.push('/login')"
              style="
                --border-color: #247CFF;
                --bg-color: #247CFF;
                --color: #fff;
                --sub-color: #ffffffb0;
                --border-hov: #438fff;
                --bg-hov: #438fff;
                "
                >
                <img src="/icons/heart.svg" alt="Icon" width="auto" height="100%">
                  <div class="text">
                    <h3>Login</h3>
                    <h4>Masuk ke akun Anda</h4>
                  </div>
              </div>

              <div v-else style="display: inline-flex; gap: 1rem;">
                <div v-if="userRole == 'seller'" class="button" @click="router.push(`/seller/dashboard/detail-kios?lokasi=${userKiosLocation}`)"
                style="
                  --border-color: #247CFF;
                  --bg-color: #247CFF;
                  --color: #fff;
                  --sub-color: #ffffffb0;
                  --border-hov: #438fff;
                  --bg-hov: #438fff;
                  "
                >
                  <img src="/icons/heart.svg" alt="Icon" width="auto" height="100%">
                    <div class="text">
                      <h3>Kelola</h3>
                      <h4>Konfigurasi kios saya</h4>
                    </div>
                </div>
                <div v-if="userRole == 'admin'" class="button" @click="router.push('/admin/dashboard')" style="--border-color: #2ea7ff; --bg-color: #2ea7ff; --bg-hov: #1b96ff; --border-hov: #1b96ff; color: #fff;">
                  <img src="/icons/heart.svg" alt="Icon" width="auto" height="100%">
                    <div class="text">
                      <h3>Kelola</h3>
                      <h4>Panel konfigurasi Admin</h4>
                    </div>
                </div>
                <div class="button" @click="$router.push('/auth?auth-type=sign-out&auto-proceed=true');"
                  style="--border-color: #ff5b5b; --bg-color: #ff5b5b; --bg-hov: #f54f4f; --border-hov: #f54f4f; --color: #fff; --sub-color: #fff;">
                    <img src="/icons/drought.svg" alt="Icon" width="auto" height="100%">
                  <div class="text">
                    <h3>Logout</h3>
                    <h4>Keluar dari akun Anda</h4>
                  </div>
                </div>
              </div>
          </div>
        </div>
    </nav>
    <div class="maps-controls">
      <button class="zoom-btn" @click="zoomIn" title="Perbesar">
        <img src="/icons/plus.svg" alt="Zoom In" width="16" height="16">
      </button>
      <button class="zoom-btn" @click="zoomOut" title="Perkecil">
        <img src="/icons/minus.svg" alt="Zoom Out" width="16" height="16">
      </button>
      <button class="zoom-btn" @click="resetView" title="Reset Tampilan">
        <img src="/icons/car.svg" alt="Reset" width="16" height="16">
      </button>
    </div>

  </div>

  <!-- Area Peta -->
  <div
    class="maps-area" 
      :class="{ dragging: isDragging }"
    @mousedown="startDrag"
    @wheel.prevent="onWheel">
    <div 
      class="absolute top-0 left-0"
      :style="{
        transform: `translate(${offset.x}px, ${offset.y}px) scale(${scale})`
      }">

      <!-- Import komponen SVG Pasar Owi -->
      <PasarOwi class="w-full h-full"
        :hoveredRect="hoveredRect"
        :selectedKios="selectedKiosId"
        :isSearchMode="isVisible"
        :occupiedKiosIds="occupiedKiosIds"
        @rect-hover="onRectHover"
        @rect-out="onRectOut"
        @click="onSvgClick"
        @center-on-kios="onCenterOnKios"
      />
    </div>
  </div>

  <!-- Panel pencarian di sebelah kiri dengan animasi transisi -->
  <div class="sideLeft-container" :class="{ 'visible': isVisible }">
    <div class="panel">
      <div class="Box" :class="{ 'ing': Text }">
        <img src="/icons/searchPixelated.svg" alt="Icon" class="-icon">
        <input 
          id="input"
          v-model="Text"
          class="-text" 
          placeholder="Cari di Pasar Owi..."
          @keyup.esc="toggle"
          ref="Input">
        <img 
          src="/icons/close.svg" 
          alt="Icon" 
          class="close-icon"
          @click="clear"
          :class="{ 'visible': Text }"
          title="Clear ">
      </div>
            <!-- <div v-if="isLoading" class="loading-container">
              <div class="loading-spinner"></div>
              <p>Memuat data penjual...</p>
            </div> -->
            
            <!-- <div v-else-if="filteredPenjual.length === 0" class="empty-state">
              <p>Tidak ada penjual yang ditemukan</p>
            </div> -->
<div class="kios-list">
  <div v-if="isLoading">
    <div class="-results-info">
      <p>Mengambil data...</p>
    </div>
  </div>

  <div v-else>
    <div class="-results-info" @click="resetView()">
      <p>Ditemukan {{ filteredPenjual.length }} kios <a href="#" @click="fetchAllPenjual" onclick="return false" @mouseenter="updateHoverText" :title="`Terakhir mengambil data terbaru ${hoverText}`">Perbarui data</a></p>
    </div>
  </div>
              
  <div 
    v-for="penjual in filteredPenjual" 
    :key="penjual.id"
    class="kios-item"
    :class="{ 'kios-item-aktif': selectedPenjual && selectedPenjual.id === penjual.id }"
    @click="selectKiosFromSearch(penjual)"
  >
    <img class="kios-img" :src="toPublicUrl(penjual.foto_profil) || '/icons/profile.svg'" :alt="penjual.nama" />
    <div class="kios-info">
      <div class="kios-nama">{{ penjual.nama }}</div>
      <div class="kios-desc">{{ penjual.produk || 'Tidak ada informasi produk' }}</div>
      <div class="kios-id">{{ penjual.lokasi || 'Tidak ada informasi lokasi' }}</div>
    </div>
  </div>
  </div>
  </div>
  </div>

  <div class="sideRight-container" :class="{ 'visible': isVisible && !isLoading }">
    <div v-if="isLoading" class="panel loading-panel">
    </div>
    
    <div v-else-if="hasError" class="panel error-panel">
      <div class="error-header">
        <img src="/icons/close.svg" alt="Error" class="error-icon">
        <h2>Data Tidak Ditemukan</h2>
      </div>
      
      <div class="error-message">
        <p>{{ errorMessage }}</p>
      </div>
      
      <div class="error-suggestions">
        <h3>Coba deh ah:</h3>
        <ul>
          <li>Pilih kios lain di peta</li>
          <li>Klik "/" untuk fitur pencarian untuk mencari kios tertentu</li>
        </ul>
      </div>
    </div>
    
    <div v-else-if="selectedPenjual" class="panel kios-detail">
      <div class="kios-header">
        <img class="kios-profile" :src="toPublicUrl(selectedPenjual.foto_profil) || '/icons/profile.svg'" :alt="selectedPenjual.nama">
        <div class="kios-title">
          <h2>{{ selectedPenjual.nama || 'Nama Tidak Tersedia' }}</h2>
          <div class="kios-id">{{ selectedPenjual.lokasi }}</div>
        </div>
      </div>
      
      <div class="kios-main-image">
        <img :src="toPublicUrl(selectedPenjual.foto_kios) || '/icons/profile.svg'" :alt="selectedPenjual.nama">
      </div>
      
      <div class="kios-section">
        <p>{{ selectedPenjual.deskripsi || 'Tidak ada deskripsi tersedia' }}</p>
      </div>
      
      <div class="kios-section">
        <h3>Produk:</h3>
        <p>{{ selectedPenjual.produk || 'Tidak ada informasi produk' }}</p>
      </div>
      
      <div class="kios-section">
        <h3>Patokan:</h3>
        <p>{{ selectedPenjual.patokan || 'Tidak ada informasi patokan' }}</p>
      </div>
      
      <div class="kios-section">
        <h3>Kontak:</h3>
        <p>{{ selectedPenjual.kontak || 'Tidak ada informasi kontak' }}</p>
      </div>
      
      <button id="detail_kios" class="lihat-lokasi-btn" @click="$router.push(`detail-kios?lokasi=${selectedPenjual.lokasi}`)">
        <img src="/icons/Eye.svg" alt="Lokasi" class="btn-icon">
        Detail Kios
      </button>
      
      <button class="download-denah-btn" @click="downloadDenah">
        <img src="/icons/save.svg" alt="Download" class="btn-icon">
        Download Denah
      </button>
    </div>
  </div>
</template>

<style scoped>
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
@font-face {
  font-family: 'Minecraft Standard';
  src: url('@/fonts/MinecraftStandard.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
}

.rect-hover {
  fill: rgba(255, 0, 0, 0.5);
  cursor: pointer;
}

.highlight {
  fill: rgba(255, 0, 0, 0.7);
  stroke: #ff0000;
  stroke-width: 2;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 0.7; }
  50% { opacity: 1; }
  100% { opacity: 0.7; }
}

/* Styling untuk kios yang dipilih dari pencarian */
.kios-item-aktif {
  border-left: 6px solid #FF6B35 !important;
  background-color: rgba(255, 107, 53, 0.1);
  box-shadow: 0 2px 8px rgba(255, 107, 53, 0.2);
}

/* Efek hover yang lebih jelas untuk kios yang dipilih */
.kios-item-aktif:hover {
  background-color: rgba(255, 107, 53, 0.15);
  transform: translateX(3px);
}
* {
  margin: 0;
}

.relative {
  z-index: 2;
  position: absolute;
  top: 2rem;
  width: 100dvw;
  display: flex;
  justify-content: center;
  transform: translateY(0) scale(1);
  transition: transform 0.3s ease-out;
  pointer-events: none;
}

.relative.notVisible {
  transform: translateY(-140px) scale(0);
}

nav {
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 2px solid black;
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);  
  padding: 1.2rem;
  width: 96%;
  box-sizing: border-box;
}

.maps-area {
  height: 100%;
  width: 100%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  top: 0;
  left: 0;
  cursor: grab;
  transition: cursor 0.1s;
}

.maps-controls {
  position: absolute;
  width: 96%;
  display: flex;
  top: 8rem;
  justify-content: end;
  display: flex;
  pointer-events: none;
  gap: .8rem;
}

.zoom-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 45px;
  height: 45px;
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #247CFF;
  border-radius: 6px;
  pointer-events: auto;
  cursor: pointer;
  transition: all 0.2s ease;
}

.zoom-btn:hover {
  background: #438fff;
  transform: translateY(-1px);
}

.zoom-btn:hover img {
  filter: brightness(0) invert(1);
}

.maps-area.dragging {
  cursor: grabbing;
}

nav .left {
  display: flex;
  flex-direction: column;
}

nav h1 {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  width: fit-content;
  font-size: 1.44rem;
  align-items: flex-end;
}

nav h2 {
  all: unset;
  font-family: 'Pixel Operator', sans-serif;
  font-style: normal;
  font-size: 1.12rem;
}

nav .right {
  display: flex;
  align-items: center;
  justify-content: center;
}

nav .right .button {
  --border-color: #222;
  --bg-color: #fff;
  --color: #000;
  --sub-color: #3333339a;
  --border-hov: #024196;
  --bg-hov: transparent;
  
  pointer-events: auto;
  
  all: none;
  cursor: pointer;
  user-select: none;
  border: var(--border-color) 1.6px solid;
  color: var(--color);
  background-color: var(--bg-color);
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.48rem;
  padding: 0.64rem 0.8rem;
  height: fit-content;
  width: fit-content;
  transition: all 0.2s;
}

nav .right .button:hover {
  border-color: var(--border-hov);
  -webkit-box-shadow: inset 0px 0px 0px 2.4px var(--border-hov);
  -moz-box-shadow: inset 0px 0px 0px 2.4px var(--border-hov);
  box-shadow: inset 0px 0px 0px 2.4px var(--border-hov);
  background: var(--bg-hov);
}

nav .right img {
  height: 2.08rem;
}

nav .right h3 {
  height: fit-content;
  font-family: 'Minecraft', sans-serif;
  font-weight: 100;
  font-size: 1rem;
}

nav .right h4 {
  height: fit-content;
  font-family: 'Pixel Operator', sans-serif;
  font-weight: 100;
  font-size: 1rem;
}

nav .right .text {
  display: flex;
  flex-direction: column;
}

html {
  overflow: hidden;
}

.sideLeft-container {
  position: absolute;
  top: 0;
  left: 0;
  min-width: 20%;
  max-width: 30%;
  height: 91dvh;
  z-index: 3;
  transform: scale(0) translateX(-100%);
  transition: transform 0.3s ease-out;
}

.sideLeft-container.visible {
  transform: scale(1) translateX(0);
}

.sideRight-container {
  position: absolute;
  top: 0;
  right: 0;
  width: 320px;
  height: 91dvh;
  z-index: 3;
  transform: scale(0) translateX(-200px);
  transition: all 0.3s ease-out;
}

.sideRight-container.visible {
  transform: scale(1) translateX(0);
}

.kios-detail {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 0.8rem;
  font-family: 'Pixel Operator', sans-serif;
}

.kios-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.3rem;
}

.kios-profile {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  object-fit: cover;
}

.kios-title {
  display: flex;
  flex-direction: column;
}

.kios-title h2 {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  font-size: 1rem;
  margin-bottom: 0.1rem;
}

.kios-id {
  font-family: 'Minecraft Standard', sans-serif;
  font-size: 0.7rem;
  color: #333;
}

.kios-main-image {
  width: 100%;
  height: 180px;
  margin: 0.3rem 0;
  border-radius: 6px;
  overflow: hidden;
}

.kios-main-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.kios-description {
  margin-bottom: 0.3rem;
}

.kios-description p {
  font-size: 0.85rem;
  line-height: 1.3;
}

.kios-section {
  margin-bottom: 0.5rem;
}

.kios-section h3 {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  margin-bottom: 0.2rem;
  display: block;
}

.kios-section p {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  margin: 0;
  line-height: 1.3;
}

.lihat-lokasi-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.7rem;
  margin-top: 0.3rem;
  background: transparent;
  border: 2px solid #000;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.lihat-lokasi-btn:hover {
  background: rgba(0, 0, 0, 0.1);
}

.download-denah-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.7rem;
  margin-top: 0.3rem;
  background: transparent;
  border: 2px solid #024196;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #024196;
}

.download-denah-btn:hover {
  background: #024196;
  color: white;
  transform: translateY(-1px);
}

.btn-icon {
  width: 20px;
  height: 20px;
}

.panel {
  padding: 1.4rem;
  height: 100%;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);  
}
.Box {
  height: 2.4rem;
  padding: 0 .4rem;
  margin-bottom: .6rem;
  border: 1px solid black;
  display: flex;
  align-items: center;
  gap: .7rem;
}

.-icon {
  height: 1.5rem;
  width: 1.5rem;
  opacity: 70%;
}

.close-icon {
  height: 1.7rem;
  width: 1.7rem;
  margin-left: auto;
  opacity: 0;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.close-icon.visible {
  opacity: 0.7;
}

.close-icon:hover {
  opacity: 1;
}

.-text {
  all: unset;
  font-family: 'Minecraft Standard', sans-serif;
  font-size: .8rem;
  line-height: 1.2;
  flex: 1;
  width: 100%;
}

.-text:focus {
  outline: none;
  background: rgba(255, 255, 255, 0.2);
}

.Box.ing {
  border-color: #024196;
  box-shadow: 0 0 0 2px rgba(21, 161, 200, 0.2);
}

.kios-list {
  padding-right: 1rem;
  display: flex;
  flex-direction: column;
  gap: .8rem;
  height: 80dvh;
  overflow-y: auto;
}

.kios-item {
  display: flex;
  align-items: center;
  padding: .4rem .4rem;
  border-left: 6px solid #d3d3d3;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  cursor: pointer;
  border-radius: 6px;
  outline: none;
  user-select: none;
}
.kios-item:hover > .kios-info,
.kios-item:focus > .kios-info {
  transform: translateX(2px);
}

.kios-item-aktif {
  border-left: 6px solid #024196;
}

.kios-img {
  width: 48px;
  height: auto;
  object-fit: cover;
  border-radius: 2px;
  margin-right: 1rem;
}

.kios-info {
  flex: 1;
  width: fit-content;
  width: 60%;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease-in-out;
}

.kios-nama {
  all: unset;
  width: fit-content;
  font-family: 'Minecraft', 'Pixel Operator', sans-serif;
  font-size: 1rem;
}

.kios-desc {
  all: unset;
  width: fit-content;
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  width: 100%;
  opacity: 0.6;
  margin-right: 1rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

}

.kios-id {
  all: unset;
  width: fit-content;
  font-family: 'Minecraft Standard', sans-serif;
  font-size: .6rem;
  font-weight: bold;
  color: #222;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  height: 200px;
}

.loading-spinner {
  width: 10px;
  height: 10px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #666;
  text-align: center;
  padding: 0 20px;
}

.-results-info {
  display: flex;
  gap: .8rem;
  border-radius: 6px;
  margin: 0.6rem 0;
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  color: #333;
}

.-results-info p {
  margin: 0;
}

/* Efek highlight saat hover di item kios */
.kios-item:hover {
  background-color: rgba(21, 161, 200, 0.1);
  border-left-color: #024196;
}

/* Animasi fade-in untuk daftar hasil pencarian */
.kios-list {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Styling untuk panel error */
.error-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  font-family: 'Pixel Operator', sans-serif;
  text-align: center;
}

.error-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
  margin-bottom: 1rem;
}

.error-icon {
  width: 48px;
  height: 48px;
  opacity: 0.7;
  filter: invert(1);
}

.error-header h2 {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  font-size: 1.2rem;
  color: #d32f2f;
  margin: 0;
}

.error-message {
  background-color: rgba(211, 47, 47, 0.1);
  border: 1px solid rgba(211, 47, 47, 0.3);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.error-message p {
  margin: 0;
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  color: #d32f2f;
}

.error-suggestions {
  text-align: left;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.error-suggestions h3 {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  margin-bottom: 0.8rem;
  display: block;
  color: #333;
}

.error-suggestions ul {
  margin: 0;
  font-family: 'Pixel Operator', sans-serif;
  padding-left: 1.2rem;
}

.error-suggestions li {
  font-size: 1rem;
  margin-bottom: 0.4rem;
  color: #555;
}

.retry-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.8rem;
  background: #024196;
  border: 2px solid #024196;
  color: white;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
}

.retry-btn:hover {
  background: #0d7a9c;
  border-color: #0d7a9c;
  transform: translateY(-1px);
}

</style>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import toast from "@/services/toast";
import PasarOwi from "./admin/PasarOwi.vue";
import api from "@/services/api";
import { getUserRole } from "@/services/api";
import { getAllSellerProfile } from "@/services/api";
import { useRouter } from "vue-router";
import { toPublicUrl } from "@/services/image";
import 'js-loading-overlay'

const isAuthenticated = computed(() => !!localStorage.getItem('token'));
const userKiosLocation = ref(null); // Placeholder for user's kiosk location
const userRole = ref(null);

const fetchUserKiosLocation = async () => {
  try {
    if (userRole.value == "admin") return;
    else if (userRole.value == "seller") {
      const response = await api.get('/my-kios');
      userKiosLocation.value = response.data.lokasi;
    }
  } catch (error) {
    toast.error("Gagal mengambil informasi pengguna.");
  }
};

onMounted(() => {
  if (isAuthenticated.value) {
    getUserRole().then(role => {
      userRole.value = role;
    });
    fetchUserKiosLocation();
  }
});

const sudahHintWkwkwk = ref(false);
const lastFetched = ref(null);
const hoverText = ref("Belum pernah diambil");
const penjualList = ref([]);
const penjualCache = ref({});
const selectedPenjual = ref(null);
const selectedKiosId = ref(null); // ID kios yang dipilih dari pencarian
const scale = ref(1.5);
const offset = ref({ x: -20, y: 70 });
const minZoom = 1;
const maxZoom = 3;
const isDragging = ref(false);
const lastMouse = ref({ x: 0, y: 0 });
const hoveredRect = ref(null);
const Text = ref('');
const isVisible = ref(false);
const isLoading = ref(false);
const refreshInterval = ref(null);
const hasError = ref(false);
const errorMessage = ref('');
const router = useRouter();


///////////////////////////// HINT SHORTCUTS ///////////////////////////////
watch(isVisible, (newValue) => {
  if (newValue && !sudahHintWkwkwk.value) {
    toast.info("Tekan 'Esc' untuk keluar.");
    sudahHintWkwkwk.value = true;
  }
});
toast.info("Tekan '/' untuk membuka pencarian.");

// Filter daftar penjual berdasarkan kata kunci pencarian
const filteredPenjual = computed(() => {
  if (!Text.value.trim()) return penjualList.value;
  
  const Lower = Text.value.toLowerCase();
  return penjualList.value.filter(penjual => 
    penjual.nama?.toLowerCase().includes(Lower) || 
    penjual.produk?.toLowerCase().includes(Lower) ||
    penjual.lokasi?.toLowerCase().includes(Lower) ||
    penjual.deskripsi?.toLowerCase().includes(Lower) ||
    penjual.patokan?.toLowerCase().includes(Lower)
  );
});

// Cek apakah ada hasil pencarian yang ditemukan
const hasResults = computed(() => {
  return Text.value.trim() && filteredPenjual.value.length > 0;
});

// Kumpulan ID kios terpakai untuk penandaan di denah
const occupiedKiosIds = computed(() => {
  try {
    return (penjualList.value || [])
      .map(p => p?.lokasi)
      .filter(v => typeof v === 'string' && v.trim() !== '');
  } catch {
    return [];
  }
});

function startDrag(e) {
  isDragging.value = true;
  lastMouse.value = { x: e.clientX, y: e.clientY };
  window.addEventListener("mousemove", onDrag);
  window.addEventListener("mouseup", endDrag);
}

function onDrag(e) {
  if (!isDragging.value) return;
  const dx = e.clientX - lastMouse.value.x;
  const dy = e.clientY - lastMouse.value.y;
  offset.value = {
    x: offset.value.x + dx,
    y: offset.value.y + dy,
  };
  lastMouse.value = { x: e.clientX, y: e.clientY };
}

function endDrag() {
  isDragging.value = false;
  window.removeEventListener("mousemove", onDrag);
  window.removeEventListener("mouseup", endDrag);
}

function onWheel(e) {
  const zoomIntensity = 0.1;
  if (e.deltaY < 0) {
    scale.value = Math.min(scale.value + zoomIntensity, maxZoom);
  } else if (e.deltaY > 0) {
    scale.value = Math.max(scale.value - zoomIntensity, minZoom);
  }
}

// Tombol kontrol
const zoomIn = () => {
  scale.value = Math.min(scale.value + 0.2, maxZoom);
};

const zoomOut = () => {
  scale.value = Math.max(minZoom, scale.value - 0.2);
};

function resetView() {
  scale.value = 1;
  offset.value = { x: 0, y: 70 };
}

////////////////////////////////////// MENGAMBIL DATA KIOS /////////////////////////////////
// Ambil semua data penjual dari API
// async function fetchAllPenjual() {
//   isLoading.value = true;
//   try {
//     const res = await api.get('/penjual', { silent: true });
//     // Ambil data dari response API, handle struktur data yang berbeda
//     penjualList.value = res.data.data || res.data;
//     // Seed/refresh cache berdasarkan lokasi
//     const newCache = {};
//     for (const item of (penjualList.value || [])) {
//       if (item && item.lokasi) {
//         newCache[item.lokasi] = item;
//       }
//     }
//     penjualCache.value = newCache;
//     console.log("Data penjual berhasil diambil:", penjualList.value);
//   } catch (err) {
//     console.error("Error mengambil data penjual:", err);
//     toast.error("Gagal mengambil data penjual");
//   } finally {
//     isLoading.value = false;
//   }
// }

async function fetchAllPenjual() {
  isLoading.value = true;
  try {
    const res = await getAllSellerProfile(); 
    // respon sudah bntk array dari API

    // Daftar field yang gak boleh null / kosong
    const requiredFields = ['nama', 'lokasi', 'produk'];

    // Filter data biar cuma yang lengkap aja
    const filteredRes = res.filter(penjual => {
      return requiredFields.every(field => {
        const value = penjual[field];
        return value !== null && value !== undefined && value.toString().trim() !== '';
      });
    });

    // Baru assign ke reactive state
    penjualList.value = filteredRes;  
    lastFetched.value = new Date();
    updateHoverText();
    console.log("Data kios berhasil diambil:", penjualList.value);
  } catch (err) {
    console.error("Error mengambil data kios:", err);
    toast.error("Gagal mengambil data kios");
  } finally {
    JsLoadingOverlay.hide();
    isLoading.value = false;
  }
}


function updateHoverText() {
  if (!lastFetched.value) {
    hoverText.value = "Belum pernah diambil";
    return;
  }

  const diff = Math.floor((Date.now() - lastFetched.value.getTime()) / 1000);
  if (diff < 60) hoverText.value = `${diff} detik yang lalu`;
  else if (diff < 3600) hoverText.value = `${Math.floor(diff / 60)} menit yang lalu`;
  else if (diff < 86400) hoverText.value = `${Math.floor(diff / 3600)} jam yang lalu`;
  else hoverText.value = `${Math.floor(diff / 86400)} hari yang lalu`;
}
// Ambil data penjual tertentu berdasarkan ID atau lokasi
async function fetchPenjualById(id) {
  isLoading.value = true;
  hasError.value = false;
  errorMessage.value = '';
  
  try {
    // Konversi ID ke string untuk memastikan format yang benar
    const lokasi = typeof id === 'string' ? id : id.toString();
    console.log("Mengambil data penjual dengan lokasi:", lokasi);
    // Cek cache terlebih dahulu
    const cached = penjualCache.value[lokasi];
    if (cached) {
      selectedPenjual.value = cached;
      selectedKiosId.value = lokasi;
      isVisible.value = true;
      return; // gunakan cache, tidak perlu hit API detail
    }
    
    const res = await getUserRole.get(`/penjual/detail/${lokasi}`, { silent: true });
    
    // Cek apakah data ditemukan
    if (res.data.data && Object.keys(res.data.data).length > 0) {
      // Data ditemukan, tampilkan detail kios
      selectedPenjual.value = res.data.data;
      selectedKiosId.value = lokasi; // Update kios yang dipilih
      // Simpan ke cache untuk akses berikutnya
      penjualCache.value[lokasi] = res.data.data;
      hasError.value = false;
    } else {
      // Data tidak ditemukan
      hasError.value = true;
      errorMessage.value = `Kios dengan lokasi "${lokasi}" tidak ditemukan atau belum memiliki data`;
      selectedPenjual.value = null;
      selectedKiosId.value = null; // Clear kios yang dipilih
    }
    
    // Tampilkan panel pencarian dan detail kios
    isVisible.value = true;
  } catch (err) {
    console.error(`Error mengambil data penjual dengan ID ${id}:`, err);
    
    // Set error state
    hasError.value = true;
    errorMessage.value = `Gagal mengambil data kios "${id}". Silakan coba lagi nanti.`;
    selectedPenjual.value = null;
    selectedKiosId.value = null; // Clear kios yang dipilih saat error
    
    toast.info("Kios tidak ditemukan/kosong");
  } finally {
    isLoading.value = false;
  }
}

// Mulai pembaruan data secara otomatis
function startAutoRefresh() {
  // Refresh data setiap 5 menit untuk menjaga data tetap up-to-date
  const REFRESH_INTERVAL = 5 * 60 * 1000; // 5 menit dalam milidetik
  refreshInterval.value = setInterval(fetchAllPenjual, REFRESH_INTERVAL);
}

// Hentikan pembaruan otomatis
function stopAutoRefresh() {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
    refreshInterval.value = null;
  }
}

// Handle klik pada area kios di peta SVG
async function onSvgClick(e) {
  const targetId = e.target.id;
  if (!targetId) return;

  console.log(`Kios ${targetId} diklik!`);
  
  // Cek apakah kios memiliki class occupied-state
  const hasOccupiedState = e.target.classList.contains('occupied-state');
  
  if (!hasOccupiedState) {
    // Kios kosong/tidak ditemukan, tampilkan toast tanpa request ke backend
    toast.info("Kios kosong/tidak ditemukan");
    return;
  }
  
  // Clear error state dan selected penjual sebelum fetch data baru
  hasError.value = false;
  errorMessage.value = '';
  // Gunakan cache jika tersedia
  const cached = penjualCache.value[targetId];
  if (cached) {
    selectedPenjual.value = cached;
    selectedKiosId.value = targetId;
    isVisible.value = true;
  } else {
    selectedPenjual.value = null;
    selectedKiosId.value = targetId; // Set kios yang dipilih
    await fetchPenjualById(targetId);
  }
}

// Handle centering otomatis ke kios yang dipilih
function onCenterOnKios(data) {
  const { offsetX, offsetY } = data;
  
  // Update offset untuk centering kios yang dipilih
  offset.value = {
    x: offset.value.x + offsetX,
    y: offset.value.y + offsetY
  };
  
  console.log(`Centering ke kios ${data.kiosId} dengan offset:`, { offsetX, offsetY });
}

// Pilih kios dari hasil pencarian
async function selectKiosFromSearch(penjual) {
  // Set kios yang dipilih terlebih dahulu
  selectedKiosId.value = penjual.lokasi;
  
  // Reset view peta ke posisi default terlebih dahulu
  resetView();
  
  // Gunakan data lokal dari daftar (cache)
  if (penjual) {
    selectedPenjual.value = penjual;
    // Pastikan cache juga terisi/terkini
    if (penjual.lokasi) penjualCache.value[penjual.lokasi] = penjual;
    isVisible.value = true;
  } else {
    // Fallback jika tidak ada data
    await fetchPenjualById(penjual.lokasi);
  }
}

function onRectHover(id) {
  hoveredRect.value = id;
}
function onRectOut() {
  hoveredRect.value = null;
}

function clear() {
  Text.value = '';
  // Clear selected kios saat clear pencarian
  selectedKiosId.value = null;
  selectedPenjual.value = null;
}

// // Coba ambil data kios lagi jika sebelumnya gagal
// function retryFetch() {
//   if (selectedPenjual.value && selectedPenjual.value.lokasi) {
//     fetchPenjualById(selectedPenjual.value.lokasi);
//   }
// }

// Highlight kata kunci pencarian dalam teks
function highlightTerm(text, Term) {
  if (!Term || !text) return text;
  const regex = new RegExp(`(${Term})`, 'gi');
  return text.toString().replace(regex, '<mark>$1</mark>');
}

// Tampilkan tips pencarian ketika tidak ada hasil
function showTips() {
  if (Text.value.trim() && filteredPenjual.value.length === 0) {
    // toast.info("Coba cari dengan kata kunci lain atau periksa ejaan");
  }
}

function toggle() {
  isVisible.value = !isVisible.value;
  
  // Clear error state saat membuka/menutup pencarian
  if (isVisible.value) {
    hasError.value = false;
    errorMessage.value = '';
    document.getElementById('input').focus();
  } else {
    document.getElementById('input').blur();
    // Clear selected kios saat menutup pencarian
    selectedKiosId.value = null;
    selectedPenjual.value = null;
  }
}

// Handle shortcut keyboard untuk navigasi
function handleKeyDown(e) {
  // Tombol '/' untuk membuka/menutup pencarian (kecuali saat mengetik)
  if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
    e.preventDefault(); // Jangan tampilkan karakter '/' di input
    if (!isVisible.value) {
      toggle(); // Buka panel pencarian
    } else {
      document.getElementById('input').focus(); // Fokus ke input pencarian
    }
  }
  
  // Tombol ESC untuk menutup panel pencarian
  if (e.key === 'Escape' && isVisible.value) {
    toggle(); // Tutup panel pencarian
  }
}

// Pantau perubahan teks pencarian untuk memberikan feedback
watch(Text, (newValue) => {
  if (newValue.trim() && filteredPenjual.value.length === 0) {
    // Tunggu sebentar agar user selesai mengetik
    setTimeout(() => {
      if (Text.value.trim() && filteredPenjual.value.length === 0) {
        showTips();
      }
    }, 1000);
  }
});

// Setup dan cleanup saat komponen dibuka dan ditutup
onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
  
  // Load data penjual saat halaman pertama kali dibuka
  fetchAllPenjual();
  
  // Aktifkan pembaruan data otomatis
  startAutoRefresh();

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
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  
  // Matikan pembaruan otomatis saat meninggalkan halaman
  stopAutoRefresh();
});

// unduh denah, keren euyyy
async function downloadDenah() {
  if (!selectedPenjual.value) {
    toast.error("Pilih kios terlebih dahulu untuk mengunduh denah");
    return;
  }

  try {
    // Buat canvas untuk menggabungkan denah dan informasi kios
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // set ukuran canvas
    canvas.width = 1200;
    canvas.height = 800;
    
    // background putih
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // header dengan nama pasar
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 32px Minecraft, monospace';
    ctx.textAlign = 'center';
    ctx.fillText('PASAR OWI', canvas.width / 2, 50);
    ctx.font = '20px Pixel Operator, monospace';
    ctx.fillText('Jl. Prabangsa No. 4 Pati', canvas.width / 2, 80);
    
    // garis pemisah
    ctx.strokeStyle = '#024196';
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.moveTo(50, 100);
    ctx.lineTo(canvas.width - 50, 100);
    ctx.stroke();
    
    // Informasi kios yang dipilih
    const kios = selectedPenjual.value;
    ctx.font = 'bold 24px Minecraft, monospace';
    ctx.textAlign = 'left';
    ctx.fillStyle = '#FF6B35';
    ctx.fillText('INFORMASI KIOS YANG DIPILIH:', 50, 140);
    
    ctx.font = '18px Minecraft, monospace';
    ctx.fillStyle = '#000000';
    ctx.fillText(`Nama: ${kios.nama || 'Tidak tersedia'}`, 50, 170);
    ctx.fillText(`Lokasi: ${kios.lokasi || 'Tidak tersedia'}`, 50, 200);
    ctx.fillText(`Produk: ${kios.produk || 'Tidak tersedia'}`, 50, 230);
    ctx.fillText(`Kontak: ${kios.kontak || 'Tidak tersedia'}`, 50, 260);
    
    if (kios.deskripsi) {
      ctx.fillText(`Deskripsi: ${kios.deskripsi}`, 50, 290);
    }
    if (kios.patokan) {
      ctx.fillText(`Patokan: ${kios.patokan}`, 50, 320);
    }
    
    // Garis pemisah kedua
    ctx.strokeStyle = '#024196';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(50, 360);
    ctx.lineTo(canvas.width - 50, 360);
    ctx.stroke();
    
    // Label denah
    ctx.font = 'bold 24px Minecraft, monospace';
    ctx.textAlign = 'center';
    ctx.fillStyle = '#024196';
    ctx.fillText('DENAH PASAR (Kios yang dipilih ditandai)', canvas.width / 2, 400);
    
    // Ambil SVG denah dan konversi ke gambar
    resetView();
    const svgElement = document.querySelector('.maps-area svg');
    if (svgElement) {
      // Clone SVG untuk modifikasi
      const clonedSvg = svgElement.cloneNode(true);
      
      // Highlight kios yang dipilih
      const targetRect = clonedSvg.querySelector(`#${CSS.escape(kios.lokasi)}`);
      if (targetRect) {
        targetRect.style.fill = '#FF6B35';
        targetRect.style.stroke = '#FF6B35';
        targetRect.style.strokeWidth = '4';
        targetRect.style.filter = 'drop-shadow(0 0 10px rgba(255, 107, 53, 0.8))';
      }
      
      // Konversi SVG ke data URL
      const svgData = new XMLSerializer().serializeToString(clonedSvg);
      const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
      const svgUrl = URL.createObjectURL(svgBlob);
      
      // Buat gambar dari SVG
      const img = new Image();
      img.onload = () => {
        // Gambar denah di canvas
        const denahWidth = 500;
        const denahHeight = 250;
        const denahX = (canvas.width - denahWidth) / 2;
        const denahY = 420;
        
        ctx.drawImage(img, denahX, denahY, denahWidth, denahHeight);
        
        // Border untuk denah
        ctx.strokeStyle = '#024196';
        ctx.lineWidth = 3;
        ctx.strokeRect(denahX, denahY, denahWidth, denahHeight);
        
        // Footer
        ctx.font = '16px Pixel Operator, monospace';
        ctx.textAlign = 'center';
        ctx.fillStyle = '#666666';
        ctx.fillText(`Dibuat pada: ${new Date().toLocaleString('id-ID')}`, canvas.width / 2, canvas.height - 30);
        
        // Download gambar
        canvas.toBlob((blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `denah_pasar_owi_${kios.lokasi}_${new Date().toISOString().split('T')[0]}.png`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(url);
          URL.revokeObjectURL(svgUrl);
          
          toast.success("Denah berhasil diunduh!");
        }, 'image/png');
      };
      
      img.src = svgUrl;
    } else {
      toast.error("Tidak dapat menemukan denah SVG");
    }
    
  } catch (error) {
    console.error('Error generating denah:', error);
    toast.error("Gagal mengunduh denah. Silakan coba lagi.");
  }
}
</script>
