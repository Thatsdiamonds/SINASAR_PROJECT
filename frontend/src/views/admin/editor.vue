<template>
  <div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div v-if="!selected">
        <h2 class="sidebar-title">Palette</h2>

        <div v-for="item in palette" :key="item.type" class="palette-item">
          <div
            class="draggable"
            draggable="true"
            @dragstart="onPaletteDragStart($event, item)"
          >
            {{ item.label }}
          </div>
        </div>

        <hr class="divider" />
        
        <label class="import-btn">
          Import SVG
          <input ref="fileInput" type="file" accept=".svg,image/svg+xml" @change="importSvg" hidden />
        </label>
        
        <button @click="exportSvg" class="btn">Export SVG</button>
        <button @click="clearAll" class="btn">Clear All</button>

        <hr class="divider" />
      </div>
      
      <div v-if="selected">
        <h3 class="edit-title">Edit Shape</h3>

        <!-- ID field -->
        <div class="field">
          <label><strong>ID:</strong></label>
          <input 
            v-model="tempId" 
            @input="onIdInput"
            @blur="validateAndApplyId"
            type="text"
            class="input monospace"
            placeholder="Enter unique ID"
          />
          <div v-if="idError" class="error-text">{{ idError }}</div>
        </div>

        <div class="info"><strong>Type:</strong> {{ selected.type }}</div>

        <div class="col">
          <label class="field">x
            <input v-model.number="selected.x" type="number" class="input" />
          </label>
          <label class="field">y
            <input v-model.number="selected.y" type="number" class="input" />
          </label>
        </div>

        <div v-if="selected.type==='rect'" class="col">
          <label class="field">w
            <input v-model.number="selected.width" type="number" class="input" />
          </label>
          <label class="field">h
            <input v-model.number="selected.height" type="number" class="input" />
          </label>
        </div>

        <div v-if="selected.type==='circle'" class="field">
          <label>r
            <input v-model.number="selected.r" type="number" class="input" />
          </label>
        </div>

        <div class="field">
          <label>fill
            <input v-model="selected.fill" type="color" class="input" />
          </label>
        </div>

        <div v-if="selected.type==='text'" class="field">
          <label>text
            <input v-model="selected.text" type="text" class="input" />
          </label>
        </div>

        <div class="col">
          <button @click="duplicateSelected" class="btn btn-blue">Duplicate</button>
          <button @click="deleteSelected" class="btn btn-red">Delete</button>
        </div>
      </div>

      <div v-else class="hint-text">Click a shape to select it</div>
      
    <div class="button cheatingBtn" @click="$router.go(-1);"style="
                    --border-color: #E3E8EF;
                    --bg-color: white;
                    --color: #024196;
                    --sub-color: #0241969a;
                    --border-hov: #247CFF;
                    --bg-hov: #F8FAFD;"
                     >
          <img src="/icons/drought.svg" alt="Icon" width="auto" height="100%">
        <div class="text">
          <a>kembali</a>
          <a>halaman sebelumnya</a>
        </div>
      </div>
    </aside>

    <!-- Canvas -->
    <div class="canvas-area">
    <!-- Header -->
    <div class="canvas-header">
      <div>SVG Drag & Drop — Vue 3</div>
      <div class="subtext">Zoom: {{ (zoom * 100).toFixed(0) }}%</div>
    </div>

    <!-- Kontrol zoom (desain seperti maps-controls) -->
    <div class="maps-controls">
      <button class="zoom-btn" @click="zoomIn" title="Perbesar">
        <img src="/icons/plus.svg" alt="Zoom In" />
      </button>
      <button class="zoom-btn" @click="zoomOut" title="Perkecil">
        <img src="/icons/minus.svg" alt="Zoom Out" />
      </button>
      <button class="zoom-btn" @click="resetView" title="Reset Tampilan">
        <img src="/icons/car.svg" alt="Reset" />
      </button>
    </div>

    <!-- Wrapper Canvas -->
    <div 
      class="canvas-wrapper" 
      ref="svgWrapper"
      @pointerdown="startPan"
      @pointermove="doPan"
      @pointerup="endPan"
      @pointercancel="endPan"
      @wheel.prevent="onWheelZoom"
      @drop="onDrop"
      @dragover.prevent
      @click.self="deselectAll"
    >
      <svg
        ref="svg"
        :viewBox="`0 0 ${canvasWidth} ${canvasHeight}`"
        preserveAspectRatio="xMidYMid meet"
        class="svg-canvas"
      >
        <g 
          v-for="(s, idx) in shapes" :key="s.id" :data-id="s.id"
          :transform="`translate(${offset.x}, ${offset.y}) scale(${zoom})`"
          style="transform-origin: 0 0;" 
        >

            <!-- shapes -->
              <rect
                v-if="s.type==='rect'"
                :id="s.id"
                :x="s.x"
                :y="s.y"
                :width="s.width"
                :height="s.height"
                :fill="s.fill"
                :stroke="isSelected(s) ? '#2563eb' : '#0f172a'"
                :stroke-width="isSelected(s) ? 2 : 1"
                @pointerdown.stop="startDrag($event, s)"
                @click.stop="select(s)"
                class="shape"
              />

              <circle
                v-if="s.type==='circle'"
                :id="s.id"
                :cx="s.x"
                :cy="s.y"
                :r="s.r"
                :fill="s.fill"
                :stroke="isSelected(s) ? '#2563eb' : '#0f172a'"
                :stroke-width="isSelected(s) ? 2 : 1"
                @pointerdown.stop="startDrag($event, s)"
                @click.stop="select(s)"
                class="shape"
              />

              <!-- selection handle -->
              <g v-if="isSelected(s)">
                <rect
                  v-if="s.type==='rect'"
                  :x="s.x - 6"
                  :y="s.y - 6"
                  width="12"
                  height="12"
                  class="resize-handle"
                  @pointerdown.stop.prevent="startResize($event, s, 'br')"
                />
              </g>
        </g>
      </svg>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, computed } from 'vue'

