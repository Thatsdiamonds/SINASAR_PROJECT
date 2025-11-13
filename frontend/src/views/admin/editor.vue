<template>
  <div class="app" style="display:flex; height:100vh; font-family:Arial, Helvetica, sans-serif;">
    <!-- Sidebar -->
    <aside style="width:220px; padding:16px; border-right:1px solid #e5e7eb; box-sizing:border-box; overflow-y:auto;">
      <h2 style="margin:0 0 12px 0">Palette</h2>

      <div v-for="item in palette" :key="item.type" style="margin-bottom:10px;">
        <div
          class="draggable"
          draggable="true"
          @dragstart="onPaletteDragStart($event, item)"
          style="padding:8px; border-radius:6px; background:#f8fafc; cursor:grab; border:1px solid #e2e8f0;"
        >
          {{ item.label }}
        </div>
      </div>

      <hr style="margin:12px 0" />
      
      <label style="width:100%; padding:8px; margin-bottom:8px; background:#8b5cf6; color:white; border:none; border-radius:4px; cursor:pointer; display:block; text-align:center; box-sizing:border-box;">
        Import SVG
        <input ref="fileInput" type="file" accept=".svg,image/svg+xml" @change="importSvg" style="display:none" />
      </label>
      
      <button @click="exportSvg" style="width:100%; padding:8px; margin-bottom:8px; box-sizing:border-box;">Export SVG</button>
      <button @click="clearAll" style="width:100%; padding:8px; box-sizing:border-box;">Clear All</button>

      <hr style="margin:12px 0" />

      <div v-if="selected">
        <h3 style="margin:0 0 8px 0">Edit Shape</h3>
        
        <div style="margin-bottom:8px;">
          <label style="font-size:13px; display:block; margin-bottom:4px;"><strong>ID:</strong></label>
          <input 
            v-model="tempId" 
            @input="onIdInput"
            @blur="validateAndApplyId"
            type="text" 
            style="width:100%; padding:6px; font-family:monospace; font-size:12px; border:1px solid #e2e8f0; border-radius:4px;" 
            placeholder="Enter unique ID"
          />
          <div v-if="idError" style="font-size:11px; color:#ef4444; margin-top:4px;">{{ idError }}</div>
        </div>
        
        <div style="font-size:13px; margin-bottom:8px"><strong>Type:</strong> {{ selected.type }}</div>

        <div style="display:flex; gap:8px; margin-bottom:8px;">
          <label style="flex:1">x
            <input v-model.number="selected.x" type="number" style="width:100%" />
          </label>
          <label style="flex:1">y
            <input v-model.number="selected.y" type="number" style="width:100%" />
          </label>
        </div>

        <div v-if="selected.type==='rect'" style="display:flex; gap:8px; margin-bottom:8px;">
          <label style="flex:1">w
            <input v-model.number="selected.width" type="number" style="width:100%" />
          </label>
          <label style="flex:1">h
            <input v-model.number="selected.height" type="number" style="width:100%" />
          </label>
        </div>

        <div v-if="selected.type==='circle'" style="margin-bottom:8px;">
          <label>r
            <input v-model.number="selected.r" type="number" style="width:100%" />
          </label>
        </div>

        <div style="margin-bottom:8px;">
          <label>fill
            <input v-model="selected.fill" type="color" style="width:100%" />
          </label>
        </div>

        <div v-if="selected.type==='text'" style="margin-bottom:8px;">
          <label>text
            <input v-model="selected.text" type="text" style="width:100%" />
          </label>
        </div>

        <div style="display:flex; gap:8px; margin-top:10px;">
          <button @click="duplicateSelected" style="flex:1; background:#3b82f6; color:white; border:none; padding:8px; border-radius:4px; cursor:pointer;">Duplicate</button>
          <button @click="deleteSelected" style="flex:1; background:#ef4444; color:white; border:none; padding:8px; border-radius:4px; cursor:pointer;">Delete</button>
        </div>
      </div>

      <div v-else style="margin-top:12px; color:#6b7280">Click a shape to select it</div>
    </aside>

    <!-- Canvas -->
    <div style="max-width: 300px; display:flex; flex-direction:column;">
      <div style="padding:8px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
        <div>SVG Drag & Drop — Vue 3</div>
        <div style="font-size:13px; color:#6b7280">Drag a palette item onto the canvas to add it</div>
      </div>

      <div style="flex:1; display:flex;">
        <div style="flex:1; padding:12px; box-sizing:border-box; position:relative">
          <div
            ref="svgWrapper"
            @dragover.prevent
            @drop="onDrop"
            style="width:100%; height:100%; border:1px dashed #e6edf3; display:flex; align-items:center; justify-content:center; background:#fbfdff"
          >
            <svg
              ref="svg"
              :width="canvasWidth"
              :height="canvasHeight"
              @click.self="deselectAll"
              style="background:white; touch-action:none"
            >
              <!-- grid -->
              <defs>
                <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                  <path d="M20 0 L0 0 0 20" fill="none" stroke="#f1f5f9" stroke-width="1" />
                </pattern>
              </defs>
              <rect :width="canvasWidth" :height="canvasHeight" fill="url(#grid)" @click="deselectAll" />

              <!-- shapes -->
              <g v-for="(s, idx) in shapes" :key="s.id" :data-id="s.id">
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
                  style="cursor:move"
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
                  style="cursor:move"
                />

                <line
                  v-if="s.type==='line'"
                  :id="s.id"
                  :x1="s.x1"
                  :y1="s.y1"
                  :x2="s.x2"
                  :y2="s.y2"
                  :stroke="s.stroke"
                  :stroke-width="s.strokeWidth"
                  @pointerdown.stop="startDrag($event, s)"
                  @click.stop="select(s)"
                  style="cursor:move"
                />

                <text
                  v-if="s.type==='text'"
                  :id="s.id"
                  :x="s.x"
                  :y="s.y"
                  :font-size="s.fontSize"
                  :fill="s.fill"
                  @pointerdown.stop="startDrag($event, s)"
                  @click.stop="select(s)"
                  style="cursor:move; user-select:none"
                >
                  {{ s.text }}
                </text>

                <!-- selection handles -->
                <g v-if="isSelected(s)">
                  <rect
                    v-if="s.type==='rect'"
                    :x="s.x - 6"
                    :y="s.y - 6"
                    width="12"
                    height="12"
                    fill="#fff"
                    stroke="#2563eb"
                    stroke-width="1"
                    style="cursor:nwse-resize"
                    @pointerdown.stop.prevent="startResize($event, s, 'br')"
                  />
                </g>
              </g>
            </svg>
          </div>
        </div>

        <!-- Right side: small preview / instructions -->
        <aside style="width:260px; padding:12px; border-left:1px solid #e5e7eb; box-sizing:border-box;">
          <h4 style="margin:0 0 8px 0">Tips</h4>
          <ul style="padding-left:18px; margin:0; font-size:13px">
            <li>Drag palette items onto canvas to add.</li>
            <li>Click a shape to select and edit properties.</li>
            <li>Click empty area to deselect.</li>
            <li>Import SVG files to edit existing designs.</li>
            <li>Edit properties in the sidebar and changes apply instantly.</li>
            <li>Drag shapes to move. Resize rect by dragging corner handle.</li>
            <li>Export creates a downloadable .svg file.</li>
          </ul>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, computed } from 'vue'

