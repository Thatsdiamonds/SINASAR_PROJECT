# Pasar Owi - Frontend Application

## Fitur Denah Interaktif

### Sistem State Marked-Down
Aplikasi ini memiliki sistem denah interaktif dengan tiga state marked-down:

1. **NONE** - State default tanpa highlight
2. **HOVER** - State saat mouse hover di atas kios (biru dengan scale 1.02)
3. **SELECTED** - State saat kios dipilih dari pencarian (orange dengan scale 1.05 dan glow effect)

### Fitur Centering Otomatis
- Saat pengguna memilih kios tertentu dari pencarian (contoh: lokasi L001)
- Sistem otomatis menandai (marked-down) kios tersebut pada denah
- Posisi kios yang dipilih akan berada di tengah layar secara otomatis
- Tidak ada animasi untuk performa yang lebih baik

### Cara Penggunaan
1. Buka panel pencarian dengan tombol "Cari kios..." atau tekan "/"
2. Ketik nama atau lokasi kios yang ingin dicari
3. Klik pada kios dari hasil pencarian
4. Sistem akan otomatis:
   - Menampilkan detail kios
   - Menandai kios pada denah
   - Memindahkan view ke tengah kios yang dipilih

### Komponen yang Terlibat
- `PasarOwi.vue` - Komponen SVG denah dengan state management
- `index.vue` - Halaman utama dengan integrasi pencarian dan centering

## Teknologi
- Vue 3 Composition API
- SVG manipulation
- Responsive design
- State management dengan reactive refs