const palette = [
  { type: 'rect', label: 'Rectangle' },
  { type: 'circle', label: 'Circle' },
]

const canvasWidth = 800
const canvasHeight = 600

// --- STATE ZOOM & PAN / DRAG ---

const zoom = ref(1)
const minZoom = 0.2
const maxZoom = 4
const offset = ref({ x: 0, y: 0 })


const shapes = reactive([])
const selectedId = ref(null)
const svg = ref(null)
const svgWrapper = ref(null)
const fileInput = ref(null)
const idError = ref('')
const tempId = ref('')

let isPanning = false
let panStart = { x: 0, y: 0 }
let activePointerId = null
let dragState = null
let resizeState = null


// Computed property untuk mendapatkan shape yang sedang dipilih
const selected = computed(() => {
  if (!selectedId.value) return null
  return shapes.find(s => s.id === selectedId.value)
})

function uid(prefix = '') {
  // Generate ID unik dengan format: prefix + timestamp + random
  const timestamp = Date.now().toString(36)
  const random = Math.random().toString(36).slice(2, 7)
  return `${prefix}${timestamp}_${random}`
}

function getSvgPoint(evt) {
  const pt = svg.value.createSVGPoint()
  pt.x = evt.clientX
  pt.y = evt.clientY
  const ctm = svg.value.getScreenCTM().inverse()
  return pt.matrixTransform(ctm)
}

function onPaletteDragStart(e, item) {
  e.dataTransfer.setData('application/x-shape', JSON.stringify(item))
}

function onDrop(e) {
  try {
    const raw = e.dataTransfer.getData('application/x-shape')
    const item = JSON.parse(raw)
    const p = getSvgPoint(e)
    addShape(item.type, p.x, p.y)
  } catch (err) {
    // ignore
  }
}

function addShape(type, x = 100, y = 100) {
  const base = { id: uid('s_'), type }
  if (type === 'rect') {
    shapes.push(Object.assign(base, { x: x - 50, y: y - 25, width: 100, height: 50, fill: '#fef3c7' }))
  } else if (type === 'circle') {
    shapes.push(Object.assign(base, { x, y, r: 40, fill: '#bfdbfe' }))
  }
}

function select(s) {
  selectedId.value = s.id
  tempId.value = s.id // Set tempId saat select
  idError.value = '' // Reset error saat select shape baru
}

function isSelected(s) {
  return selectedId.value === s.id
}

function deselectAll() {
  // Deselect semua shape saat klik di area kosong
  selectedId.value = null
  tempId.value = ''
  idError.value = ''
}

function onIdInput() {
  // Reset error saat user mengetik
  idError.value = ''
}

