<template>
    <div class="body">
  <div class="bsod-container">
    <div class="sad-face">:(</div>

    <div class="error-title">
      Your PC ran into a problem and needs to restart. We're just collecting some error info, and then we'll restart for you.
    </div>

    <div class="progress-container">
      <div class="percentage"><span>{{ Math.floor(progress) }}</span>% complete</div>
    </div>

    <div class="details">
      For more information about this issue and possible fixes, visit
      <a href="https://www.windows.com/stopcode" target="_blank" class="underline">windows.com/stopcode</a>
      <div class="stop-code">
        <br />
        If you call a support person, give them this info:<br />
        Stop code: <span class="code-value">CRITICAL_PROCESS_DIED</span>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";

const progress = ref(0);
const router = useRouter();

onMounted(() => {
  const interval = setInterval(() => {
    if (progress.value < 100) {
      progress.value += Math.random() * 2 + 7;
      if (progress.value > 100) progress.value = 100;
    } else {
      clearInterval(interval);
      setTimeout(() => {
        router.go(-1);
      }, 2000);
    }
  }, Math.random() * 50 + 500);

  document.addEventListener("contextmenu", (e) => e.preventDefault());
  document.addEventListener("keydown", (e) => {
    if (e.ctrlKey || e.altKey || e.metaKey) e.preventDefault();
  });
});
</script>

<style scoped>
.body {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  background: #0078d7;
  color: #ffffff;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  overflow: hidden;
}

.bsod-container {
  max-width: 800px;
  padding: 40px 80px;
  text-align: left;
  animation: fadeIn 0.1s ease-in;
}

.sad-face {
  font-size: 120px;
  font-weight: 300;
  margin-bottom: 30px;
  line-height: 1;
}

.error-title {
  font-size: 28px;
  font-weight: 300;
  margin-bottom: 40px;
  letter-spacing: 0.5px;
}

.error-message {
  font-size: 18px;
  font-weight: 300;
  line-height: 1.6;
  margin-bottom: 30px;
}

.progress-container {
  margin: 40px 0;
}

.percentage {
  font-size: 18px;
  font-weight: 300;
  margin-top: 5px;
}

.details {
  margin-top: 50px;
  font-size: 15px;
  font-weight: 300;
  line-height: 1.8;
}

.stop-code {
  margin-top: 10px;
}

.code-value {
  text-transform: uppercase;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
