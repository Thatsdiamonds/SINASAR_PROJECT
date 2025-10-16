# API Changes Documentation

## Overview
Frontend telah dimodifikasi untuk menggunakan **token-based authentication** dimana backend akan menyediakan data user (id, role, dll) saat diperlukan, bukan disimpan di localStorage.

## Perubahan Utama

### 1. localStorage Usage
**Sebelum:**
```javascript
// Menyimpan semua data user di localStorage
localStorage.setItem("token", response.data.token);
localStorage.setItem("role", response.data.role);
localStorage.setItem("user_id", response.data.user.id);
```

**Sesudah:**
```javascript
// Hanya menyimpan token
localStorage.setItem("token", response.data.token);
// Role dan user_id akan diambil dari backend saat diperlukan
```

### 2. Route Protection
**Sebelum:**
```javascript
// Menggunakan role dari localStorage
const role = localStorage.getItem('role')
if (to.meta.role && to.meta.role !== role) {
  // redirect ke login
}
```

**Sesudah:**
```javascript
// Mengambil role dari backend berdasarkan token
const response = await api.get('/user/profile', { silent: true })
const userRole = response.data.role
if (to.meta.role && to.meta.role !== userRole) {
  // redirect ke login
}
```

### 3. API Calls
**Sebelum:**
```javascript
// Mengirim user_id dari localStorage
const user_id = localStorage.getItem("user_id");
const response = await api.post(`/update-denah/`, formData, {
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
});
```

**Sesudah:**
```javascript
// Backend akan mengidentifikasi user dari token
const response = await api.post(`/update-denah/`, formData, {
  headers: {
    'Content-Type': 'multipart/form-data'
  }
});
```

## Utility Functions

### getUserProfile()
Mengambil semua data user dari backend:
```javascript
import { getUserProfile } from '@/services/api'

const userProfile = await getUserProfile()
console.log(userProfile) // { id, username, email, role, ... }
```

### getUserRole()
Mengambil role user dari backend:
```javascript
import { getUserRole } from '@/services/api'

const userRole = await getUserRole()
console.log(userRole) // 'admin' atau 'seller'
```

### getUserId()
Mengambil ID user dari backend:
```javascript
import { getUserId } from '@/services/api'

const userId = await getUserId()
console.log(userId) // user ID
```

## Backend Requirements

Backend harus menyediakan endpoint berikut:

### GET /api/user/profile
**Response:**
```json
{
  "id": 1,
  "username": "admin",
  "email": "admin@example.com",
  "role": "admin",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### Authentication
- Semua request harus menyertakan `Authorization: Bearer <token>` header
- Backend harus memvalidasi token dan mengembalikan data user yang sesuai
- Jika token invalid, kembalikan status 401

## Keuntungan

1. **Security**: Data user tidak disimpan di client-side
2. **Consistency**: Data selalu up-to-date dari backend
3. **Flexibility**: Backend dapat mengubah data user tanpa perlu logout
4. **Scalability**: Mudah untuk menambah field user baru

## Migration Guide

### Untuk Component Baru:
```javascript
import { getUserProfile, getUserRole, getUserId } from '@/services/api'

export default {
  setup() {
    const userProfile = ref(null)
    
    const loadUserData = async () => {
      try {
        userProfile.value = await getUserProfile()
      } catch (error) {
        console.error('Failed to load user data:', error)
      }
    }
    
    onMounted(loadUserData)
    
    return { userProfile }
  }
}
```

### Untuk API Calls:
```javascript
// Tidak perlu lagi mengirim user_id manual
const response = await api.post('/some-endpoint', data)
// Backend akan mengidentifikasi user dari token
```

## Error Handling

Jika terjadi error 401 (Unauthorized):
- Token akan dihapus dari localStorage
- User akan di-redirect ke halaman login
- Toast error akan ditampilkan

## Notes

- Semua utility functions menggunakan `{ silent: true }` untuk menghindari toast error otomatis
- Router guard akan mengecek token dan role untuk setiap route yang memerlukan authentication
- Backend harus memastikan endpoint `/user/profile` tersedia dan berfungsi dengan benar