function validateAndApplyId() {
  if (!selected.value) return
  
  const newId = tempId.value.trim()
  const oldId = selected.value.id
  
  // Jika ID tidak berubah, tidak perlu validasi
  if (newId === oldId) {
    return
  }
  
  // Cek jika ID kosong
  if (!newId) {
    idError.value = 'ID cannot be empty'
    tempId.value = oldId // Kembalikan ke ID lama
    return
  }
  
  // Cek jika ID sudah dipakai oleh shape lain
  const duplicate = shapes.find(s => s.id === newId && s.id !== oldId)
  if (duplicate) {
    idError.value = 'ID already exists. Please use unique ID.'
    tempId.value = oldId // Kembalikan ke ID lama
    return
  }
  
  // ID valid, apply perubahan
  selected.value.id = newId
  selectedId.value = newId // Update selectedId agar shape tetap terseleksi
  idError.value = ''
}

function getSelected() {
  return shapes.find((x) => x.id === selectedId.value)
}

// Fungsi DELETE - menghapus shape yang dipilih
function deleteSelected() {
  if (!selectedId.value) return
  
  const idx = shapes.findIndex((x) => x.id === selectedId.value)
  if (idx !== -1) {
    // Konfirmasi sebelum menghapus (opsional)
    if (confirm('Are you sure you want to delete this shape?')) {
      shapes.splice(idx, 1)
      selectedId.value = null
    }
  }
}

function duplicateSelected() {
  const s = getSelected()
  if (!s) return
  const copy = JSON.parse(JSON.stringify(s))
  copy.id = uid('s_')
  copy.x = (copy.x || copy.cx || copy.x1) + 20
  copy.y = (copy.y || copy.cy || copy.y1) + 20
  shapes.push(copy)
  // Select the duplicated shape
  selectedId.value = copy.id
}

function clearAll() {
  if (shapes.length > 0) {
    if (confirm('Are you sure you want to clear all shapes?')) {
      shapes.splice(0, shapes.length)
      selectedId.value = null
    }
  }
}

// --- Dragging shapes inside SVG ---
function startDrag(evt, shape) {
  // evt is pointerdown from shape; stop pan starting
  evt.stopPropagation && evt.stopPropagation()
  evt.preventDefault && evt.preventDefault()

  // pointer capture on the target so move/up are delivered reliably
  activePointerId = evt.pointerId ?? null
  try { evt.target.setPointerCapture && evt.target.setPointerCapture(activePointerId) } catch (e) { /* ignore */ }

  const p = getSvgPoint(evt)
  dragState = {
    shapeId: shape.id,
    startX: p.x,
    startY: p.y,
    orig: JSON.parse(JSON.stringify(shape)),
  }

  // gunakan pointermove/pointerup pada window to be safe
  window.addEventListener('pointermove', onDrag)
  window.addEventListener('pointerup', endDrag)

  select(shape)
}

function onDrag(evt) {
  // ignore if different pointer
  if (activePointerId != null && evt.pointerId !== activePointerId && dragState && dragState.shapeId) return
  if (!dragState) return
  const p = getSvgPoint(evt)
  const dx = p.x - dragState.startX
  const dy = p.y - dragState.startY
  const s = shapes.find((x) => x.id === dragState.shapeId)
  if (!s) return
  if (s.type === 'rect' || s.type === 'text') {
    s.x = dragState.orig.x + dx
    s.y = dragState.orig.y + dy
  } else if (s.type === 'circle') {
    s.x = dragState.orig.x + dx
    s.y = dragState.orig.y + dy
  } else if (s.type === 'line') {
    s.x1 = dragState.orig.x1 + dx
    s.y1 = dragState.orig.y1 + dy
    s.x2 = dragState.orig.x2 + dx
    s.y2 = dragState.orig.y2 + dy
  }
}

function endDrag(evt) {
  // release pointer capture from last target if any
  if (activePointerId != null) {
    try {
      // try release from svgWrapper or event target
      if (evt && evt.target && evt.target.releasePointerCapture) {
        evt.target.releasePointerCapture(activePointerId)
      }
      if (svgWrapper.value && svgWrapper.value.releasePointerCapture) {
        svgWrapper.value.releasePointerCapture(activePointerId)
      }
    } catch (e) { /* ignore */ }
  }

  dragState = null
  activePointerId = null
  window.removeEventListener('pointermove', onDrag)
  window.removeEventListener('pointerup', endDrag)
}

// --- Resizing (simple implementation for rect's bottom-right corner) ---
function startResize(evt, shape, handle) {
  const p = getSvgPoint(evt)
  resizeState = { shapeId: shape.id, startX: p.x, startY: p.y, orig: JSON.parse(JSON.stringify(shape)), handle }
  window.addEventListener('pointermove', onResize)
  window.addEventListener('pointerup', endResize)
}