const palette = [
  { type: 'rect', label: 'Rectangle' },
  { type: 'circle', label: 'Circle' },
  { type: 'line', label: 'Line' },
  { type: 'text', label: 'Text' },
]

const shapes = reactive([])
const selectedId = ref(null)
const svg = ref(null)
const svgWrapper = ref(null)
const fileInput = ref(null)
const idError = ref('')
const tempId = ref('')
const canvasWidth = 1000
const canvasHeight = 700

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
  } else if (type === 'line') {
    shapes.push(Object.assign(base, { x1: x - 40, y1: y - 40, x2: x + 40, y2: y + 40, stroke: '#0f172a', strokeWidth: 2 }))
  } else if (type === 'text') {
    shapes.push(Object.assign(base, { x, y, text: 'Hello', fontSize: 24, fill: '#0f172a' }))
  }
}

function addRandomShape() {
  const t = palette[Math.floor(Math.random() * palette.length)].type
  addShape(t, Math.random() * (canvasWidth - 200) + 100, Math.random() * (canvasHeight - 200) + 100)
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
  const p = getSvgPoint(evt)
  dragState = {
    shapeId: shape.id,
    startX: p.x,
    startY: p.y,
    orig: JSON.parse(JSON.stringify(shape)),
  }

  window.addEventListener('pointermove', onDrag)
  window.addEventListener('pointerup', endDrag)

  select(shape)
}

