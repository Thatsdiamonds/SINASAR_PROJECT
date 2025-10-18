<template>
  <div class="spinner-wrapper" :class="layout">
    <img
      :src="spinnerSrc"
      :class="['spinner', color]"
      alt="Loading..."
    />

    <!-- Message optional -->
    <span
      v-if="message"
      :class="['message_display', color]"
    >
      {{ message }}
    </span>
  </div>
</template>

<script setup>
import spinner from '/icons/spinner_white.svg'

defineProps({
  color: {
    type: String,
    default: 'white', // "white" | "black"
  },
  size: {
    type: String,
    default: '24px',
  },
  message: {
    type: String,
    default: '', // kosongin default biar gak muncul terus
  },
  layout: {
    type: String,
    default: 'horizontal', // "horizontal" | "vertical"
  },
})

// lo bisa ubah nanti kalau punya versi lain dari spinner
const spinnerSrc = spinner
</script>

<style scoped>
@font-face {
  font-family: 'Minecraft Standard';
  src: url('@/fonts/MinecraftStandard.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
}

.spinner-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spinner-wrapper.vertical {
  flex-direction: column;
}

.spinner {
  width: v-bind(size);
  height: v-bind(size);
  animation: spin 1s linear infinite;
  transition: filter 0.3s ease;
}

/* warna spinner */
.spinner.white {
  filter: brightness(1) invert(0);
}
.spinner.black {
  filter: brightness(0);
}

/* teks */
.message_display {
  font-family: "Minecraft Medium";
  transform: translateY(1px);
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.5px;
}
.message_display.white {
  color: white;
}
.message_display.black {
  color: black;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
