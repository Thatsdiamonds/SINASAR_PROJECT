<template>
  <div style="padding: 1rem" ref="svgContainer" @click="$emit('click', $event)"></div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import api from "@/services/api";

const props = defineProps({
  hoveredRect: String,
  isSearchMode: Boolean,
  selectedKios: String,
  occupiedKiosIds: { type: Array, default: () => [] },
  kiosImageMap: { type: Object, default: () => ({}) }
});

const svgContent = ref("");
const svgContainer = ref(null);
const emit = defineEmits(["rect-hover", "rect-out", "click", "center-on-kios"]);

onMounted(async () => {
  const res = await api.get('/denah');
  svgContent.value = res.data.svg;
  await nextTick();
  renderSvg();
});

watch(svgContent, async () => {
  await nextTick();
  renderSvg();
});

watch(() => props.hoveredRect, () => {
  updateRectStates();
  updateLabelStates(); // 🟢 update posisi label hover
});

watch(() => props.selectedKios, async () => {
  updateRectStates();
  updateLabelStates(); // 🟢 update posisi label selected
  if (props.selectedKios && props.isSearchMode) {
    centerOnKios(props.selectedKios);
  }
});

watch(() => props.isSearchMode, () => {
  updateRectStates();
  updateLabelStates();
});

watch(() => props.occupiedKiosIds, updateRectStates);
watch(() => props.kiosImageMap, updateRectStates);

// === Ambil data pemilik kios dari API ===
async function getKiosOwnersMap() {
  try {
    const res = await api.get('/penjual');
    const map = {};
    res.data.forEach(item => {
      if (item.lokasi && item.user?.username) {
        map[item.lokasi] = item.user.username;
      }
    });
    return map;
  } catch (err) {
    console.error("Gagal memuat data pemilik kios:", err);
    return {};
  }
}

// === Render utama ===
async function renderSvg() {
  if (!svgContainer.value) return;
  svgContainer.value.innerHTML = svgContent.value;

  const rects = svgContainer.value.querySelectorAll("rect");
  rects.forEach(rect => {
    rect.addEventListener("mouseover", () => emit("rect-hover", rect.id));
    rect.addEventListener("mouseout", () => emit("rect-out"));
    rect.addEventListener("click", () => emit("click", rect.id)); // 🟢 tangkap klik kios
    rect.style.transition = "all 0.2s ease";
    rect.style.cursor = "pointer";
    rect.classList.add('kios-rect');
  });

  updateRectStates();
  await addKiosTextLabelsToSvg();
  updateLabelStates(); // 🟢 update posisi label awal
}

// === Tambahkan dua baris label: ID + username ===
async function addKiosTextLabelsToSvg() {
  if (!svgContainer.value) return;
  const svg = svgContainer.value.querySelector('svg');
  if (!svg) return;

  svg.querySelectorAll('.kios-svg-label-group').forEach(e => e.remove());

  const kiosOwnerMap = await getKiosOwnersMap();

  const rects = svg.querySelectorAll('rect[id^="L"]');
  rects.forEach((rect) => {
    const id = rect.id;
    const username = kiosOwnerMap[id] || "(Kosong)";

    const x = parseFloat(rect.getAttribute('x'));
    const y = parseFloat(rect.getAttribute('y'));
    const width = parseFloat(rect.getAttribute('width'));
    const height = parseFloat(rect.getAttribute('height'));
    const centerX = x + width / 2;
    const topY = y - 8;

    const svgns = "http://www.w3.org/2000/svg";
    const g = document.createElementNS(svgns, 'g');
    g.setAttribute('class', 'kios-svg-label-group');
    g.setAttribute('data-kios-id', id);
    g.style.pointerEvents = 'none';
    g.style.userSelect = 'none';

    const textId = document.createElementNS(svgns, 'text');
    textId.setAttribute('x', centerX);
    textId.setAttribute('y', topY);
    textId.setAttribute('text-anchor', 'middle');
    textId.setAttribute('font-size', '11');
    textId.setAttribute('font-family', 'Arial, sans-serif');
    textId.setAttribute('font-weight', 'bold');
    textId.setAttribute('fill', '#333');
    textId.setAttribute('stroke', 'white');
    textId.setAttribute('stroke-width', '2');
    textId.setAttribute('paint-order', 'stroke');
    textId.textContent = id;

    const textUser = document.createElementNS(svgns, 'text');
    textUser.setAttribute('x', centerX);
    textUser.setAttribute('y', topY + 14);
    textUser.setAttribute('text-anchor', 'middle');
    textUser.setAttribute('font-size', '10');
    textUser.setAttribute('font-family', 'Arial, sans-serif');
    textUser.setAttribute('fill', username === "(Kosong)" ? '#999' : '#2563eb');
    textUser.setAttribute('stroke', 'white');
    textUser.setAttribute('stroke-width', '2');
    textUser.setAttribute('paint-order', 'stroke');
    textUser.textContent = username;

    // 🟢 simpan posisi untuk animasi hover/selected
    textId.setAttribute('data-default-x', centerX);
    textId.setAttribute('data-default-y', topY);
    textId.setAttribute('data-hover-x', centerX);
    textId.setAttribute('data-hover-y', y + height / 2);

    textUser.setAttribute('data-default-x', centerX);
    textUser.setAttribute('data-default-y', topY + 14);
    textUser.setAttribute('data-hover-x', centerX);
    textUser.setAttribute('data-hover-y', y + height / 2 + 14);

    g.appendChild(textId);
    g.appendChild(textUser);
    svg.appendChild(g);
  });
}

