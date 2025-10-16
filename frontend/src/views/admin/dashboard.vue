<script setup>
import { ref, reactive, onMounted, computed, watch } from "vue";
import { useRoute } from 'vue-router';
import toast from "@/services/toast";
import PasarOwi from "./PasarOwi.vue";
import api from "@/services/api";
import { toPublicUrl } from "@/services/image";

// State untuk form konfigurasi kios
const kiosConfig = reactive({
  id: null,
  nama: '',
  deskripsi: '',
  produk: '',
  lokasi: '',
  patokan: '',
  kontak: '',
  foto_profil: null,
  foto_kios: null
});

// State untuk form konfigurasi pengguna
const userConfig = reactive({
  id: null,
  username: '',
  email: '',
  password: '',
  confirmPassword: '',
  confirmUsername: '' // Untuk konfirmasi hapus akun
});

// State untuk validasi
const errors = reactive({
  username: '',
  email: '',
  password: '',
  confirmPassword: ''
});

// State untuk menampilkan password
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isLoading = ref(false);
const penjualList = ref([]);
const penjualCache = ref({});
const selectedPenjual = ref(null);
const selectedKiosId = ref(null); // ID kios yang dipilih dari pencarian
const hoveredRect = ref(null); // Kios yang sedang di-hover di denah

// Kumpulan ID kios terpakai untuk penandaan di denah admin
const occupiedKiosIds = computed(() => {
  try {
    return (penjualList.value || [])
      .map(p => p?.lokasi)
      .filter(v => typeof v === 'string' && v.trim() !== '');
  } catch {
    return [];
  }
});

//////////////////////////////////////////////// Validasi form
const validateForm = () => {
  let isValid = true;
  
  // Reset error blah
  Object.keys(errors).forEach(key => errors[key] = '');
  
  // Validasi username
  if (!userConfig.username.trim()) {
    errors.username = 'Username wajib diisi';
    isValid = false;
  }
  
  // Validasi email
  if (!userConfig.email.trim()) {
    errors.email = 'Email wajib diisi';
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userConfig.email)) {
    errors.email = 'Format email tidak valid';
    isValid = false;
  }
  
  // Validasi password (opsional untuk update)
  if (userConfig.password && userConfig.password.length < 6) {
    errors.password = 'Password minimal 6 karakter';
    isValid = false;
  }
  
  // Validasi confirm password (hanya jika password diisi)
  if (userConfig.password && !userConfig.confirmPassword) {
    errors.confirmPassword = 'Konfirmasi password wajib diisi jika password diubah';
    isValid = false;
  } else if (userConfig.password && userConfig.password !== userConfig.confirmPassword) {
    errors.confirmPassword = 'Password tidak cocok';
    isValid = false;
  }
  
  return isValid;
};

// Handle simpan konfigurasi pengguna
const handleSaveUserConfig = async () => {
  if (!userConfig.id) {
    toast.error('Tidak ada user yang dipilih untuk disimpan');
    return;
  }

  if (!validateForm()) {
    return;
  }

  try {
    isLoading.value = true;
    
    // Prepare data for API
    const updateData = {
      username: userConfig.username,
      email: userConfig.email,
      password: userConfig.password || undefined, // Only send if not empty
    };

    const response = await api.put(`/user/${userConfig.id}`, updateData);
    
    if (response.data.message) {
      toast.success('Data pengguna berhasil disimpan');
      
      // Reset form setelah berhasil
      userConfig.id = null;
      userConfig.username = '';
      userConfig.email = '';
      userConfig.password = '';
      userConfig.confirmPassword = '';
      userConfig.confirmUsername = '';
      
      // Clear errors
      Object.keys(errors).forEach(key => errors[key] = '');
      
      // Refresh data
      await fetchAllPenjual();
    }
  } catch (error) {
    console.error('Error saving user:', error);
    
    // Handle validation errors from backend
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(key => {
        if (errors.hasOwnProperty(key)) {
          errors[key] = backendErrors[key][0];
        }
      });
    } else {
      toast.error('Gagal menyimpan data pengguna. Silakan coba lagi.');
    }
  } finally {
    isLoading.value = false;
  }
};

// Handle hapus akun pengguna
const handleDeleteUser = async () => {
  if (!userConfig.id) {
    toast.error('Tidak ada user yang dipilih untuk dihapus');
    return;
  }

  // Validasi konfirmasi username
  if (userConfig.confirmUsername !== userConfig.username) {
    toast.error('Username konfirmasi tidak cocok');
    return;
  }

  // Konfirmasi sebelum menghapus
  if (!confirm(`Beneran ni, nak hapus akun "${userConfig.username}"? \nIki bakale kabeh sing berkaitan dihapus.`)) {
    return;
  }

  try {
    isLoading.value = true;
    const response = await api.delete(`/user/${userConfig.id}`);
    
    if (response.data.message) {
      toast.success('Akun berhasil dihapus');
      
      // Reset form setelah berhasil
      userConfig.id = null;
      userConfig.username = '';
      userConfig.email = '';
      userConfig.password = '';
      userConfig.confirmPassword = '';
      userConfig.confirmUsername = '';
      
      // Reset kios config juga
      Object.keys(kiosConfig).forEach(key => {
        if (key === 'foto_profil' || key === 'foto_kios') {
          kiosConfig[key] = null;
        } else {
          kiosConfig[key] = '';
        }
      });
      
      // Clear selection
      selectedPenjual.value = null;
      selectedKiosId.value = null;
      
      // Refresh data
      await fetchAllPenjual();
    }
  } catch (error) {
    console.error('Error deleting user:', error);
    toast.error('Gagal menghapus akun. Silakan coba lagi.');
  } finally {
    isLoading.value = false;
  }
};


