<!-- For halaman login, ror gabut ni orang ancen -->
<template>
  <button :style="buttonStyle"
    :class="['app-btn', type, { loading, success, error }]"
    :disabled="loading"
    @click="$emit('click')"
  >
    <!-- State: Loading -->
    <div v-if="loading" class="loading-container">
      <Spinner
        :color="spinnerColor"
        :size="spinnerSize"
        :message="loadingText"
        layout="horizontal"
      />
    </div>

    <!-- State: Success -->
    <span v-else-if="success">{{ successText }}</span>

    <!-- State: Error -->
    <span v-else-if="error">{{ errorText }}</span>

    <!-- State: Default -->
    <span v-else>{{ label }}</span>
  </button>
</template>

<script setup>
import Spinner from './Spinner.vue'
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, default: 'Simpan' },
  loading: { type: Boolean, default: false },
  success: { type: Boolean, default: false },
  error: { type: Boolean, default: false },

  type: {
    type: String,
    default: 'primary', // primary | secondary | danger | etc.
  },
  spinnerColor: {
    type: String,
    default: 'white',
  },
  spinnerSize: {
    type: String,
    default: '20px',
  },
  loadingText: {
    type: String,
    default: 'Memproses...', 
  },
  successText: {
    type: String,
    default: 'Tersimpan ✅',
  },
  errorText: {
    type: String,
    default: 'Gagal ❌',
  },
  fontFamily: {
    type: String,
    default: 'Minecraft Standard',
  },
});

const buttonStyle = computed(() => {
  return { '--button-font-family': props.fontFamily };
});
</script>

<style scoped>
@font-face {
  font-family: 'Minecraft Medium';
  src: url('@/fonts/minecraft.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
}
.app-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: var(--button-font-family, "Minecraft Standard", sans-serif);
  border: none;
  border-radius: 10px;
  padding: 10px 20px;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 120px;

}
span {
  font-family: "Minecraft Medium";
  transform: translateY(+2px);
}

/* Variasi style */
.app-btn.primary {
  background: #024196;
  color: white;
}
.app-btn.buttonSubmit {
  background: #247CFF;
  color: white;

  &:hover {
    background: #438fff;
  }
}
.app-btn.buttonSubmit2 {
  background: #33C3F0;
  color: white;

  &:hover {
    background: #5ccfff;
  }
}
.app-btn.primary:hover {
  background: #187bcd;
}

.app-btn.secondary {
  background: #e6e6e6;
  color: #222;
}
.app-btn.secondary:hover {
  background: #d9d9d9;
}
</style>