// 🟢 === Update posisi dan warna label (hover/selected animation) ===
function updateLabelStates() {
  if (!svgContainer.value) return;
  const svg = svgContainer.value.querySelector('svg');
  if (!svg) return;

  const groups = svg.querySelectorAll('.kios-svg-label-group');
  groups.forEach((g) => {
    const kiosId = g.getAttribute('data-kios-id');
    const [textId, textUser] = g.querySelectorAll('text');

    const isHovered = kiosId === props.hoveredRect;
    const isSelected = kiosId === props.selectedKios && props.isSearchMode;

    // Ambil posisi awal dan hover
    const defY = textId.getAttribute('data-default-y');
    const hovY = textId.getAttribute('data-hover-y');

    if (isSelected) {
      textId.setAttribute('y', hovY);
      textId.setAttribute('fill', '#e53e3e');
      textId.setAttribute('font-size', '12');
      textUser.setAttribute('fill', '#e53e3e');
    } else if (isHovered) {
      textId.setAttribute('y', hovY);
      textId.setAttribute('fill', '#2563eb');
      textUser.setAttribute('fill', '#2563eb');
    } else {
      textId.setAttribute('y', defY);
      textId.setAttribute('fill', '#333');
      textUser.setAttribute('fill', '#2563eb');
    }

    g.style.transition = "all 0.25s ease";
  });
}

// === State gaya untuk rect ===
function updateRectStates() {
  if (!svgContainer.value) return;

  const rects = svgContainer.value.querySelectorAll("rect");
  const occupiedSet = new Set((props.occupiedKiosIds || []).filter(Boolean));

  rects.forEach(rect => {
    rect.classList.remove('hover-state', 'selected-state', 'occupied-state');
    rect.style.transform = "";
    rect.style.filter = "";
    rect.style.stroke = "";
    rect.style.strokeWidth = "";
    rect.removeAttribute('data-has-image');

    const imageUrl = props.kiosImageMap && props.kiosImageMap[rect.id];
    if (typeof imageUrl === 'string' && imageUrl.trim() !== '') {
      setRectImage(rect, imageUrl);
    }

    if (occupiedSet.has(rect.id)) applyOccupiedState(rect);
    if (rect.id === props.selectedKios && props.isSearchMode) applySelectedState(rect);
    else if (rect.id === props.hoveredRect) applyHoverState(rect);
  });
}

function applyHoverState(rect) {
  rect.classList.add('hover-state');
  rect.style.filter = "brightness(1.1)";
  rect.style.stroke = "#024196";
  rect.style.strokeWidth = "2";
}

function applySelectedState(rect) {
  rect.classList.add('selected-state');
  rect.style.filter = "brightness(1.2)";
  rect.style.stroke = "#FF6B35";
  rect.style.strokeWidth = "3";
}

function applyOccupiedState(rect) {
  rect.classList.add('occupied-state');
  const hasImage = rect.getAttribute('data-has-image') === 'true';
  if (!hasImage) rect.style.fill = '#cfffd9';
  else rect.style.opacity = '0.95';
  rect.style.stroke = '#d7fade';
  rect.style.strokeWidth = '1.5';
  rect.setAttribute('title', `${rect.id} (Terpakai)`);
}

// === Pasang gambar ===
function setRectImage(rect, imageUrl) {
  const svg = rect.ownerSVGElement;
  if (!svg || !imageUrl) return;
  const svgns = "http://www.w3.org/2000/svg";
  const patternId = `pattern-${rect.id}`;

  let pattern = svg.querySelector(`#${CSS.escape(patternId)}`);
  if (!pattern) {
    pattern = document.createElementNS(svgns, 'pattern');
    pattern.setAttribute('id', patternId);
    pattern.setAttribute('patternUnits', 'objectBoundingBox');
    pattern.setAttribute('width', '1');
    pattern.setAttribute('height', '1');

    const image = document.createElementNS(svgns, 'image');
    image.setAttribute('href', imageUrl);
    image.setAttribute('width', '100%');
    image.setAttribute('height', '100%');
    image.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    pattern.appendChild(image);

    let defs = svg.querySelector('defs');
    if (!defs) {
      defs = document.createElementNS(svgns, 'defs');
      svg.insertBefore(defs, svg.firstChild);
    }
    defs.appendChild(pattern);
  } else {
    const image = pattern.querySelector('image');
    if (image) image.setAttribute('href', imageUrl);
  }

  rect.style.fill = `url(#${patternId})`;
  rect.setAttribute('data-has-image', 'true');
}

// === 🟢 Center-on-kios (auto focus posisi saat diklik/selected) ===
function centerOnKios(kiosId) {
  if (!svgContainer.value) return;
  const target = svgContainer.value.querySelector(`#${CSS.escape(kiosId)}`);
  if (!target) return;

  const container = svgContainer.value;
  const rect = target.getBoundingClientRect();
  const containerRect = container.getBoundingClientRect();

  const offsetX = containerRect.width / 2 - (rect.left - containerRect.left) - rect.width / 2;
  const offsetY = containerRect.height / 2 - (rect.top - containerRect.top) - rect.height / 2;

  container.scrollTo({
    left: container.scrollLeft - offsetX,
    top: container.scrollTop - offsetY,
    behavior: "smooth"
  });
}

// === Reload ===
async function reloadSvg() {
  const res = await api.get('/denah');
  svgContent.value = res.data.svg;
  await nextTick();
  renderSvg();
}

defineExpose({ reloadSvg });
</script>