// Handle simpan konfigurasi kios
async function handleSaveKiosConfig() {
  try {
    const id_penjual = String(kiosConfig.id || '');

    // Ambil data cache berdasarkan lokasi yang sedang diedit
    const oldLokasi = selectedKiosId.value || kiosConfig.lokasi || '';
    const cached = oldLokasi ? penjualCache.value[oldLokasi] || null : null;

    // Helper untuk normalisasi string
    const norm = (v) => (typeof v === 'string' ? v.trim() : (v == null ? '' : String(v)));

    // Cek perubahan field teks
    const textUnchanged = (
      norm(kiosConfig.lokasi) === norm(cached?.lokasi) &&
      norm(kiosConfig.nama) === norm(cached?.nama) &&
      norm(kiosConfig.produk) === norm(cached?.produk) &&
      norm(kiosConfig.patokan) === norm(cached?.patokan) &&
      norm(kiosConfig.deskripsi) === norm(cached?.deskripsi) &&
      norm(kiosConfig.kontak) === norm(cached?.kontak)
    );

    // Cek perubahan gambar: File = pasti berubah; string dibandingkan string URL lama
    const fotoProfilChanged = (
      kiosConfig.foto_profil instanceof File ||
      (typeof kiosConfig.foto_profil === 'string' ? kiosConfig.foto_profil : null) !==
        (typeof cached?.foto_profil === 'string' ? cached.foto_profil : null)
    );
    const fotoKiosChanged = (
      kiosConfig.foto_kios instanceof File ||
      (typeof kiosConfig.foto_kios === 'string' ? kiosConfig.foto_kios : null) !==
        (typeof cached?.foto_kios === 'string' ? cached.foto_kios : null)
    );

    const nothingChanged = textUnchanged && !fotoProfilChanged && !fotoKiosChanged;

    if (!id_penjual) {
      toast.error('ID penjual tidak tersedia. Pilih kios terlebih dahulu.');
      return;
    }

    if (nothingChanged) {
      toast.info('Tidak ada perubahan untuk disimpan.');
      return;
    }

    const formData = new FormData();

    // Field teks (selalu kirim nilai terbaru agar backend konsisten)
    formData.append('lokasi', kiosConfig.lokasi || '');
    formData.append('nama', kiosConfig.nama || '');
    formData.append('produk', kiosConfig.produk || '');
    formData.append('patokan', kiosConfig.patokan || '');
    formData.append('deskripsi', kiosConfig.deskripsi || '');
    formData.append('kontak', kiosConfig.kontak || '');

    // Field file: kirim hanya jika benar-benar berubah menjadi File baru
          if (kiosConfig.foto_profil instanceof File) {
        formData.append('foto_profil', kiosConfig.foto_profil);
      } else if (kiosConfig.foto_profil === null && cached?.foto_profil) {
        // Jika gambar dihapus (null) dan sebelumnya ada gambar, kirim flag hapus
        formData.append('remove_foto_profil', '1');
      }
      
      if (kiosConfig.foto_kios instanceof File) {
        formData.append('foto_kios', kiosConfig.foto_kios);
      } else if (kiosConfig.foto_kios === null && cached?.foto_kios) {
        // Jika gambar dihapus (null) dan sebelumnya ada gambar, kirim flag hapus
        formData.append('remove_foto_kios', '1');
      }

    // Gunakan POST + _method=PUT agar kompatibel dengan upload multipart di Laravel
    formData.append('_method', 'PUT');
    const response = await api.post(`/penjual/${id_penjual}`, formData);

    // Update cache dan list jika respons mengandung data terbaru
    const updated = response?.data?.data || response?.data;
    if (updated) {
      const newLokasi = updated.lokasi || kiosConfig.lokasi || oldLokasi;

      // Jika lokasi berubah, migrasi key cache
      if (oldLokasi && newLokasi && oldLokasi !== newLokasi) {
        const oldData = penjualCache.value[oldLokasi] || {};
        delete penjualCache.value[oldLokasi];
        penjualCache.value[newLokasi] = { ...oldData, ...updated };
      } else if (newLokasi) {
        penjualCache.value[newLokasi] = { ...(penjualCache.value[newLokasi] || {}), ...updated };
      }

      // Sinkronkan ke list penjual
      const idxOld = penjualList.value.findIndex(p => p.lokasi === oldLokasi);
      const idxNew = penjualList.value.findIndex(p => p.lokasi === newLokasi);
      if (idxOld !== -1 && oldLokasi !== newLokasi) {
        // Lokasi berubah: pindahkan entry
        penjualList.value.splice(idxOld, 1);
      }
      if (idxNew !== -1) {
        penjualList.value[idxNew] = { ...penjualList.value[idxNew], ...updated };
      } else if (newLokasi) {
        penjualList.value.push({ ...(cached || {}), ...updated });
      }

      // Sync panel state setelah simpan
      syncPanelData({ ...(cached || {}), ...updated });
      toast.success('Perubahan berhasil disimpan.');
    }

    return response.data;
  } catch (error) {
    console.error('❌ Gagal simpan kios:', error?.response || error);
    throw error;
  }
}

// Handle reset form kios
const handleResetKiosConfig = () => {
  Object.keys(kiosConfig).forEach(key => {
    if (key.includes('foto')) {
      kiosConfig[key] = null;
    } else {
      kiosConfig[key] = '';
    }
  });
};

// Handle reset form
const handleResetUserConfig = () => {
  userConfig.id = null;
  userConfig.username = '';
  userConfig.email = '';
  userConfig.password = '';
  userConfig.confirmPassword = '';
  userConfig.confirmUsername = '';
  Object.keys(errors).forEach(key => errors[key] = '');
};

// Fungsi untuk sinkronisasi data antar panel
const syncPanelData = (kiosData) => {
  if (!kiosData) return;
     
  // Update selectedPenjual untuk highlight di panel Kios Terdaftar
  selectedPenjual.value = kiosData;
  selectedKiosId.value = kiosData.lokasi;
  
  // Update form konfigurasi kios
  kiosConfig.id = kiosData.id || '';
  kiosConfig.nama = kiosData.nama || '';
  kiosConfig.deskripsi = kiosData.deskripsi || '';
  kiosConfig.produk = kiosData.produk || '';
  kiosConfig.lokasi = kiosData.lokasi || '';
  kiosConfig.patokan = kiosData.patokan || '';
  kiosConfig.kontak = kiosData.kontak || '';
  kiosConfig.foto_profil = kiosData.foto_profil || null;
  kiosConfig.foto_kios = kiosData.foto_kios || null;
  
  // Update form konfigurasi pengguna (hanya username dan email)
  userConfig.id = kiosData.user?.id || null;
  userConfig.username = kiosData.user?.username || '';
  userConfig.email = kiosData.user?.email || '';
};

// Helper untuk membuat data kios kosong
const createEmptyKiosData = (lokasi) => ({
  lokasi,
  nama: '',
  deskripsi: '',
  produk: '',
  patokan: '',
  kontak: '',
  foto_profil: null,
  foto_kios: null
});

// Handle klik pada kios di denah
const handleKiosClick = async (event) => {
  const targetId = event.target.id;
  if (!targetId) return;

  console.log(`Kios ${targetId} diklik di denah!`);
  
  try {
    // Gunakan cache terlebih dahulu
    const cached = penjualCache.value[targetId];
    if (cached) {
      syncPanelData(cached);
      toast.success(`Kios ${targetId} dipilih.`);
      return;
    }
    // Ambil data kios berdasarkan lokasi jika cache miss
    const res = await api.get(`/penjual/detail/${targetId}`, { silent: true });
    
    if (res.data.data && Object.keys(res.data.data).length > 0) {
      // Data ditemukan, sinkronkan semua panel
      syncPanelData(res.data.data);
      // Simpan ke cache
      penjualCache.value[targetId] = res.data.data;
      toast.success(`Kios ${targetId} dipilih`);
    } else {
      // Data tidak ditemukan, buat data kosong
      syncPanelData(createEmptyKiosData(targetId));
      toast.info(`Kios ${targetId} belum memiliki data, silakan isi informasi`);
    }
  } catch (err) {
    console.error(`Error mengambil data kios ${targetId}:`, err);
    
    // Buat data kosong jika gagal fetch
    syncPanelData(createEmptyKiosData(targetId));
    toast.info(`Kios ${targetId} dipilih (data kosong)`);
  }
};