function onResize(evt) {
  if (!resizeState) return
  const p = getSvgPoint(evt)
  const dx = p.x - resizeState.startX
  const dy = p.y - resizeState.startY
  const s = shapes.find((x) => x.id === resizeState.shapeId)
  if (!s) return
  if (s.type === 'rect') {
    s.width = Math.max(10, resizeState.orig.width + dx)
    s.height = Math.max(10, resizeState.orig.height + dy)
  }
}

function endResize() {
  resizeState = null
  window.removeEventListener('pointermove', onResize)
  window.removeEventListener('pointerup', endResize)
}

// --- Export ---
function exportSvg() {
  resetView()
  const svgEl = svg.value
  const cloned = svgEl.cloneNode(true)

  // Hapus atribut data-id dari g wrapper
  const nodes = cloned.querySelectorAll('[data-id]')
  nodes.forEach((n) => n.removeAttribute('data-id'))

  // Hapus atribut Vue scoped (data-v-*)
  const allElements = cloned.querySelectorAll('*')
  allElements.forEach(el => {
    Array.from(el.attributes).forEach(attr => {
      if (attr.name.startsWith('data-v-')) el.removeAttribute(attr.name)
    })
  })

  // Hapus selection handles
  const handles = cloned.querySelectorAll('rect[width="12"][height="12"]')
  handles.forEach(h => h.parentNode.removeChild(h))

  // Cari semua shape
  const shapesEls = cloned.querySelectorAll('rect, circle')
  if (shapesEls.length === 0) {
    alert('No shapes to export.')
    return
  }

  // Hitung bounding box (min/max x/y)
  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity

  shapesEls.forEach(el => {
    if (el.tagName === 'rect') {
      const x = parseFloat(el.getAttribute('x') || 0)
      const y = parseFloat(el.getAttribute('y') || 0)
      const w = parseFloat(el.getAttribute('width') || 0)
      const h = parseFloat(el.getAttribute('height') || 0)
      minX = Math.min(minX, x)
      minY = Math.min(minY, y)
      maxX = Math.max(maxX, x + w)
      maxY = Math.max(maxY, y + h)
    } else if (el.tagName === 'circle') {
      const cx = parseFloat(el.getAttribute('cx') || 0)
      const cy = parseFloat(el.getAttribute('cy') || 0)
      const r = parseFloat(el.getAttribute('r') || 0)
      minX = Math.min(minX, cx - r)
      minY = Math.min(minY, cy - r)
      maxX = Math.max(maxX, cx + r)
      maxY = Math.max(maxY, cy + r)
    }
  })

  const width = maxX - minX
  const height = maxY - minY
  const padding = 100 // agar tidak terlalu pas di tepi

  // Set viewBox agar sesuai posisi shape
  cloned.setAttribute('viewBox', `${minX - padding} ${minY - padding} ${width + padding * 2} ${height + padding * 2}`)
  cloned.setAttribute('width', width + padding * 2)
  cloned.setAttribute('height', height + padding * 2)

  // Hapus grid background
  const grid = cloned.querySelector('rect[fill^="url(#grid)"]')
  if (grid) grid.remove()

  // Hapus style inline tidak perlu
  shapesEls.forEach(el => el.removeAttribute('style'))

  const serializer = new XMLSerializer()
  let source = serializer.serializeToString(cloned)

  // Pastikan namespace benar
  if (!source.includes('xmlns="http://www.w3.org/2000/svg"')) {
    source = source.replace(
      '<svg',
      '<svg xmlns="http://www.w3.org/2000/svg"'
    )
  }

  const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'canvas.svg'
  a.click()
  URL.revokeObjectURL(url)
}





// --- Import SVG ---
function importSvg(event) {
  const file = event.target.files[0]
  if (!file) return
  
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const svgText = e.target.result
      const parser = new DOMParser()
      const svgDoc = parser.parseFromString(svgText, 'image/svg+xml')
      
      // Cek jika ada error parsing
      const parserError = svgDoc.querySelector('parsererror')
      if (parserError) {
        alert('Error: Invalid SVG file')
        return
      }
      
      const svgElement = svgDoc.querySelector('svg')
      if (!svgElement) {
        alert('Error: No SVG element found')
        return
      }
      
      // Parse semua elemen SVG yang didukung
      parseSvgElements(svgElement)
      
      // Reset file input
      if (fileInput.value) {
        fileInput.value.value = ''
      }
      
    } catch (error) {
      console.error('Import error:', error)
      alert('Error importing SVG file')
    }
  }
  
  reader.readAsText(file)
}

