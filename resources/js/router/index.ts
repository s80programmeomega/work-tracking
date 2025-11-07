// resources\js\router\index.ts
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
  },
  routes: [
    // ==========================================
    // DASHBOARD
    // ==========================================
    {
      path: '/',
      name: 'Dashboard',
      component: () => import('../pages/Dashboard.vue'),
      meta: {
        title: 'Dashboard',
        requiresAuth: true,
      },
    },


    // ==========================================
    // WORKSPACES
    // ==========================================
    {
      path: '/workspaces',
      name: 'workspaces.index',
      component: () => import('../pages/workspaces/Index.vue'),
      meta: {
        title: 'Mes Workspaces',
        requiresAuth: true,
      },
    },
    {
      path: '/workspaces/create',
      name: 'workspaces.create',
      component: () => import('../pages/workspaces/Create.vue'),
      meta: {
        title: 'Créer un Workspace',
        requiresAuth: true,
      },
    },
    {
      path: '/workspaces/:id',
      name: 'workspaces.show',
      component: () => import('../pages/workspaces/Show.vue'),
      meta: {
        title: 'Détails du Workspace',
        requiresAuth: true,
      },
    },
    {
      path: '/workspaces/:id/edit',
      name: 'workspaces.edit',
      component: () => import('../pages/workspaces/Edit.vue'),
      meta: {
        title: 'Modifier le Workspace',
        requiresAuth: true,
      },
    },
    {
      path: '/workspaces/:id/settings',
      name: 'workspaces.settings',
      component: () => import('../pages/workspaces/Settings.vue'),
      meta: {
        title: 'Paramètres du Workspace',
        requiresAuth: true,
      },
    },
    {
      path: '/accept-invitation/:token',
      name: 'accept-invitation',
      component: () => import('../pages/AcceptInvitation.vue'),
      meta: {
        requiresAuth: false,
        title: 'Accepter l\'invitation'
      }
    },
 // ==========================================
    // PROJETS
    // ==========================================
    {
      path: '/projets/list/all',
      name: 'projets.index',
      component: () => import('../pages/Projets.vue'),
      meta: {
        title: 'Tous les Projets',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/mes-projets',
      name: 'projets.my',
      component: () => import('../pages/projets/MyProjects.vue'),
      meta: {
        title: 'Mes Projets',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/archives',
      name: 'projets.archived',
      component: () => import('../pages/projets/Archived.vue'),
      meta: {
        title: 'Projets Archivés',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/create',
      name: 'projets.create',
      component: () => import('../pages/projets/Create.vue'),
      meta: {
        title: 'Créer un Projet',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/:id',
      name: 'projets.show',
      component: () => import('../pages/projets/Show.vue'),
      meta: {
        title: 'Détails du Projet',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/:id/edit',
      name: 'projets.edit',
      component: () => import('../pages/projets/Edit.vue'),
      meta: {
        title: 'Modifier le Projet',
        requiresAuth: true,
      },
    },

    //  {
    //   path: '/projets',
    //   name: 'Projets',
    //   component: () => import('../pages/Projets.vue'),
    //   meta: {
    //     title: 'Gestion des Projets',
    //     requiresAuth: true,
    //   },
    // },

    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../pages/Others/UserProfile.vue'),
      meta: {
        title: 'Profile',
        requiresAuth: true,
      },
    },
    {
      path: '/form-elements',
      name: 'Form Elements',
      component: () => import('../pages/Forms/FormElements.vue'),
      meta: {
        title: 'Form Elements',
        requiresAuth: true,
      },
    },
    {
      path: '/basic-tables',
      name: 'Basic Tables',
      component: () => import('../pages/Tables/BasicTables.vue'),
      meta: {
        title: 'Basic Tables',
        requiresAuth: true,
      },
    },
    {
      path: '/line-chart',
      name: 'Line Chart',
      component: () => import('../pages/Chart/LineChart/LineChart.vue'),
    },
    {
      path: '/bar-chart',
      name: 'Bar Chart',
      component: () => import('../pages/Chart/BarChart/BarChart.vue'),
    },
    {
      path: '/alerts',
      name: 'Alerts',
      component: () => import('../pages/UiElements/Alerts.vue'),
      meta: {
        title: 'Alerts',
      },
    },
    {
      path: '/avatars',
      name: 'Avatars',
      component: () => import('../pages/UiElements/Avatars.vue'),
      meta: {
        title: 'Avatars',
      },
    },
    {
      path: '/badge',
      name: 'Badge',
      component: () => import('../pages/UiElements/Badges.vue'),
      meta: {
        title: 'Badge',
      },
    },

    {
      path: '/buttons',
      name: 'Buttons',
      component: () => import('../pages/UiElements/Buttons.vue'),
      meta: {
        title: 'Buttons',
      },
    },

    {
      path: '/images',
      name: 'Images',
      component: () => import('../pages/UiElements/Images.vue'),
      meta: {
        title: 'Images',
      },
    },
    {
      path: '/videos',
      name: 'Videos',
      component: () => import('../pages/UiElements/Videos.vue'),
      meta: {
        title: 'Videos',
      },
    },
    {
      path: '/blank',
      name: 'Blank',
      component: () => import('../pages/Pages/BlankPage.vue'),
      meta: {
        title: 'Blank',
      },
    },

    {
      path: '/users',
      name: 'Users',
      component: () => import('../pages/Users/Index.vue'),
      meta: {
        title: 'Gestion des Utilisateurs',
        requiresAuth: true,
        roles: ['super_admin', 'manager'],
      },
    },



    {
      path: '/activites',
      name: 'Activites',
      component: () => import('../pages/Activites.vue'),
      meta: {
        title: 'Gestion des Activités',
        requiresAuth: true,
      },
    },

    {
      path: '/taches',
      name: 'Taches',
      component: () => import('../pages/Taches.vue'),
      meta: {
        title: 'Gestion des Tâches',
        requiresAuth: true,
      },
    },

    {
      path: '/notifications',
      name: 'Notifications',
      component: () => import('../pages/Notifications.vue'),
      meta: {
        title: 'Notifications',
        requiresAuth: true,
      },
    },

    {
      path: '/notification-preferences',
      name: 'NotificationPreferences',
      component: () => import('../pages/NotificationPreferences.vue'),
      meta: {
        title: 'Préférences de notification',
        requiresAuth: true,
      },
    },
 
    {
      path: '/users/invitations',
      name: 'invitations',
      component: () => import('../pages/Users/Invitations.vue'),
      meta: {
        title: 'invitations',
        requiresAuth: true,
      },
    },
    {
      path: '/teams',
      name: 'teams.index',
      component: () => import('../pages/Teams.vue'),
      meta: {
        title: 'Équipes',
        requiresAuth: true,
      },
    },

    {
      path: '/teams/:uuid',
      name: 'teams.show',
      component: () => import('../pages/Teams/Show.vue'),
      meta: {
        title: 'Détails de l\'équipe',
        requiresAuth: true,
      },
    },

    {
      path: '/labels',
      name: 'labels.index',
      component: () => import('../pages/labels/LabelsManagement.vue'),
      meta: {
        title: 'Gestion des Labels',
        requiresAuth: true,
      },
    },

    {
      path: '/labels/templates',
      name: 'labels.templates',
      component: () => import('../pages/labels/LabelTemplates.vue'),
      meta: {
        title: 'Templates de Labels',
        requiresAuth: true,
      },
    },

    {
      path: '/error-404',
      name: '404 Error',
      component: () => import('../pages/Errors/FourZeroFour.vue'),
      meta: {
        title: '404 Error',
      },
    },

    {
      path: '/signin',
      name: 'Signin',
      component: () => import('../pages/Auth/Signin.vue'),
      meta: {
        title: 'Signin',
        guest: true,
      },
    },
    {
      path: '/signup',
      name: 'Signup',
      component: () => import('../pages/Auth/Signup.vue'),
      meta: {
        title: 'Signup',
        guest: true,
      },
    },
    {
      path: '/forgot-password',
      name: 'ForgotPassword',
      component: () => import('../pages/Auth/ForgotPassword.vue'),
      meta: {
        title: 'Forgot Password',
        guest: true,
      },
    },
    {
      path: '/reset-password',
      name: 'ResetPassword',
      component: () => import('../pages/Auth/ResetPassword.vue'),
      meta: {
        title: 'Reset Password',
        guest: true,
      },
    },
    {
      path: '/unauthorized',
      name: 'Unauthorized',
      component: () => import('../pages/Errors/FourZeroFour.vue'),
      meta: {
        title: 'Unauthorized',
      },
    },
  ],
})

export default router

// Global navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Set document title
  document.title = `${to.meta.title || 'Dashboard'} | Work Tracking`

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'Signin', query: { redirect: to.fullPath } })
  }

  // Check if route is for guests only (signin, signup)
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'Ecommerce' })
  }

  // Check role requirements
  if (to.meta.roles && !authStore.hasAnyRole(to.meta.roles)) {
    return next({ name: 'Unauthorized' })
  }

  // Check permission requirements
  if (to.meta.permissions && !authStore.hasAnyPermission(to.meta.permissions)) {
    return next({ name: 'Unauthorized' })
  }

  next()
})