// Baca query ?linked= dan auto-pilih kios yang sesuai
const route = useRoute();
onMounted(async () => {
  await fetchAllPenjual();
  const linked = route.query?.linked;
  if (typeof linked === 'string' && linked.trim() !== '') {
    // Jika sudah ada di cache/list, pilih langsung; jika belum, fetch detail
    const id = linked.trim();
    const cached = penjualCache.value[id];
    if (cached) {
      syncPanelData(cached);
      toast.success(`Linked ke kios ${id}`);
    } else {
      try {
        const res = await api.get(`/penjual/detail/${id}`, { silent: true });
        const data = res?.data?.data || res?.data || null;
        if (data) {
          penjualCache.value[id] = data;
          syncPanelData(data);
          toast.success(`Linked ke kios ${id}`);
        }
      } catch {}
    }
  }
});

// Handle hover pada kios di denah
const handleKiosHover = (kiosId) => {
  hoveredRect.value = kiosId;
};

// Handle mouse out dari kios di denah
const handleKiosOut = () => {
  hoveredRect.value = null;
};

// Pilih kios dari panel Kios Terdaftar
const selectKiosFromSearch = (penjual) => {
  syncPanelData(penjual);
  if (penjual && penjual.lokasi) {
    penjualCache.value[penjual.lokasi] = penjual;
  }
  toast.success(`Kios ${penjual.lokasi} dipilih dari daftar`);
};

// Reset semua panel ke state awal
const resetAllPanels = () => {
  selectedPenjual.value = null;
  selectedKiosId.value = null;
  hoveredRect.value = null;
  handleResetKiosConfig();
  handleResetUserConfig();
};


async function fetchAllPenjual() {
  isLoading.value = true;
  try {
    const res = await api.get('/penjual', { silent: true });
    penjualList.value = res.data.data || res.data;
    const newCache = {};
    for (const item of (penjualList.value || [])) {
      if (item && item.lokasi) newCache[item.lokasi] = item;
    }
    penjualCache.value = newCache;
  } catch (err) {
    console.error("Error mengambil data penjual:", err);
    toast.error("Gagal mengambil data penjual");
  } finally {
    isLoading.value = false;
  }
}

// Upload Denah Modal Functions
const showUploadDenahModal = ref(false);
const denahDraft = ref(null);
const denahDraftUrl = ref(null);
const isUploading = ref(false);
const denahContainer = ref(null);
const minZoom = 0.5;
const maxZoom = 3;
const pasarOwiRef = ref(null);

// Enhanced zoom & pan system variables (similar to index.vue)
const scale = ref(1);
const offset = ref({ x: -20, y: 70 });
const isDragging = ref(false);
const lastMouse = ref({ x: 0, y: 0 });