function parseSvgElements(svgElement) {
  const existingIds = new Set(shapes.map(s => s.id))
  
  // Helper function to get or generate unique ID
  function getUniqueId(element, prefix = 's_') {
    let originalId = element.getAttribute('id')
    
    // Jika ada ID asli dan belum dipakai, gunakan itu
    if (originalId && !existingIds.has(originalId)) {
      existingIds.add(originalId)
      return originalId
    }
    
    // Jika ID asli sudah dipakai, tambahkan suffix
    if (originalId && existingIds.has(originalId)) {
      let counter = 1
      let newId = `${originalId}_${counter}`
      while (existingIds.has(newId)) {
        counter++
        newId = `${originalId}_${counter}`
      }
      existingIds.add(newId)
      return newId
    }
    
    // Jika tidak ada ID, generate baru
    let newId = uid(prefix)
    while (existingIds.has(newId)) {
      newId = uid(prefix)
    }
    existingIds.add(newId)
    return newId
  }
  
  // Parse rectangles
  const rects = svgElement.querySelectorAll('rect')
  rects.forEach(rect => {
    // Skip jika ini adalah grid background atau selection handle
    const width = parseFloat(rect.getAttribute('width') || 0)
    const height = parseFloat(rect.getAttribute('height') || 0)
    
    if (width === 12 && height === 12) return // Skip selection handles
    if (width === canvasWidth && height === canvasHeight) return // Skip grid
    
    const shape = {
      id: getUniqueId(rect, 'rect_'),
      type: 'rect',
      x: parseFloat(rect.getAttribute('x') || 0),
      y: parseFloat(rect.getAttribute('y') || 0),
      width: width,
      height: height,
      fill: rect.getAttribute('fill') || '#fef3c7'
    }
    shapes.push(shape)
  })
  
  // Parse circles
  const circles = svgElement.querySelectorAll('circle')
  circles.forEach(circle => {
    const shape = {
      id: getUniqueId(circle, 'circle_'),
      type: 'circle',
      x: parseFloat(circle.getAttribute('cx') || 0),
      y: parseFloat(circle.getAttribute('cy') || 0),
      r: parseFloat(circle.getAttribute('r') || 40),
      fill: circle.getAttribute('fill') || '#bfdbfe'
    }
    shapes.push(shape)
  })
  
  // Parse lines
  const lines = svgElement.querySelectorAll('line')
  lines.forEach(line => {
    const shape = {
      id: getUniqueId(line, 'line_'),
      type: 'line',
      x1: parseFloat(line.getAttribute('x1') || 0),
      y1: parseFloat(line.getAttribute('y1') || 0),
      x2: parseFloat(line.getAttribute('x2') || 100),
      y2: parseFloat(line.getAttribute('y2') || 100),
      stroke: line.getAttribute('stroke') || '#0f172a',
      strokeWidth: parseFloat(line.getAttribute('stroke-width') || 2)
    }
    shapes.push(shape)
  })
  
  // Parse text
  const texts = svgElement.querySelectorAll('text')
  texts.forEach(text => {
    const shape = {
      id: getUniqueId(text, 'text_'),
      type: 'text',
      x: parseFloat(text.getAttribute('x') || 0),
      y: parseFloat(text.getAttribute('y') || 0),
      text: text.textContent || 'Hello',
      fontSize: parseFloat(text.getAttribute('font-size') || 24),
      fill: text.getAttribute('fill') || '#0f172a'
    }
    shapes.push(shape)
  })
  
  console.log(`Imported ${shapes.length} shapes`)
}

function zoomIn() {
  zoom.value = Math.min(maxZoom, zoom.value + 0.1)
}
function zoomOut() {
  zoom.value = Math.max(minZoom, zoom.value - 0.1)
}
function resetView() {
  zoom.value = 1
  offset.value = { x: 0, y: 0 }
}
function onWheelZoom(e) {
  if (e.ctrlKey || e.metaKey) {
    e.preventDefault()
    if (e.deltaY < 0) zoomIn()
    else zoomOut()
  }
}
function startPan(evt) {
  // hanya klik kiri / primary pointer
  if (evt.button !== undefined && evt.button !== 0) return

  // jika klik berasal dari shape atau childnya (memiliki data-id pada wrapper group), abort pan
  // evt.target mungkin adalah elemen svg child (rect/circle/text...), jadi cek closest
  const fromShape = evt.target && evt.target.closest && evt.target.closest('[data-id]')
  if (fromShape) return

  isPanning = true
  activePointerId = evt.pointerId ?? null
  panStart = { x: evt.clientX - offset.value.x, y: evt.clientY - offset.value.y }

  // capture pointer to ensure we continue receiving pointermove/pointerup
  if (svgWrapper.value && svgWrapper.value.setPointerCapture && activePointerId != null) {
    try { svgWrapper.value.setPointerCapture(activePointerId) } catch (e) { /* ignore */ }
  }
}


