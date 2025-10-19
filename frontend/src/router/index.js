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
      path: '/seller/dashboard/detail-kios',
      name: 'Kios Desc seller',
      component: () => import('@/views/seller/view-kios.vue'),
    },
    {
      path: '/detail-kios',
      name: 'Kios Desc',
      component: () => import('@/views/seller/view-kios.vue'),
    },
    {
      path: '/login',
      name: 'Login',
      redirect: to => {
        return { path: '/auth', query: { 'auth-type': 'sign-in' } }
      }
    },
    {
      path: '/logout',
      name: 'Logout',
      redirect: to => {
        return { path: '/auth', query: { 'auth-type': 'sign-out' } }
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
            name: to.name,
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
      redirect: to => {
        return { path: '/auth', query: { 'auth-type': 'sign-up' } }
      }
    },
    {
      path: '/auth',
      name: 'AuthNextGen',
      component: () => import('@/views/auth-nextgen.vue'),
    },
    {
      path: '/admin/dashboard',
      name: 'AdminDenah',
      component: () => import('@/views/admin/dashboard.vue'),
      meta: { role: 'admin' }
    },
    {
      path: '/seller/dashboard',
      name: 'SellerDashboard',
      component: () => import('@/views/seller/dashboard.vue'),
      meta: { roles: ['seller', 'admin'] }
    },
    {
      path: "/seller/dashboard/my-kios/update",
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

  if (to.path === '/' || to.name === 'Kios Desc' || to.path === '/auth' || to.path.startsWith('/seller/dashboard/detail-kios')) {
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
    console.log('User Role:', userRole);

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
