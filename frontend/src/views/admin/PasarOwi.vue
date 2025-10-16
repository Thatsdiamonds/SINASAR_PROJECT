<template>
  <div style="padding: 1rem" ref="svgContainer" @click="$emit('click', $event)"></div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import api, { apiBase } from "@/services/api";

const props = defineProps({
  hoveredRect: String,
  selectedKios: String, // Kios yang dipilih dari pencarian
  isSearchMode: Boolean, // Mode pencarian aktif
  occupiedKiosIds: { type: Array, default: () => [] } // Daftar kios terpakai dari API
});

const svgContent = ref("");
const svgContainer = ref(null);

// State untuk marked-down
const markedStates = {
  NONE: 'none',
  HOVER: 'hover',
  SELECTED: 'selected'
};

onMounted(async () => {
  const res = await api.get('/denah');
  svgContent.value = res.data.svg; // Use SVG string directly from backend
  console.log(res.data);
  await nextTick();
  renderSvg();
});

watch(svgContent, async () => {
  await nextTick();
  renderSvg();
});

watch(() => props.hoveredRect, () => {
  updateRectStates();
});

watch(() => props.selectedKios, () => {
  updateRectStates();
  if (props.selectedKios && props.isSearchMode) {
    centerOnKios(props.selectedKios);
  }
});

watch(() => props.isSearchMode, () => {
  updateRectStates();
});

watch(() => props.occupiedKiosIds, () => {
  updateRectStates();
});

function renderSvg() {
  if (svgContainer.value) {
    svgContainer.value.innerHTML = svgContent.value;
    const rects = svgContainer.value.querySelectorAll("rect");
    rects.forEach(rect => {
      rect.addEventListener("mouseover", () => {
        emitRectHover(rect.id);
      });
      rect.addEventListener("mouseout", () => {
        emitRectOut();
      });
      rect.style.transition = "all 0.2s ease";
      rect.style.cursor = "pointer";
      
      // Tambahkan class untuk styling
      rect.classList.add('kios-rect');
    });
    updateRectStates();
    addKiosTextLabelsToSvg(); // Tambahkan label text di dalam SVG
  }
}

// Fungsi untuk menambahkan <text> label dengan style minimal
function addKiosTextLabelsToSvg() {
  if (!svgContainer.value) return;
  const svg = svgContainer.value.querySelector('svg');
  if (!svg) return;
  
  // Hapus label lama jika ada
  svg.querySelectorAll('.kios-svg-label').forEach(e => e.remove());

  // Loop semua rect kios (id mulai L)
  const rects = svg.querySelectorAll('rect[id^="L"]');
  rects.forEach((rect) => {
    const id = rect.id;
    const x = parseFloat(rect.getAttribute('x'));
    const y = parseFloat(rect.getAttribute('y'));
    const width = parseFloat(rect.getAttribute('width'));
    const height = parseFloat(rect.getAttribute('height'));
    
    const label = id;
    const svgns = "http://www.w3.org/2000/svg";
    
    // Default position: di atas kios
    const defaultX = x + width / 2;
    const defaultY = y - 8;
    
    // Hover position: di tengah kios
    const hoverX = x + width / 2;
    const hoverY = y + height / 2;
    
    // Buat elemen <text> minimal
    const text = document.createElementNS(svgns, 'text');
    text.setAttribute('x', defaultX);
    text.setAttribute('y', defaultY);
    text.setAttribute('text-anchor', 'middle');
    text.setAttribute('dominant-baseline', 'middle');
    text.setAttribute('class', 'kios-svg-label');
    text.setAttribute('font-size', '11');
    text.setAttribute('font-family', 'Arial, sans-serif');
    text.setAttribute('font-weight', 'bold');
    text.setAttribute('fill', '#333');
    text.setAttribute('stroke', 'white');
    text.setAttribute('stroke-width', '2');
    text.setAttribute('paint-order', 'stroke');
    text.textContent = label;
    
    // Simpan posisi default dan hover untuk transisi
    text.setAttribute('data-default-x', defaultX);
    text.setAttribute('data-default-y', defaultY);
    text.setAttribute('data-hover-x', hoverX);
    text.setAttribute('data-hover-y', hoverY);
    text.setAttribute('data-kios-id', id);
    
    // PENTING: Non-interactable
    text.style.pointerEvents = 'none';
    text.style.userSelect = 'none';
    
    svg.appendChild(text);
  });
}