const openUploadDenah = () => {
  denahDraft.value = null;
  denahDraftUrl.value = null;
  showUploadDenahModal.value = true;
};
const closeUploadDenahModal = () => {
  showUploadDenahModal.value = false;
  if (denahDraftUrl.value) URL.revokeObjectURL(denahDraftUrl.value);
  denahDraft.value = null;
  denahDraftUrl.value = null;
};
const handleDenahDraft = (event) => {
  const file = event.target.files[0];
  if (file && file.type === 'image/svg+xml') {
    if (denahDraftUrl.value) URL.revokeObjectURL(denahDraftUrl.value);
    denahDraft.value = file;
    denahDraftUrl.value = URL.createObjectURL(file);
  } else {
    toast.error('Pilih file SVG yang valid');
  }
};
const removeDenahDraft = () => {
  if (denahDraftUrl.value) URL.revokeObjectURL(denahDraftUrl.value);
  denahDraft.value = null;
  denahDraftUrl.value = null;
};
const uploadDenah = async () => {
  if (!denahDraft.value) return;
  try {
    const formData = new FormData();
    formData.append('denah_svg', denahDraft.value);
    const response = await api.post(`/update-denah/`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (response.data.success) {
      toast.success('Denah berhasil diupload!');
      closeUploadDenahModal();
      if (pasarOwiRef.value && pasarOwiRef.value.reloadSvg) {
        pasarOwiRef.value.reloadSvg();
      }
    } else {
      toast.error('Gagal upload denah');
    }
  } catch (error) {
    console.error('Error uploading denah:', error);
    toast.error('Gagal upload denah. Silakan coba lagi.');
  } finally {
    isUploading.value = false;
  }
};

// Enhanced Zoom & Pan Functions (similar to index.vue)
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

function resetView() {
  scale.value = 1;
  offset.value = { x: -20, y: 70 };
}

// Updated zoom functions for button controls
const zoomIn = () => {
  if (scale.value < maxZoom) {
    scale.value = Math.min(scale.value + 0.2, maxZoom);
  }
};

const zoomOut = () => {
  if (scale.value > minZoom) {
    scale.value = Math.max(scale.value - 0.2, minZoom);
  }
};

const resetZoom = () => {
  resetView();
};

onMounted(() => {
  fetchAllPenjual();
});

// Untuk revoke object URL
let fotoProfilObjectUrl = null;
let fotoKiosObjectUrl = null;

const fotoProfilSrc = computed(() => {
  if (kiosConfig.foto_profil instanceof File) {
    if (fotoProfilObjectUrl) URL.revokeObjectURL(fotoProfilObjectUrl);
    fotoProfilObjectUrl = URL.createObjectURL(kiosConfig.foto_profil);
    return fotoProfilObjectUrl;
  }
  if (typeof kiosConfig.foto_profil === 'string' && kiosConfig.foto_profil.trim() !== '') {
    return toPublicUrl(kiosConfig.foto_profil);
  }
  return null;
});

const fotoKiosSrc = computed(() => {
  if (kiosConfig.foto_kios instanceof File) {
    if (fotoKiosObjectUrl) URL.revokeObjectURL(fotoKiosObjectUrl);
    fotoKiosObjectUrl = URL.createObjectURL(kiosConfig.foto_kios);
    return fotoKiosObjectUrl;
  }
  if (typeof kiosConfig.foto_kios === 'string' && kiosConfig.foto_kios.trim() !== '') {
    return toPublicUrl(kiosConfig.foto_kios);
  }
  return null;
});

const removeFotoProfil = () => {
  if (fotoProfilObjectUrl) URL.revokeObjectURL(fotoProfilObjectUrl);
  fotoProfilObjectUrl = null;
  kiosConfig.foto_profil = null;
};
const removeFotoKios = () => {
  if (fotoKiosObjectUrl) URL.revokeObjectURL(fotoKiosObjectUrl);
  fotoKiosObjectUrl = null;
  kiosConfig.foto_kios = null;
};

watch(() => kiosConfig.foto_profil, (val, oldVal) => {
  if (oldVal instanceof File && fotoProfilObjectUrl) {
    URL.revokeObjectURL(fotoProfilObjectUrl);
    fotoProfilObjectUrl = null;
  }
});
watch(() => kiosConfig.foto_kios, (val, oldVal) => {
  if (oldVal instanceof File && fotoKiosObjectUrl) {
    URL.revokeObjectURL(fotoKiosObjectUrl);
    fotoKiosObjectUrl = null;
  }
});

const showFotoProfilModal = ref(false);
const showFotoKiosModal = ref(false);
const fotoProfilDraft = ref(null);
const fotoKiosDraft = ref(null);
const fotoProfilDraftUrl = ref(null);
const fotoKiosDraftUrl = ref(null);

const openFotoProfilModal = () => {
  fotoProfilDraft.value = null;
  fotoProfilDraftUrl.value = null;
  showFotoProfilModal.value = true;
};
const openFotoKiosModal = () => {
  fotoKiosDraft.value = null;
  fotoKiosDraftUrl.value = null;
  showFotoKiosModal.value = true;
};
const closeFotoProfilModal = () => {
  showFotoProfilModal.value = false;
  if (fotoProfilDraftUrl.value) URL.revokeObjectURL(fotoProfilDraftUrl.value);
  fotoProfilDraft.value = null;
  fotoProfilDraftUrl.value = null;
};
const closeFotoKiosModal = () => {
  showFotoKiosModal.value = false;
  if (fotoKiosDraftUrl.value) URL.revokeObjectURL(fotoKiosDraftUrl.value);
  fotoKiosDraft.value = null;
  fotoKiosDraftUrl.value = null;
};
const handleFotoProfilDraft = (event) => {
  const file = event.target.files[0];
  if (file && file.type.startsWith('image/')) {
    if (fotoProfilDraftUrl.value) URL.revokeObjectURL(fotoProfilDraftUrl.value);
    fotoProfilDraft.value = file;
    fotoProfilDraftUrl.value = URL.createObjectURL(file);
  } else {
    toast.error('Pilih file gambar yang valid');
  }
};
const handleFotoKiosDraft = (event) => {
  const file = event.target.files[0];
  if (file && file.type.startsWith('image/')) {
    if (fotoKiosDraftUrl.value) URL.revokeObjectURL(fotoKiosDraftUrl.value);
    fotoKiosDraft.value = file;
    fotoKiosDraftUrl.value = URL.createObjectURL(file);
  } else {
    toast.error('Pilih file gambar yang valid');
  }
};
const saveFotoProfil = () => {
  if (fotoProfilDraft.value) {
    kiosConfig.foto_profil = fotoProfilDraft.value;
    closeFotoProfilModal();
  }
};
const saveFotoKios = () => {
  if (fotoKiosDraft.value) {
    kiosConfig.foto_kios = fotoKiosDraft.value;
    closeFotoKiosModal();
  }
};

const removeFotoProfilDraft = () => {
  if (fotoProfilDraftUrl.value) URL.revokeObjectURL(fotoProfilDraftUrl.value);
  fotoProfilDraft.value = null;
  fotoProfilDraftUrl.value = null;
};
const removeFotoKiosDraft = () => {
  if (fotoKiosDraftUrl.value) URL.revokeObjectURL(fotoKiosDraftUrl.value);
  fotoKiosDraft.value = null;
  fotoKiosDraftUrl.value = null;
};
</script>

<template>
  <div class="body-wrapper">
  <!-- Tombol Aksi - Header -->
  <div class="upperButtonMenu marginbttm">
    <div class="button" @click="$router.push('/logout');">
        <img src="/icons/drought.svg" alt="Icon" width="auto" height="100%">
      <div class="text">
        <a>Logout</a>
        <a>Keluar dari akun Anda</a>
      </div>
    </div>

    <div style="display: inline-flex; gap: 1rem;">
      <div class="button" style="--border-color: #0D9C9C" @click="$router.push('/admin/register');">
          <img src="/icons/rocket.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Halaman Add Seller</a>
          <a>Klik di sini untuk berpindah</a>
        </div>
      </div>

      <div class="button" style="--border-color: #0D9C9C">
          <img src="/icons/sunglasses.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>Kelola</a>
          <a>Klik di sini untuk berpindah</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Header -->
  <div class="marginbttm" style="display: flex; align-items: center; justify-content: space-between;">
    <div class="header">
      <h2 class="subheading">Panel Kontrol Manajemen Sinasar</h2>
      <h1 class="heading">Pasar Owi</h1>
    </div>

    <div style="display: inline-flex; gap: 1rem;">
      <div class="button" 
           style="--border-color: #15a1c8; --bg-color: #15a1c8; --color: #fff; --bg-hov: #16abd5"
           @click="resetAllPanels">
        <img src="/icons/save.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>RESET SEMUA PANEL</a>
        </div>
      </div>

      <div class="button" 
           style="--border-color: #e57373; --bg-color: transparent; --color: #e57373; --border-hov: #d32f2f; --bg-hov: #ffebee"
           @click="resetAllPanels">
        <img src="/icons/cancel.svg" alt="Icon" width="auto" height="100%">
          <div class="text">
          <a>Reset Semua</a>
          </div>
      </div>
    </div>
  </div>

  <!-- Layout -->
  <div class="main-layout equal-height-layout">
    <!-- Area Denah -->
    <div class="content-container equal-height-item" style="overflow: hidden;">
      <div class="content-header marginbttm">
        <div class="content-header-text">
          <h2 class="content-title">Editor Denah Pasar</h2>
          <div class="content-sub">Total sebanyak 16 kios</div>
        </div>
        <div class="button" style="--border-color: #19a7a7; --bg-color: #19a7a7; --color: #fff; --sub-color: #ffffff9a; --bg-hov: #16abd5" @click="openUploadDenah">
          <img src="/icons/cloud.svg" alt="Icon" width="auto" height="100%">
          <div class="text">
            <a>Upload Denah</a>
          </div>
        </div>
      </div>

      <!-- Denah Pasar Owi dengan komponen PasarOwi -->
      <div class="denah-area marginbttm">
        <div class="denah-controls">
          <button class="zoom-btn" @click="zoomIn" title="Zoom In">
            <img src="/icons/plus.svg" alt="Zoom In" width="16" height="16">
          </button>
          <button class="zoom-btn" @click="zoomOut" title="Zoom Out">
            <img src="/icons/minus.svg" alt="Zoom Out" width="16" height="16">
          </button>
          <button class="zoom-btn" @click="resetZoom" title="Reset Zoom">
            <img src="/icons/car.svg" alt="Reset" width="16" height="16">
          </button>
        </div>
        <div 
          class="denah-container" 
          ref="denahContainer"
          :class="{ dragging: isDragging }"
          @mousedown="startDrag"
          @wheel.prevent="onWheel">
          <div 
            class="absolute top-0 left-0"
            :style="{
              transform: `translate(${offset.x}px, ${offset.y}px) scale(${scale})`
            }">
            <PasarOwi 
              ref="pasarOwiRef"
              :hoveredRect="hoveredRect"
              :selectedKios="selectedKiosId"
              :isSearchMode="true"
              :occupiedKiosIds="occupiedKiosIds"
              @rect-hover="handleKiosHover"
              @rect-out="handleKiosOut"
              @click="handleKiosClick"
            />
          </div>
        </div>
      </div>

      <div class="denah-tip">
        <img src="/icons/pro.svg" alt="Icon" width="auto" height="100%">
        <div class="tip-text">
          <span class="tip-title">Pro Tip</span>
          <span class="tip-desc">Klik kios dalam denah untuk memilih target pengeditan</span>
        </div>
      </div>
    </div>

    <!-- Kios Terdaftar Container -->
    <div class="content-container equal-height-item" style="flex: 2 0 0; min-width: 340px;">
      <div class="content-header marginbttm">
        <div class="content-header-text">
          <h2 class="content-title">Kios Terdaftar</h2>
            <div v-if="isLoading">
              <div class="content-sub">Mengambil data...</div>
            </div>

            <div v-else>
                <div class="content-sub">Ditemukan {{ penjualList.length }} kios <a href="#" @click="fetchAllPenjual" onclick="return false">Perbarui data</a></div>
            </div>
        </div>
        <!--
        <div class="button" style="--border-color: #19a7a7; --bg-color: #19a7a7; --color: #fff; --sub-color: #ffffff9a; --bg-hov: #16abd5">
          <img src="/icons/search.svg" alt="Icon" width="auto" height="100%">
          <div class="text">
          <a>Cari</a>
          </div>
        </div>
        -->
      </div>
      <div class="kios-list">
        <div 
          v-for="penjual in penjualList" 
          :key="penjual.id"
          class="kios-item"
          :class="{ 'kios-item-aktif': selectedPenjual && selectedPenjual.lokasi === penjual.lokasi && penjual.lokasi !== null, 'kios-item-no-data': penjual.lokasi === null }"
          @click="selectKiosFromSearch(penjual)">

            <img class="kios-img" :src="toPublicUrl(penjual.foto_profil) || '/icons/profile.svg'" :alt="penjual.nama" />
            <div class="kios-info">
                <div class="kios-nama">{{ penjual.nama }}</div>
                <div class="kios-desc">{{ penjual.produk || penjual.lokasi || 'Tidak ada informasi produk' }}</div>
            </div>
            <div class="kios-id">{{ penjual.lokasi }}</div>
          </div>
      </div>
    </div>
  </div>

  <!-- Konfigurasi Section -->
  <div class="config-layout equal-height-layout">
    <!-- Konfigurasi Kios -->
    <div id="konfigurasi-kios" class="content-container equal-height-item">
      <div class="content-header marginbttm">
        <div class="content-header-text">
          <h2 class="content-title">Konfigurasi Kios</h2>
          <div class="content-sub">
            <span v-if="selectedPenjual">Edit informasi kios {{ selectedPenjual.lokasi }}</span>
            <span v-else class="no-selection">Pilih kios dari denah atau daftar untuk mengedit</span>
          </div>
        </div>
        <div style="display: flex; gap: 1rem;">
          <div class="button" 
               style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #e57373; --bg-hov: #f8d7da"
               @click="handleResetKiosConfig">
            <img src="/icons/change_file.svg" alt="Icon" width="auto" height="100%">
            <div class="text">
            <a>Batal</a>
            </div>
          </div>
          <div class="button" 
               style="--border-color: #16abd5; --bg-color: #19a7a7; --color: #fff; --border-hov: #16abd5; --bg-hov: #16abd5"
               @click="handleSaveKiosConfig();">
            <img src="/icons/save_pixel.svg" alt="Icon" width="auto" height="100%">
            <div class="text">
            <span>Simpan</span>
            </div>
          </div>
        </div>
      </div>

      <div class="config-form">
      <div class="config-row">
        <div class="config-field" style="display: none">
        <label>Id Penjual</label>
            <input type="text" v-model="kiosConfig.id" />
        </div>
        <div class="config-field">
        <label>Posisi Kios</label>
            <input type="text" v-model="kiosConfig.lokasi" placeholder="Contoh: L1_001" />
        </div>
        <div class="config-field">
            <label>Nama Kios</label>
            <input type="text" v-model="kiosConfig.nama" placeholder="Nama penjual" />
        </div>
        </div>
        
        <div class="config-row">
          <div class="config-field">
            <label>Produk Utama</label>
            <input type="text" v-model="kiosConfig.produk" placeholder="Produk yang dijual" />
      </div>
          <div class="config-field">
            <label>Patokan Lokasi</label>
            <input type="text" v-model="kiosConfig.patokan" placeholder="Patokan lokasi kios" />
          </div>
        </div>
        
      <div class="config-field">
        <label>Deskripsi Kios</label>
          <textarea rows="3" v-model="kiosConfig.deskripsi" placeholder="Deskripsi lengkap kios dan produk"></textarea>
      </div>
        
        <div class="config-field">
          <label>Kontak</label>
          <input type="text" v-model="kiosConfig.kontak" placeholder="Nomor telepon atau kontak lain" />
        </div>
        
        <div class="config-row">
          <div class="config-field">
            <label>Foto Profil</label>
            <div class="file-upload-container">
              <div v-if="fotoProfilSrc" class="image-preview-wrapper">
                <div class="image-preview-container">
                  <img
                    :src="fotoProfilSrc"
                    alt="Preview Foto Profil"
                    class="image-preview"
                  />
                </div>
                <div class="preview-actions">
                  <button class="button overlay-btn" type="button" @click="openFotoProfilModal" title="Ganti Foto">
                    <div class="text"><a>Ganti</a></div>
                  </button>
                  <button class="button overlay-btn" type="button" @click="removeFotoProfil" title="Hapus Foto">
                    <div class="text"><a>Hapus</a></div>
                  </button>
                </div>
              </div>
              <div v-else>
                <button class="button overlay-btn" type="button" @click="openFotoProfilModal" title="Pilih Foto">
                  <img src="/icons/plus.svg" alt="Pilih" width="16" height="16" />
                  <div class="text"><a>Pilih Gambar</a></div>
                </button>
              </div>
            </div>
          </div>
          
          <div class="config-field">
            <label>Foto Kios</label>
            <div class="file-upload-container">
              <div v-if="fotoKiosSrc" class="image-preview-wrapper">
                <div class="image-preview-container">
                  <img
                    :src="fotoKiosSrc"
                    alt="Preview Foto Kios"
                    class="image-preview"
                  />
                </div>
                <div class="preview-actions">
                  <button class="button overlay-btn" type="button" @click="openFotoKiosModal" title="Ganti Foto">
                    <div class="text"><a>Ganti</a></div>
                  </button>
                  <button class="button overlay-btn" type="button" @click="removeFotoKios" title="Hapus Foto">
                    <div class="text"><a>Hapus</a></div>
                  </button>
                </div>
              </div>
              <div v-else>
                <button class="button overlay-btn" type="button" @click="openFotoKiosModal" title="Pilih Foto">
                  <img src="/icons/plus.svg" alt="Pilih" width="16" height="16" />
                  <div class="text"><a>Pilih Gambar</a></div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Konfigurasi Pengguna -->
    <div class="content-container equal-height-item">
      <div class="content-header marginbttm">
      <div class="content-header-text">
        <h2 class="content-title">Konfigurasi Pengguna</h2>
        <div class="content-sub">
          <span v-if="selectedPenjual">Edit pengguna untuk kios {{ selectedPenjual.lokasi }}</span>
          <span v-else class="no-selection">Pilih kios dari denah atau daftar untuk mengedit pengguna</span>
        </div>
      </div>
        <div style="display: flex; gap: 1rem;">
          <div class="button" 
               style="--border-color: #030303; --bg-color: transparent; --color: #000; --border-hov: #e57373; --bg-hov: #f8d7da"
               @click="handleResetUserConfig">
            <img src="/icons/close.svg" alt="Icon" width="auto" height="100%">
            <div class="text">
            <a>Batal</a>
            </div>
          </div>
          <div class="button" 
               style="--border-color: #16abd5; --bg-color: #19a7a7; --color: #fff; --border-hov: #16abd5; --bg-hov: #16abd5"
               @click="handleSaveUserConfig">
            <img src="/icons/save_pixel.svg" alt="Icon" width="auto" height="100%">
            <div class="text">
            <a>Simpan</a>
            </div>
          </div>
        </div>
      </div>
      <div class="config-form">
        <input 
          type="hidden" 
          v-model="userConfig.id" />

        <div class="config-field">
          <label>Username</label>
          <input 
            type="text" 
            v-model="userConfig.username"
            :class="{ 'error': errors.username }"
            placeholder="Masukkan username" />
          <span v-if="errors.username" class="error-message">{{ errors.username }}</span>
          </div>
        
        <div class="config-field">
          <label>Email</label>
          <input 
            type="email" 
            v-model="userConfig.email"
            :class="{ 'error': errors.email }"
            placeholder="Masukkan email" />
          <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
        </div>
        
        <div class="config-row">
          <div class="config-field">
            <label>Password</label>
            <div class="password-input-container">
              <input 
                :type="showPassword ? 'text' : 'password'" 
                v-model="userConfig.password"
                :class="{ 'error': errors.password }"
                placeholder="Minimal 6 karakter" />
              <button 
                type="button" 
                class="password-toggle-btn"
                @click="showPassword = !showPassword">
                <img src="/icons/Eye.svg" alt="Toggle" width="16" height="16">
              </button>
          </div>
            <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
        </div>
          
        <div class="config-field">
            <label>Konfirmasi Password</label>
            <div class="password-input-container">
              <input 
                :type="showConfirmPassword ? 'text' : 'password'" 
                v-model="userConfig.confirmPassword"
                :class="{ 'error': errors.confirmPassword }"
                placeholder="Ulangi password" />
              <button 
                type="button" 
                class="password-toggle-btn"
                @click="showConfirmPassword = !showConfirmPassword">
                <img src="/icons/Eye.svg" alt="Toggle" width="16" height="16">
              </button>
        </div>
            <span v-if="errors.confirmPassword" class="error-message">{{ errors.confirmPassword }}</span>
          </div>
        </div>

        <!-- Delete Account Section -->
        <div v-if="userConfig.id" class="delete-section">
          <div class="config-field">
          <span>Hapus Akun</span>
            <label>Ketik ulang username untuk konfirmasi</label>
            <div class="delete-section-flex">
            <input 
              type="text" 
              v-model="userConfig.confirmUsername"
              placeholder="Ketik username yang akan dihapus" />
          <div class="button" 
               style="--border-color: #e57373; --bg-color: transparent; --color: #e57373; --border-hov: #d32f2f; --bg-hov: #ffebee; "
               @click="handleDeleteUser">
            <div class="text">
              <a>Hapus</a>
            </div>
          </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Upload Denah Modal -->
  <div v-if="showUploadDenahModal" class="modal-overlay" @click="closeUploadDenahModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Upload Denah Baru</h3>
        <button class="modal-close" @click="closeUploadDenahModal">
          <img src="/icons/close.svg" alt="Close" width="auto" height="100%">
        </button>
      </div>
      <div class="modal-body">
        <div class="file-upload-container">
          <input 
            type="file" 
            accept=".svg"
            @change="handleDenahDraft"
            class="file-input"
            style="display: none;"
            ref="denahDraftInput"
          />
          <div v-if="denahDraftUrl === null" class="upload-area" @click="$refs.denahDraftInput.click()">
            <img src="/icons/cloud.svg" alt="Upload" width="48" height="48">
            <p>Klik untuk memilih file SVG</p>
            <span class="file-info">Format yang didukung: SVG</span>
          </div>
          <div v-else class="selected-file">
            <img :src="denahDraftUrl" alt="Preview" style="width: 100%; height: auto; border-radius: 8px;" />
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
              <p>File terpilih: {{ denahDraft?.name }}</p>
              <button class="remove-file-btn" @click="removeDenahDraft">
                <img src="/icons/close.svg" alt="Remove" width="16" height="16">
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="modal-btn secondary" @click="closeUploadDenahModal">Batal</button>
        <button 
          class="modal-btn primary" 
          @click="uploadDenah"
          :disabled="!denahDraft || isUploading"
        >
          <span v-if="isUploading">Mengupload...</span>
          <span v-else>Upload Denah</span>
        </button>
      </div>
    </div>
  </div>
