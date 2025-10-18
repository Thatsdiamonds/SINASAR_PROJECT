import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'
import toast from '@/services/toast'
import NotFound from '@/components/NotFound.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'Index',
      component: () => import('@/views/index.vue')
    },
    {
      path: '/detail-kios',
      name: 'Kios Desc',
      component: () => import('@/views/seller/view-kios.vue'),
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/login-newgen.vue')
    },
    {
      path: '/logout',
      name: 'Logout',
      beforeEnter: async (to, from, next) => {
        try {
          await api.post("/logout", {}, {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`
            }
          })
        } catch (error) {
          console.warn("Gagal revoke token:", error);
        }

        localStorage.removeItem('token')

        toast.info('Anda telah logout.')
        next('/login')
      }
    },
    {
      path: '/admin/register',
      name: 'Register for Admins',
      component: () => import('@/views/auth-nextgen.vue'),
      meta: { role: 'admin' },
      beforeEnter: (to, from, next) => {
        if (!to.query['auth-type']) {
          next({ 
            path: to.path, 
            query: { ...to.query, 'auth-type': 'sign-up' }
          })
        } else {
            next()
        }
  }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('@/views/auth-nextgen.vue'),
      beforeEnter: (to, from, next) => {
        if (!to.query['auth-type']) {
          next({ 
            path: to.path, 
            query: { ...to.query, 'auth-type': 'sign-up' }
          })
        } else {
            next()
        }
      }},
    {
      path: '/auth',
      name: 'AuthNextGen',
      component: () => import('@/views/auth-nextgen.vue'),
    },
    {
      path: '/admin',
      name: 'AdminDenah',
      component: () => import('@/views/admin/dashboard.vue'),
      meta: { role: 'admin' }
    },
    {
      path: '/seller',
      name: 'SellerDashboard',
      component: () => import('@/views/seller/dashboard.vue'),
      meta: { roles: ['seller', 'admin'] }
    },
    {
      path: "/my-kios/update",
      name: "UpdateKios",
      component: () => import('@/views/seller/update-kios.vue'),
      meta: { roles: ['seller'] }
    },
    {
      path: "/:catchAll(.*)",
      name: "NotFound",
      component: NotFound,
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  if (to.matched.some(record => record.name === 'NotFound')) {
    return next()
  }

  if (to.path === '/' || to.path === '/login' || to.name === 'Kios Desc' || to.name === 'Register' || to.path === '/auth'   || to.path.startsWith('/detail-kios')) {
    return next()
  }

  const token = localStorage.getItem('token')
  
  if (!token) {
    toast.warning('Silakan login terlebih dahulu.')
    return next('/login')
  }

  try {
    const response = await api.get('/user/profile', { silent: true })
    const userRole = response.data.role

    if (to.meta.role && to.meta.role !== userRole) {
      toast.warning('Akses ditolak. Silakan login dengan role yang sesuai.')
      return next('/login')
    }

    if (to.meta.roles && !to.meta.roles.includes(userRole)) {
      toast.warning('Akses ditolak. Silakan login dengan role yang sesuai.')
      return next('/login')
    }

    next()
  } catch (error) {
    localStorage.removeItem('token')
    toast.error('Sesi Anda telah berakhir. Silakan login ulang.')
    next('/login')
  }
})

export default router