function updateRectStates() {
  if (!svgContainer.value) return;
  
  const rects = svgContainer.value.querySelectorAll("rect");
  const occupiedSet = new Set((props.occupiedKiosIds || []).filter(Boolean));
  rects.forEach(rect => {
    // Reset semua state
    rect.classList.remove('hover-state', 'selected-state', 'occupied-state');
    rect.style.transform = "";
    rect.style.filter = "";
    rect.style.stroke = "";
    rect.style.strokeWidth = "";
    // Reset fill hanya jika bukan terpakai (biarkan SVG asli menentukan warna default)
    // Catatan: jangan reset fill di sini agar warna asli SVG tetap ada saat tidak occupied
    
    // Apply state berdasarkan kondisi
    if (occupiedSet.has(rect.id)) {
      // Tandai kios terpakai
      applyOccupiedState(rect);
    }
    if (rect.id === props.selectedKios && props.isSearchMode) {
      // State SELECTED - Kios yang dipilih dari pencarian
      applySelectedState(rect);
    } else if (rect.id === props.hoveredRect) {
      // State HOVER - Kios yang sedang di-hover
      applyHoverState(rect);
    }
  });
  
  // Update label states
  updateLabelStates();
}

function updateLabelStates() {
  if (!svgContainer.value) return;
  
  const svg = svgContainer.value.querySelector('svg');
  if (!svg) return;
  
  const labels = svg.querySelectorAll('.kios-svg-label');
  
  labels.forEach((label) => {
    const kiosId = label.getAttribute('data-kios-id');
    const defaultX = label.getAttribute('data-default-x');
    const defaultY = label.getAttribute('data-default-y');
    const hoverX = label.getAttribute('data-hover-x');
    const hoverY = label.getAttribute('data-hover-y');
    
    if (kiosId === props.selectedKios && props.isSearchMode) {
      // Selected state: warna merah, posisi tengah kios
      label.setAttribute('fill', '#e53e3e');
      label.setAttribute('x', hoverX);
      label.setAttribute('y', hoverY);
      label.setAttribute('font-size', '12');
      label.setAttribute('stroke', 'white');
      label.setAttribute('stroke-width', '3');
    } else if (kiosId === props.hoveredRect) {
      // Hover state: posisi pindah ke tengah kios
      label.setAttribute('fill', '#2563eb');
      label.setAttribute('x', hoverX);
      label.setAttribute('y', hoverY);
      label.setAttribute('font-size', '11');
      label.setAttribute('stroke', 'white');
      label.setAttribute('stroke-width', '2');
    } else {
      // Default state: posisi di atas kios
      label.setAttribute('fill', '#333');
      label.setAttribute('x', defaultX);
      label.setAttribute('y', defaultY);
      label.setAttribute('font-size', '11');
      label.setAttribute('stroke', 'white');
      label.setAttribute('stroke-width', '2');
    }
  });
}

function applyHoverState(rect) {
  rect.classList.add('hover-state');
  rect.style.filter = "brightness(1.1)";
  rect.style.stroke = "#15A1C8";
  rect.style.strokeWidth = "2";
}

function applySelectedState(rect) {
  rect.classList.add('selected-state');
  rect.style.filter = "brightness(1.2)";
  rect.style.stroke = "#FF6B35";
  rect.style.strokeWidth = "3";
  
  // Tambahkan efek glow
  rect.style.boxShadow = "0 0 10px rgba(255, 107, 53, 0.6)";
}