function onDrag(evt) {
  if (!dragState) return
  const p = getSvgPoint(evt)
  const dx = p.x - dragState.startX
  const dy = p.y - dragState.startY
  const s = shapes.find((x) => x.id === dragState.shapeId)
  if (!s) return
  if (s.type === 'rect') {
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
  } else if (s.type === 'text') {
    s.x = dragState.orig.x + dx
    s.y = dragState.orig.y + dy
  }
}

function endDrag() {
  dragState = null
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
  const svgEl = svg.value
  const cloned = svgEl.cloneNode(true)
  
  // Hapus atribut data-id dari g wrapper
  const nodes = cloned.querySelectorAll('[data-id]')
  nodes.forEach((n) => n.removeAttribute('data-id'))
  
  // Hapus atribut Vue scoped (data-v-*)
  const allElements = cloned.querySelectorAll('*')
  allElements.forEach(el => {
    // Hapus semua atribut yang dimulai dengan data-v-
    Array.from(el.attributes).forEach(attr => {
      if (attr.name.startsWith('data-v-')) {
        el.removeAttribute(attr.name)
      }
    })
  })
  
  // Hapus selection handle group elements
  const handles = cloned.querySelectorAll('g')
  handles.forEach((g) => {
    // Hapus group yang berisi rect handle kecil (12x12)
    const rect = g.querySelector('rect[width="12"]')
    if (rect) {
      g.remove()
    }
  })
  
  // Bersihkan g wrapper yang kosong dan hanya menyisakan shape dengan ID
  const groups = cloned.querySelectorAll('g')
  groups.forEach(g => {
    // Jika g memiliki child element dengan id, pindahkan child ke parent g
    const children = Array.from(g.children)
    if (children.length > 0) {
      const parent = g.parentNode
      children.forEach(child => {
        if (child.hasAttribute('id')) {
          parent.insertBefore(child, g)
        }
      })
      g.remove()
    }
  })
  
  // Bersihkan style inline yang tidak perlu
  const shapesWithId = cloned.querySelectorAll('[id]')
  shapesWithId.forEach(el => {
    // Hapus style cursor
    el.removeAttribute('style')
    
    // Reset stroke untuk export (hapus highlight selection)
    if (el.tagName === 'rect' || el.tagName === 'circle') {
      el.setAttribute('stroke', '#0f172a')
      el.setAttribute('stroke-width', '1')
    }
  })

  const serializer = new XMLSerializer()
  let source = serializer.serializeToString(cloned)
  
  // Bersihkan namespace xmlns yang berlebihan jika ada
  source = source.replace(/ xmlns="[^"]*"/g, (match, offset) => {
    // Hanya pertahankan xmlns pertama di tag <svg>
    return offset < 100 ? match : ''
  })
  
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

onMounted(() => {
  // create a helpful default
  addShape('rect', 150, 120)
  addShape('circle', 320, 160)
})
</script>

<style scoped>
/* small responsive tweaks */
.draggable:active { cursor:grabbing }

button {
  cursor: pointer;
  transition: opacity 0.2s;
}

button:hover {
  opacity: 0.9;
}

button:active {
  opacity: 0.8;
}
</style>