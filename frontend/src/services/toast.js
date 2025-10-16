// utils/toast.js
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-default.css'

const toast = useToast()

// simpan daftar toast aktif
const activeToasts = []

// 🎨 Custom base style
const baseStyle = `
  border-radius: 10px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 0.95rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  color: #fff;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  letter-spacing: 0.3px;
`

// 💅 Color themes
const toastTheme = {
  success: { background: 'linear-gradient(135deg, #22c55e, #16a34a)' },
  error: { background: 'linear-gradient(135deg, #ef4444, #b91c1c)' },
  info: { background: 'linear-gradient(135deg, #3b82f6, #2563eb)' },
  warning: { background: 'linear-gradient(135deg, #f59e0b, #d97706)' },
}

// 🧠 Function utama
function show(type, msg, options = {}) {
  const style = `${baseStyle} background: ${toastTheme[type].background};`

  // kalau udah ada 3 toast aktif, hapus yang paling lama
  if (activeToasts.length >= 3) {
    const oldest = activeToasts.shift()
    oldest?.dismiss?.() // tutup toast paling lama
  }

  const instance = toast.open({
    message: `<span>${msg}</span>`,
    type,
    position: 'bottom-left',
    dangerouslyHTMLString: true,
    duration: 3000,
    style,
    ...options,
    onClose: () => {
      // hapus dari daftar atkif kalau udah ditutup otomatis
      const idx = activeToasts.indexOf(instance)
      if (idx !== -1) activemToasts.splice(idx, 1)
      // panggil callback user (kalau ada)
      options?.onClose?.()
    },
  })

  // simpan instance aktif
  activeToasts.push(instance)
}

export default {
  success(msg, options = {}) {
    show('success', msg, options)
  },
  error(msg, options = {}) {
    show('error', msg, options)
  },
  info(msg, options = {}) {
    show('info', msg, options)
  },
  warning(msg, options = {}) {
    show('warning', msg, options)
  },
}