function applyOccupiedState(rect) {
  rect.classList.add('occupied-state');
  // Warna isi lembut untuk menandai terpakai tanpa mengganggu hover/selected
  rect.style.fill = '#cfffd9';
  rect.style.opacity = '1';
  // Tambah stroke halus agar terlihat jelas saat tidak di-hover
  if (!rect.classList.contains('hover-state') && !rect.classList.contains('selected-state')) {
    rect.style.stroke = '#d7fade';
    rect.style.strokeWidth = '1.5';
  }
  // Tooltip sederhana
  rect.setAttribute('data-occupied', 'true');
  rect.setAttribute('title', `${rect.id} (Terpakai)`);
}

// Advanced kios centering algorithm with performance optimizations and error handling
function centerOnKios(kiosId, options = {}) {
  // Default configuration
  const config = {
    animationDuration: 300,
    easing: 'ease-out',
    padding: 20,
    zoomToFit: false,
    maxZoom: 3,
    minZoom: 0.1,
    ...options
  };

  // Early validation
  if (!svgContainer.value) {
    console.warn('SVG container not available');
    return Promise.reject(new Error('Container not found'));
  }

  if (!kiosId) {
    console.warn('Kios ID is required');
    return Promise.reject(new Error('Invalid kios ID'));
  }

  // Use more efficient selector and add error handling
  const targetElement = svgContainer.value.querySelector(`#${CSS.escape(kiosId)}`);
  if (!targetElement) {
    console.warn(`Kios element with ID "${kiosId}" not found`);
    return Promise.reject(new Error('Target element not found'));
  }

  try {
    // Get container dimensions
    const container = svgContainer.value;
    const containerRect = container.getBoundingClientRect();
    
    // Handle edge case where container has no dimensions
    if (containerRect.width === 0 || containerRect.height === 0) {
      console.warn('Container has zero dimensions');
      return Promise.reject(new Error('Invalid container dimensions'));
    }

    // Get target element bounds with better precision
    const targetBounds = targetElement.getBoundingClientRect();
    
    // Calculate actual centers (fixing the original math)
    const containerCenterX = containerRect.width / 2;
    const containerCenterY = containerRect.height / 2;
    
    // Calculate target center relative to container
    const targetCenterX = (targetBounds.left - containerRect.left) + (targetBounds.width / 2);
    const targetCenterY = (targetBounds.top - containerRect.top) + (targetBounds.height / 2);
    
    // Calculate required offset to center the target
    const offsetX = containerCenterX - targetCenterX;
    const offsetY = containerCenterY - targetCenterY;

    // Advanced: Calculate optimal zoom if requested
    let optimalZoom = 1;
    if (config.zoomToFit && targetBounds.width > 0 && targetBounds.height > 0) {
      const scaleX = (containerRect.width - config.padding * 2) / targetBounds.width;
      const scaleY = (containerRect.height - config.padding * 2) / targetBounds.height;
      optimalZoom = Math.min(scaleX, scaleY, config.maxZoom);
      optimalZoom = Math.max(optimalZoom, config.minZoom);
    }

    // Get current transform for smooth transitions
    const currentTransform = getCurrentTransform(container);

    // Prepare comprehensive transformation data
    const transformData = {
      kiosId,
      
      // Position data
      offsetX,
      offsetY,
      
      // Bounds information
      targetBounds: {
        x: targetBounds.left,
        y: targetBounds.top,
        width: targetBounds.width,
        height: targetBounds.height,
        centerX: targetCenterX,
        centerY: targetCenterY
      },
      
      containerBounds: {
        width: containerRect.width,
        height: containerRect.height,
        centerX: containerCenterX,
        centerY: containerCenterY
      },
      
      // Transform data
      currentTransform,
      optimalZoom,
      
      // Animation config
      animation: {
        duration: config.animationDuration,
        easing: config.easing
      },
      
      // Metadata
      timestamp: Date.now(),
      element: targetElement
    };

    // Emit event with comprehensive data
    emit("center-on-kios", transformData);

    // Return promise for chaining
    return Promise.resolve(transformData);

  } catch (error) {
    console.error('Error in centerOnKios:', error);
    return Promise.reject(error);
  }
}