<!-- Modal Upload Foto Profil -->
  <div v-if="showFotoProfilModal" class="modal-overlay" @click="closeFotoProfilModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Upload Foto Profil</h3>
        <button class="modal-close" @click="closeFotoProfilModal">
          <img src="/icons/close.svg" alt="Close" width="auto" height="100%">
        </button>
      </div>
      <div class="modal-body">
        <div v-if="fotoProfilDraftUrl === null" class="file-upload-container">
          <input 
            type="file" 
            accept="image/jpg,image/jpeg,image/png"
            @change="handleFotoProfilDraft"
            class="file-input"
            style="display: none;"
            ref="fotoProfilDraftInput"
          />
          <div class="upload-area" @click="$refs.fotoProfilDraftInput.click()">
            <img src="/icons/plus.svg" alt="Upload" width="48" height="48">
            <p>Klik untuk memilih file gambar</p>
            <span class="file-info">Format: JPG, JPEG, PNG</span>
          </div>
        </div>
          <div v-else class="selected-file">
            <img :src="fotoProfilDraftUrl" alt="Preview" style="width: 100%; height: auto; border-radius: 8px;"/>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
              <p>File terpilih: {{ fotoProfilDraft?.name }}</p>
              <button class="remove-file-btn" @click="removeFotoProfilDraft">
                <img src="/icons/close.svg" alt="Remove" width="16" height="16">
                Hapus
              </button>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button class="modal-btn secondary" @click="closeFotoProfilModal">Batal</button>
        <button 
          class="modal-btn primary" 
          @click="saveFotoProfil"
          :disabled="!fotoProfilDraft"
        >
          Simpan
        </button>
      </div>
    </div>
  </div>