function doPan(evt) {
  if (!isPanning) return
  // ignore other pointers
  if (activePointerId != null && evt.pointerId !== activePointerId) return

  offset.value = {
    x: evt.clientX - panStart.x,
    y: evt.clientY - panStart.y
  }
}
function endPan(evt) {
  if (activePointerId != null && evt && evt.pointerId !== activePointerId) return
  isPanning = false
  if (svgWrapper.value && svgWrapper.value.releasePointerCapture && activePointerId != null) {
    try { svgWrapper.value.releasePointerCapture(activePointerId) } catch (e) { /* ignore */ }
  }
  activePointerId = null
}

onMounted(() => {
  svgWrapper.value.addEventListener("wheel", onWheelZoom, { passive: false })
  addShape('rect', 150, 120)
  addShape('circle', 320, 160)
})

</script>

<style scoped>
/* Layout utama */
.app {
  display: flex;
  height: 100vh;
  font-family: Arial, Helvetica, sans-serif;
}

/* Sidebar */
.sidebar {
  max-width: min-content;
  padding: 16px;
  border-right: 1px solid #e5e7eb;
  box-sizing: border-box;
}
.sidebar-title {
  margin: 0 0 12px 0;
}
.palette-item {
  margin-bottom: 10px;
}
.draggable {
  padding: 8px;
  border-radius: 6px;
  background: #f8fafc;
  cursor: grab;
  border: 1px solid #e2e8f0;
}
.draggable:active {
  cursor: grabbing;
}

.import-btn {
  width: 100%;
  padding: 8px;
  margin-bottom: 8px;
  background: #8b5cf6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: block;
  text-align: center;
  box-sizing: border-box;
}

.divider {
  margin: 12px 0;
}

.edit-title {
  margin: 0 0 8px 0;
}

.field {
  margin-bottom: 8px;
}
.row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}
.input {
  width: 100%;
  padding: 6px;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 12px;
}
.monospace {
  font-family: monospace;
}
.error-text {
  font-size: 11px;
  color: #ef4444;
  margin-top: 4px;
}
.info {
  font-size: 13px;
  margin-bottom: 8px;
}
.hint-text {
  margin-top: 12px;
  color: #6b7280;
}

/* Tombol */
.btn {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
  cursor: pointer;
  border: none;
  border-radius: 4px;
  background: #e5e7eb;
  transition: opacity 0.2s;
  margin: 5px 0;
}
.btn:hover {
  opacity: 0.9;
}
.btn:active {
  opacity: 0.8;
}
.btn-blue {
  background: #3b82f6;
  color: white;
}
.btn-red {
  background: #ef4444;
  color: white;
}

/* Canvas */
.canvas-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  background: #f8fafc;
}
.canvas-header {
  padding: 8px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.subtext {
  font-size: 13px;
  color: #6b7280;
}
.canvas-wrapper {
  flex: 1;
  overflow: hidden;
  position: relative;
}
.svg-canvas {
  background: white;
  width: 100%;
  height: 100%;
  user-select: none;
  touch-action: none;
  transition: transform 0.1s ease-out;
}

/* --- Kontrol Zoom --- */
.maps-controls {
  position: absolute;
  top: 10vh;
  right: 2vh;
  display: flex;
  gap: 2vh;
  pointer-events: none;
  z-index: 10;
  width: auto;
}

.maps-controls * {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.zoom-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1vw;
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #247cff;
  border-radius: 50%;
  cursor: pointer;
  pointer-events: auto;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.zoom-btn img {
  width: 1.4vw;
  height: auto;
}

.zoom-btn:hover {
  background: #438fff;
}

.zoom-btn:hover img {
  filter: brightness(0) invert(1);
  transform: scale(1.4);
}

.zoom-btn:active img:first-child {
  filter: brightness(0) invert(1);
  transform: scale(1.2);
}
/* Shapes */
.shape {
  cursor: move;
}
.text-shape {
  user-select: none;
}
.resize-handle {
  fill: #fff;
  stroke: #2563eb;
  stroke-width: 1;
  cursor: nwse-resize;
}
</style>