// Helper function to get current transform state
function getCurrentTransform(element) {
  const style = window.getComputedStyle(element);
  const matrix = style.transform;
  
  if (matrix === 'none') {
    return { x: 0, y: 0, scale: 1, rotation: 0 };
  }
  
  // Parse matrix for current transform values
  const values = matrix.match(/matrix.*\((.+)\)/);
  if (values) {
    const parts = values[1].split(', ').map(parseFloat);
    if (parts.length === 6) {
      return {
        x: parts[4] || 0,
        y: parts[5] || 0,
        scale: Math.sqrt(parts[0] * parts[0] + parts[1] * parts[1]),
        rotation: Math.atan2(parts[1], parts[0]) * (180 / Math.PI)
      };
    }
  }
  
  return { x: 0, y: 0, scale: 1, rotation: 0 };
}

// Advanced version with viewport-aware centering
function centerOnKiosViewportAware(kiosId, options = {}) {
  const config = {
    respectViewportBounds: true,
    smoothTransition: true,
    debugMode: false,
    ...options
  };

  return centerOnKios(kiosId, config).then(transformData => {
    if (config.respectViewportBounds) {
      // Ensure the centered element doesn't go outside viewport bounds
      const adjustedTransform = constrainToViewport(transformData);
      
      if (config.debugMode) {
        console.log('Original transform:', transformData);
        console.log('Adjusted transform:', adjustedTransform);
      }
      
      return adjustedTransform;
    }
    
    return transformData;
  });
}

// Helper to constrain centering within viewport bounds
function constrainToViewport(transformData) {
  const { containerBounds, targetBounds, offsetX, offsetY } = transformData;
  
  // Calculate bounds after transform
  const newTargetX = targetBounds.centerX + offsetX;
  const newTargetY = targetBounds.centerY + offsetY;
  
  // Constrain to viewport
  const constrainedX = Math.max(
    targetBounds.width / 2,
    Math.min(containerBounds.width - targetBounds.width / 2, newTargetX)
  );
  
  const constrainedY = Math.max(
    targetBounds.height / 2,
    Math.min(containerBounds.height - targetBounds.height / 2, newTargetY)
  );
  
  // Recalculate offsets with constraints
  const adjustedOffsetX = constrainedX - targetBounds.centerX;
  const adjustedOffsetY = constrainedY - targetBounds.centerY;
  
  return {
    ...transformData,
    offsetX: adjustedOffsetX,
    offsetY: adjustedOffsetY,
    constrained: true,
    originalOffset: { x: offsetX, y: offsetY }
  };
}

// Utility function for batch centering operations
async function centerOnMultipleKios(kiosIds, options = {}) {
  const results = await Promise.allSettled(
    kiosIds.map(id => centerOnKios(id, options))
  );
  
  return {
    successful: results.filter(r => r.status === 'fulfilled').map(r => r.value),
    failed: results.filter(r => r.status === 'rejected').map(r => r.reason),
    total: kiosIds.length
  };
}

function emitRectHover(id) {
  emit("rect-hover", id);
}

function emitRectOut() {
  emit("rect-out");
}

const emit = defineEmits(["rect-hover", "rect-out", "click", "center-on-kios"]);

// Fungsi untuk soft refresh denah dari luar
async function reloadSvg() {
  const res = await api.get('/denah');
  svgContent.value = res.data.svg;
  await nextTick();
  renderSvg();
}

defineExpose({ reloadSvg });
</script>