<!-- Modal Upload Foto Kios -->
  <div v-if="showFotoKiosModal" class="modal-overlay" @click="closeFotoKiosModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Upload Foto Kios</h3>
        <button class="modal-close" @click="closeFotoKiosModal">
          <img src="/icons/close.svg" alt="Close" width="auto" height="100%">
        </button>
      </div>
      <div class="modal-body">
        <div class="file-upload-container">
          <input 
            type="file" 
            accept="image/jpg,image/jpeg,image/png"
            @change="handleFotoKiosDraft"
            class="file-input"
            style="display: none;"
            ref="fotoKiosDraftInput"
          />
          <div v-if="fotoKiosDraftUrl === null" class="upload-area" @click="$refs.fotoKiosDraftInput.click()">
            <img src="/icons/plus.svg" alt="Upload" width="48" height="48">
            <p>Klik untuk memilih file gambar</p>
            <span class="file-info">Format: JPG, JPEG, PNG</span>
          </div>
          <div v-else class="selected-file">
            <img :src="fotoKiosDraftUrl" alt="Preview" style="width: 100%; height: auto; border-radius: 8px;"/>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
              <p>File terpilih: {{ fotoKiosDraft?.name }}</p>
              <button class="remove-file-btn" @click="removeFotoKiosDraft">
                <img src="/icons/close.svg" alt="Remove" width="16" height="16">
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="modal-btn secondary" @click="closeFotoKiosModal">Batal</button>
        <button 
          class="modal-btn primary" 
          @click="saveFotoKios"
          :disabled="!fotoKiosDraft"
        >
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>
</template>

<style setup>
@import '@/styles/button.css';

.body-wrapper {
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

.marginbttm {
  margin-bottom: 1.8rem;
}

.seccolor {
  color: #000;
}

.header {
  display: flex;
  flex-direction: column;
  gap: .8rem;

  .heading {
    all: unset;
    font-family: 'Press Start 2P', sans-serif;
    font-style: normal;
    width: fit-content;
    font-size: 3rem;
    display: flex;
    align-items: flex-end;
    color: #15A1C8;
    text-shadow: 4px 4px 0px #1E3445;
  }
  .subheading {
    all: unset;
    font-family: 'Pixel Operator', sans-serif;
    font-style: normal;
    font-size: 1.8rem;
  }
}

.upperButtonMenu {
  display: flex;
  justify-content: space-between;
}

.content-container {
  flex: 3 0 0;
  min-width: 340px;
  box-sizing: border-box;
  padding: 1.5rem;
  border: 2px solid var(--container-border-color);
  background: #fff;
}
.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.content-header-text {
  display: flex;
  flex-direction: column;
}
.content-title {
  all: unset;
  font-family: 'Minecraft', sans-serif;
  font-size: 1.6rem;
}
.content-sub {
  all: unset;
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1.2rem;
}
.denah-area {
  width: 100%;
  height: 38dvh;
  border: 2px dashed var(--container-border-color);
  background-color: #f0f0f049;
  display: flex;
  align-items: center;
  justify-content: center;
}
.denah-tip {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  margin-top: 1rem;
  padding: 0.8rem;
  border: 1px solid var(--container-border-color);

  .tip-text {
    display: flex;
    flex-direction: column;
    

    .tip-title {
      all: unset;
      font-family: 'Minecraft', sans-serif;
      font-size: 1rem;
    }
    .tip-desc {
      all: unset;
      font-family: 'Pixel Operator', sans-serif;
      font-size: 1rem;
      opacity: 0.6;
    }
  }
}

.main-layout {
  display: flex;
  gap: 1rem;
  align-items: stretch;
  flex-wrap: wrap;
}
</style>

<style scoped>

.kios-list {
  padding-right: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  height: 100%;
  overflow-y: auto;
}

.kios-item {
  display: flex;
  align-items: center;
  background: #ededed;
  padding: .4rem .4rem;
  box-shadow: 0 0 0 2px #fff, 2px 2px 0 0 #bfcad6;
  border-left: 6px solid #d3d3d3;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  cursor: pointer;
  border-radius: 6px;
  gap: 0.8rem;
  outline: none;
  user-select: none;
}
.kios-item:hover > .kios-info,
.kios-item:focus > .kios-info {
  transform: translateX(4px);
}

.kios-item-aktif {
  border-left: 6px solid #15a1c8;
  background: #d1f3ff;
}

.kios-item-no-data {
  border-left: 6px solid #ff9f9f;
  background: #fff7d3;
}

.kios-img {
  width: 38px;
  height: auto;
  object-fit: cover;
  border-radius: 2px;
}

.kios-info {
  flex: 1;
  width: fit-content;
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
  opacity: 0.6;
}

.kios-id {
  all: unset;
  width: fit-content;
  font-family: 'Minecraft Standard', sans-serif;
  font-size: .6rem;
  font-weight: bold;
  color: #222;
}

.config-layout {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
  align-items: stretch;
  flex-wrap: nowrap;
}

.config-layout > .content-container {
  flex: 1 1 0;
  display: flex;
  flex-direction: column;
}

.config-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.config-title {
  font-family: 'Minecraft', 'Pixel Operator', sans-serif;
  font-size: 1.3rem;
  font-weight: bold;
  margin-bottom: 0.2rem;
}

.config-sub {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  color: #444;
  opacity: 0.8;
}

.config-actions {
  display: flex;
  gap: 0.7rem;
}

.config-btn {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  border: 2px solid #b0bec5;
  background: #fff;
  padding: 0.4rem 1.2rem;
  cursor: pointer;
  transition: background 0.15s, border 0.15s;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.config-btn.save {
  background: #19a7a7;
  color: #fff;
  border-color: #19a7a7;
}
.config-btn.save:hover {
  background: #16abd5;
  border-color: #16abd5;
}
.config-btn.cancel:hover {
  background: #f8d7da;
  border-color: #e57373;
}
.config-btn.search {
  padding: 0.4rem 0.8rem;
  font-size: 1.1rem;
}

.config-form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.config-row {
  display: flex;
  gap: 1.2rem;
  flex-wrap: wrap;
}

.config-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: .3rem;
}

.config-field label {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  color: #222;
}

.config-field input,
.config-field textarea {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 1rem;
  border: 2px solid #b0bec5;
  padding: 0.5rem 0.7rem;
  background: #fff;
  resize: none;
  outline: none;
  transition: border 0.15s;
}
.config-field input:focus,
.config-field textarea:focus {
  border-color: #19a7a7;
}

.equal-height-layout {
  display: flex;
  gap: 1rem;
  align-items: stretch;
  flex-wrap: nowrap;
  /* Set a fixed height for desktop view */
  height: 480px;
  /* You can adjust 480px as needed for your design */
}

.equal-height-item {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 340px;
  /* Ensures all items have the same max-height as their siblings */
  max-height: 100%;
  box-sizing: border-box;
  overflow: auto;
}

/* Optional: Hide scrollbars for a cleaner look */
.equal-height-item::-webkit-scrollbar {
  width: 8px;
  background: #f0f0f0;
}
.equal-height-item::-webkit-scrollbar-thumb {
  background: #d1f3ff;
}

/* Styling untuk form konfigurasi pengguna */
.password-input-container {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.password-input-container input {
  padding-right: 3.4rem;
}

.password-toggle-btn {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  font-family: 'Pixel Operator', sans-serif;
  font-size: 0.9rem;
  border: 2px solid #b0bec5;
  background: #fff;
  padding: 0.3rem 0.6rem;
  cursor: pointer;
  transition: background 0.15s, border 0.15s;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  height: 100%;
}

.password-toggle-btn:hover {
  background: #f0f0f0;
  border-color: #19a7a7;
}

.password-toggle-btn img {
  opacity: 0.8;
  transition: opacity 0.2s;
  width: 14px;
  height: 14px;
}

.password-toggle-btn:hover img {
  opacity: 1;
}

.error-message {
  color: #e57373;
  font-size: 0.85rem;
  font-family: 'Pixel Operator', sans-serif;
  margin-top: 0.2rem;
  display: block;
}

/* Delete section styling */
.delete-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 3px dashed #ffa0aa;

  span {
    font-family: 'Minecraft', 'sans-serif';
  } 
}

.delete-section-flex {
  display: flex;
  justify-content: space-around;
  gap: .8rem;

  input {
    width: 100%;  
  }

  .text {
    margin: .4rem;
  }
}

.delete-section .config-field {
  margin-bottom: 1rem;
}

.delete-section .config-field label {
  color: #d32f2f;
  font-weight: bold;
}

.config-field input.error,
.config-field textarea.error {
  border-color: #e57373;
  background-color: #fff5f5;
}

.config-field input.error:focus,
.config-field textarea.error:focus {
  border-color: #e57373;
  box-shadow: 0 0 0 2px rgba(229, 115, 115, 0.2);
}

/* Styling untuk file upload */
.file-upload-container {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-input {
  border: 2px dashed #b0bec5;
  background: #f8f9fa;
  padding: 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s;
}

.file-input:hover {
  border-color: #19a7a7;
  background: #f0f8ff;
}

.file-input:focus {
  outline: none;
  border-color: #19a7a7;
  box-shadow: 0 0 0 2px rgba(25, 167, 167, 0.2);
}

.file-upload-info {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 0.85rem;
  color: #666;
  text-align: center;
  padding: 0.3rem;
  background: #f8f9fa;
  border-radius: 4px;
  border: 1px solid #e9ecef;
}

/* Styling untuk status sinkronisasi */
.no-selection {
  color: #666;
  font-style: italic;
  opacity: 0.7;
}

/* Styling untuk kios yang dipilih */
.kios-item-aktif {
  border-left: 6px solid #15a1c8 !important;
  background-color: rgba(21, 161, 200, 0.1);
  box-shadow: 0 2px 8px rgba(21, 161, 200, 0.2);
}

/* Efek hover yang lebih jelas untuk kios yang dipilih */
.kios-item-aktif:hover {
  background-color: rgba(21, 161, 200, 0.15);
  transform: translateX(3px);
}

/* Denah area styling */
.denah-area {
  position: relative;
  border: 2px solid var(--container-border-color);
  border-radius: 8px;
  background: #f9f9f9;
  overflow: hidden;
  height: 400px;
}

.denah-controls {
  position: absolute;
  top: 10px;
  right: 10px;
  display: flex;
  gap: 0.5rem;
  z-index: 10;
}

.zoom-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #15a1c8;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: 'Pixel Operator', sans-serif;
}

.zoom-btn:hover {
  background: #15a1c8;
  transform: translateY(-1px);
}

.zoom-btn:hover img {
  filter: brightness(0) invert(1);
}

.denah-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  cursor: grab;
}

.denah-container.dragging {
  cursor: grabbing;
}

/* Modal styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  font-family: 'Minecraft', sans-serif;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s;
}

.modal-close:hover {
  background: #f8f9fa;
}

.modal-body {
  padding: 2rem;
  height: 100%;
  overflow-y: auto;
}

.upload-area {
  border: 2px dashed #15a1c8;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #f8fbff;
}

.upload-area:hover {
  border-color: #0d7a9c;
  background: #e6f3ff;
}

.upload-area img {
  opacity: 0.6;
  margin-bottom: 1rem;
}

.upload-area p {
  font-family: 'Minecraft', sans-serif;
  font-size: 1rem;
  margin: 0.5rem 0;
  color: #333;
}

.file-info {
  font-family: 'Pixel Operator', sans-serif;
  font-size: 0.9rem;
  color: #666;
}

.selected-file {
  padding: 1rem;
  background: #e6f7ff;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;   
}

.selected-file p {
  margin: 0;
  font-family: 'Pixel Operator', sans-serif;
  color: #333;
}

.remove-file-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: center;
  background: #ff9b9b;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-family: 'Pixel Operator', sans-serif;
  transition: background 0.2s;
}

.remove-file-btn:hover {
  background: #ff7272;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
}

.modal-btn {
  padding: 0.75rem 1.5rem;
  border: 2px solid;
  border-radius: 6px;
  cursor: pointer;
  font-family: 'Minecraft', sans-serif;
  font-size: 0.9rem;
  transition: all 0.2s ease;
}

.modal-btn.secondary {
  background: white;
  border-color: #6c757d;
  color: #6c757d;
}

.modal-btn.secondary:hover {
  background: #6c757d;
  color: white;
}

.modal-btn.primary {
  background: #15a1c8;
  border-color: #15a1c8;
  color: white;
}

.modal-btn.primary:hover:not(:disabled) {
  background: #0d7a9c;
  border-color: #0d7a9c;
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.image-preview-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}
.image-preview-container {
  position: relative;
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.image-preview {
  width: 96px;
  height: 96px;
  object-fit: cover;
  border: 2.5px solid #222;
  box-shadow: 0 0 0 2px #fff, 4px 4px 0 0 #1E3445;
  background: #f8f8f8;
  image-rendering: pixelated;
  border-radius: 6px;
  transition: border-color 0.15s;
}
.image-preview-container:hover .image-preview {
  border-color: #16abd5;
}
.preview-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.overlay-btn {
  padding: 0.3rem 0.5rem;
  font-family: 'Pixel Operator', sans-serif;
  font-size: .4rem;
  border: 2px solid #b0bec5;
  padding: 0.5rem 0.7rem;
  background: #fff;
  color: #222;
  transition: background 0.15s, border-color 0.15s;
  user-select: none;
}
.overlay-btn:hover {
  border-color: #19a7a7;
}
.file-upload-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

/* Animasi fokus panel */
.hash-highlight {
  transition: box-shadow .3s ease;
  animation: hash-highlight 2.5s infinite;
}
@keyframes hash-highlight {
  0% {
    outline: 3px solid #ffae00;
  }
  100% {
    outline: none;
  }
  50% {
    outline: 3px solid #ffae00;
  }
}
</style>

<script>
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
  }
}
</script>