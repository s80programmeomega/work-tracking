// resources\js\router\index.ts
import { ref, onMounted, nextTick } from 'vue';
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const loading = ref(false)
export const isLoading = ref(false)

const router = createRouter({
  history: createWebHistory('/'),
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
    // RECHERCHE GLOBALE (Phase 6)
    // ==========================================
    {
      path: '/search',
      name: 'search',
      component: () => import('../pages/Search.vue'),
      meta: {
        title: 'Recherche globale',
        requiresAuth: true,
      },
    },

    // ==========================================
    // CENTRE D'AIDE (Phase 7)
    // ==========================================
    {
      path: '/help',
      name: 'help.index',
      component: () => import('../pages/help/HelpIndex.vue'),
      meta: { title: 'Centre d\'aide', requiresAuth: true },
    },
    {
      path: '/help/c/:slug',
      name: 'help.category',
      component: () => import('../pages/help/HelpCategory.vue'),
      meta: { title: 'Catégorie d\'aide', requiresAuth: true },
    },
    {
      path: '/help/a/:slug',
      name: 'help.article',
      component: () => import('../pages/help/HelpArticle.vue'),
      meta: { title: 'Article d\'aide', requiresAuth: true },
    },
    {
      path: '/admin/support',
      name: 'admin.support',
      component: () => import('../pages/admin/AdminSupport.vue'),
      meta: { title: 'Support tickets', requiresAuth: true },
    },
    // Gestion des articles (owner/directeur + super_admin) — gardée côté API.
    {
      path: '/admin/help-articles',
      name: 'admin.help.articles',
      component: () => import('../pages/admin/HelpArticlesList.vue'),
      meta: { title: 'Gestion du centre d\'aide', requiresAuth: true },
    },
    {
      path: '/admin/help-articles/create',
      name: 'admin.help.articles.create',
      component: () => import('../pages/admin/HelpArticleForm.vue'),
      meta: { title: 'Créer un article', requiresAuth: true },
    },
    {
      path: '/admin/help-articles/:id/edit',
      name: 'admin.help.articles.edit',
      component: () => import('../pages/admin/HelpArticleForm.vue'),
      meta: { title: 'Modifier un article', requiresAuth: true },
    },
    // Catégories gérées dans l'onglet "Catégories" de la page articles (admin.help.articles).

    // ==========================================
    // WORKSPACES
    // ==========================================
    // Note : workspaces.index et workspaces.show ont été supprimés — le picker
    // est désormais le point d'entrée unique pour la sélection de workspace.
    {
      path: '/workspaces/select',
      name: 'workspaces.select',
      component: () => import('../pages/workspaces/WorkspacePicker.vue'),
      meta: {
        title: 'Sélectionner un Workspace',
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
      path: '/workspaces/:id/subscription',
      name: 'workspaces.subscription',
      component: () => import('../pages/workspaces/Subscription.vue'),
      meta: {
        title: 'Abonnement',
        requiresAuth: true,
      },
    },
    {
      path: '/subscription/plans',
      name: 'subscription.plans',
      component: () => import('../pages/subscription/Plans.vue'),
      meta: {
        title: 'Plans & abonnement',
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
    {
      path: '/invitations/projet/:token',
      name: 'AcceptProjetInvitation',
      component: () => import('../pages/AcceptProjetInvitation.vue'),
      meta: {
        requiresAuth: false, // Accessible sans authentification
        layout: 'fullscreen'
      }
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
      path: '/activites/all/activity',
      name: 'Activites',
      component: () => import('../pages/Activites.vue'),
      meta: {
        title: 'Gestion des Activités',
        requiresAuth: true,
      },
    },
    {
      path: '/activites/mes-activites',
      name: 'activites.my',
      component: () => import('../pages/MesActivites.vue'),
      meta: {
        requiresAuth: true,
        title: 'Mes activités'
      }
    },
    {
      path: '/activites/en-retard',
      name: 'activites.overdue',
      component: () => import('../pages/ActivitesEnRetard.vue'),
      meta: {
        requiresAuth: true,
        title: 'Activités en retard'
      }
    },
    {
      path: '/activites/:id',
      name: 'activites.show',
      component: () => import('../pages/ActiviteDetail.vue'),
      meta: {
        requiresAuth: true,
        title: 'Détail de l\'activité'
      }
    },

    {
      path: '/taches',
      name: 'Toutes les taches',
      component: () => import('../pages/Taches.vue'),
      meta: {
        title: 'Gestion des Tâches',
        requiresAuth: true,
      },
    },
    {
      path: '/taches/mes-taches',
      name: 'taches.mes-taches',
      component: () => import('../pages/MesTaches.vue'),
      meta: {
        title: 'Mes Tâches',
        requiresAuth: true,
        breadcrumb: 'Mes Tâches'
      }
    },
    // ✅ AJOUT : Route pour les tâches en responsabilité
    {
      path: '/taches/responsable',
      name: 'taches.responsable',
      component: () => import('../pages/TachesResponsable.vue'),
      meta: {
        requiresAuth: true,
        title: 'Mes Tâches en Responsabilité'
      }
    },
    {
      path: '/taches/assignees',
      name: 'taches.assignees',
      component: () => import('../pages/TachesAssignees.vue'),
      meta: {
        title: 'Tâches Assignées',
        requiresAuth: true,
        breadcrumb: 'Tâches Assignées'
      }
    },
    {
      path: '/taches/resultats/en-attente',
      name: 'taches.resultats.pending',
      redirect: '/validations/a-traiter',
    },
    {
      path: '/validations/a-traiter',
      name: 'validations.a-traiter',
      component: () => import('../pages/ValidationResultats.vue'),
      meta: {
        title: 'Validations à traiter',
        requiresAuth: true,
        breadcrumb: 'Validations à traiter'
      }
    },
    {
      path: '/mes-validations',
      name: 'mes-validations',
      component: () => import('../pages/MesValidationsEnAttente.vue'),
      meta: {
        title: 'Mes validations en attente',
        requiresAuth: true,
        breadcrumb: 'Mes validations en attente'
      }
    },
    {
      path: '/taches/waiting-colleagues',
      name: 'taches.waiting-colleagues',
      component: () => import('../pages/TachesAttendantCollegues.vue'),
      meta: {
        requiresAuth: true,
        title: 'En Attente de Collègues'
      }
    },
    {
      path: '/taches/coordination',
      name: 'taches.coordination',
      component: () => import('../pages/TachesParUtilisateur.vue'),
      meta: {
        requiresAuth: true,
        title: 'Vue Coordination',
        permissions: ['responsable_activite', 'responsable_projet', 'super_admin']
      }
    },
    {
      path: '/taches/en-retard',
      name: 'taches.overdue',
      component: () => import('../pages/TachesEnRetard.vue'),
      meta: {
        title: 'Tâches en Retard',
        requiresAuth: true,
        breadcrumb: 'Tâches en Retard'
      }
    },
    {
      path: '/taches/:id',
      name: 'taches.show',
      component: () => import('../pages/taches/TacheDetail.vue'),
      meta: {
        requiresAuth: true,
        title: 'Détails de la tâche'
      }
    },
    {
      path: '/taches/:id/resultats/:resultatId',
      redirect: (to) => ({ name: 'taches.show', params: { id: to.params.id }, query: { tab: 'results' } }),
    },

    // {
    // path: '/evaluations',
    // component: () => import('@/layouts/MainLayout.vue'),
    // meta: { requiresAuth: true },
    // children: [
    {
      path: '/evaluations/dashboard',
      name: 'evaluations.dashboard',
      component: () => import('../pages/EvaluationDashboard.vue'),
      meta: {
        title: 'Tableau de bord des évaluations',
        breadcrumb: [
          { label: 'Accueil', to: '/' },
          { label: 'Évaluations', to: '/evaluations/dashboard' },
          { label: 'Tableau de bord' }
        ]
      }
    },
    {
      path: '/validations/en-attente',
      name: 'validations.en-attente',
      component: () => import('../pages/evaluations/PendingValidations.vue'),
      meta: {
        title: 'Validations en attente',
        breadcrumb: [
          { label: 'Accueil', to: '/' },
          { label: 'Évaluations', to: '/evaluations/dashboard' },
          { label: 'Validations en attente' }
        ]
      }
    },
    {
      path: '/evaluations/rapport-hebdomadaire',
      name: 'evaluations.rapport-hebdomadaire',
      component: () => import('../pages/RapportHebdomadaire.vue'),
      meta: {
        title: 'Rapport hebdomadaire',
        breadcrumb: [
          { label: 'Accueil', to: '/' },
          { label: 'Évaluations', to: '/evaluations/dashboard' },
          { label: 'Rapport hebdomadaire' }
        ]
      }
    },
    {
      path: '/evaluations/fiches',
      name: 'evaluations.fiches',
      component: () => import('../pages/FichesEvaluation.vue'),
      meta: {
        title: 'Fiches d\'évaluation',
        requiresAuth: true,
        breadcrumb: [
          { label: 'Accueil', to: '/' },
          { label: 'Évaluations', to: '/evaluations/dashboard' },
          { label: 'Fiches d\'évaluation' }
        ]
      }
    },
    {
      path: '/evaluations/performance',
      name: 'evaluations.performance',
      component: () => import('../pages/PerformanceEquipe.vue'),
      meta: {
        title: 'Performance d\'équipe',
        breadcrumb: [
          { label: 'Accueil', to: '/' },
          { label: 'Évaluations', to: '/evaluations/dashboard' },
          { label: 'Performance d\'équipe' }
        ]
      }
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
      // Task 9 — Fiche d'évaluation détaillée d'un agent (8 critères + 4 sections).
      // L'ID est l'utilisateur cible: c'est l'acteur qui peut consulter sa
      // propre fiche par défaut, et les rôles d'encadrement (cadre, manager,
      // owner) peuvent voir celles de leur scope (vérification serveur).
      path: '/evaluations/personnel/:id/historique',
      name: 'evaluations.personnel.historique',
      component: () => import('../pages/evaluations/AgentSheet.vue'),
      meta: {
        title: "Fiche d'évaluation",
        requiresAuth: true,
      },
    },

    // Task 10 — Tableau de bord évaluations (owner/manager/cadre)
    {
      path: '/evaluations/tableau-de-bord',
      name: 'evaluations.tableau-de-bord',
      component: () => import('../pages/evaluations/EvaluationDashboard.vue'),
      meta: {
        title: 'Tableau de bord évaluations',
        requiresAuth: true,
      },
    },

    // Task 10 — Vue globale des tâches du workspace (owner/directeur)
    {
      path: '/workspace/taches',
      name: 'workspace.taches',
      component: () => import('../pages/workspace/WorkspaceTaches.vue'),
      meta: {
        title: 'Toutes les tâches',
        requiresAuth: true,
      },
    },


    // Gestion des membres du workspace (propriétaire uniquement — ban/unban/invite)
    {
      path: '/workspace/members',
      name: 'workspace.members',
      component: () => import('../pages/workspace/WorkspaceMembers.vue'),
      meta: {
        title: 'Membres & Invitations',
        requiresAuth: true,
      },
    },

    // Compte admin temporaire (propriétaire uniquement)
    {
      path: '/workspace/admin-account',
      name: 'workspace.admin-account',
      component: () => import('../pages/workspace/WorkspaceTempAdmin.vue'),
      meta: {
        title: 'Compte admin temporaire',
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
    // ==========================================
    // WORKSPACE CHAT (Phase 3)
    // ==========================================
    {
      path: '/workspace/chat',
      name: 'workspace.chat',
      component: () => import('../pages/workspace/WorkspaceChat.vue'),
      meta: {
        title: 'Chat Workspace',
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

    // ==========================================
    // DOCUMENTS
    // ==========================================
    {
      path: '/documents',
      name: 'Documents',
      component: () => import('../pages/Documents.vue'),
      meta: {
        title: 'Documents',
        requiresAuth: true,
      },
    },
    // {
    //   path: '/documents/:id',
    //   name: 'DocumentDetails',
    //   component: () => import('../pages/documents/Show.vue'),
    //   meta: {
    //     title: 'Détails du document',
    //     requiresAuth: true,
    //   },
    // },
    {
      path: '/workspaces/:workspaceId/documents',
      name: 'WorkspaceDocuments',
      component: () => import('../pages/documents/WorkspaceDocuments.vue'),
      meta: {
        title: 'Documents du workspace',
        requiresAuth: true,
      },
    },
    {
      path: '/projets/:projetId/documents',
      name: 'ProjetDocuments',
      component: () => import('../pages/documents/ProjetDocuments.vue'),
      meta: {
        title: 'Documents du projet',
        requiresAuth: true,
      },
    },
    {
      path: '/activites/:activiteId/documents',
      name: 'ActiviteDocuments',
      component: () => import('../pages/documents/ActiviteDocuments.vue'),
      meta: {
        title: 'Documents de l\'activité',
        requiresAuth: true,
      },
    },
    {
      path: '/taches/:tacheId/documents',
      name: 'TacheDocuments',
      component: () => import('../pages/documents/TacheDocuments.vue'),
      meta: {
        title: 'Documents de la tâche',
        requiresAuth: true,
      },
    },
    // ==========================================
    // PLATFORM ADMIN (super_admin only)
    // ==========================================
    {
      path: '/admin',
      redirect: '/admin/dashboard',
    },
    {
      path: '/admin/dashboard',
      name: 'admin.dashboard',
      component: () => import('../pages/admin/AdminDashboard.vue'),
      meta: {
        title: 'Platform Dashboard',
        requiresAuth: true,
        requiresSuperAdmin: true,
      },
    },
    {
      path: '/admin/workspaces',
      name: 'admin.workspaces',
      component: () => import('../pages/admin/AdminWorkspaces.vue'),
      meta: {
        title: 'Workspace Management',
        requiresAuth: true,
        requiresSuperAdmin: true,
      },
    },
    {
      path: '/admin/plans',
      name: 'admin.plans',
      component: () => import('../pages/admin/ManagePlans.vue'),
      meta: {
        title: 'Plan Management',
        requiresAuth: true,
        requiresSuperAdmin: true,
      },
    },
    {
      path: '/admin/users',
      name: 'admin.users',
      component: () => import('../pages/admin/AdminUsers.vue'),
      meta: {
        title: 'User Management',
        requiresAuth: true,
        requiresSuperAdmin: true,
      },
    },
    {
      path: '/admin/roles',
      name: 'admin.roles',
      component: () => import('../pages/admin/AdminRoles.vue'),
      meta: {
        title: 'Roles & Permissions',
        requiresAuth: true,
        requiresSuperAdmin: true,
      },
    },

    {
      path: '/support',
      name: 'support',
      component: () => import('../pages/Support.vue'),
      meta: { requiresAuth: true },
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
      path: '/admin/logs',
      name: 'admin.logs',
      component: () => import('../pages/admin/AdminLogs.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
    {
      path: '/admin/my-audit-log',
      name: 'admin.my-audit-log',
      component: () => import('../pages/admin/DirecteurAuditLog.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/auth/callback',
      name: 'AuthCallback',
      component: () => import('../pages/Auth/SocialCallback.vue'),
      meta: { guest: true },
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



// Navigation guard améliorée
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  isLoading.value = true
  document.title = `${to.meta.title || 'Dashboard'} | Work Tracking`

  const isLoggedIn = authStore.isAuthenticated

  try {
    if (to.meta.requiresAuth) {
      if (!isLoggedIn) {
        isLoading.value = false
        return next({ name: 'Signin', query: { redirect: to.fullPath } })
      }

      // Load user if not yet loaded
      if (!authStore.user) {
        try {
          await authStore.fetchUser()
        } catch (error) {
          isLoading.value = false
          return next({ name: 'Signin' })
        }
      }

      // Super-admin only routes
      if (to.meta.requiresSuperAdmin && !authStore.user?.is_super_admin) {
        isLoading.value = false
        return next({ name: '404 Error', query: { code: '403', from: to.fullPath } })
      }

      // Admin temporaire : traité comme un utilisateur normal (accès workspace picker).
      // Admin permanent : doit rester dans les routes admin uniquement.
      const isTempAdmin = authStore.user?.is_temp_admin === true
      if (authStore.isSuperAdmin && !isTempAdmin && !String(to.name ?? '').startsWith('admin')) {
        isLoading.value = false
        return next({ name: 'admin.dashboard' })
      }

      // Routes avec permissions requises — superadmin no longer bypasses these.
      if (Array.isArray(to.meta.permissions) && (to.meta.permissions as string[]).length > 0) {
        const userRoles: string[] = authStore.user?.roles ?? []
        const allowed = (to.meta.permissions as string[]).some((r: string) => userRoles.includes(r))
        if (!allowed) {
          isLoading.value = false
          return next({ name: '404 Error', query: { code: '403', from: to.fullPath } })
        }
      }

      // Redirect utilisateur (no workspace yet) to workspace creation,
      // unless they're already heading there, a permanent superadmin, or a temp admin.
      const noWorkspace = !authStore.user?.current_workspace_id
      const isHeadingToWorkspaceCreate = to.name === 'workspaces.create'
      if (noWorkspace && !isHeadingToWorkspaceCreate && !authStore.isSuperAdmin && !authStore.isTempAdmin) {
        isLoading.value = false
        return next({ name: 'workspaces.create' })
      }
    }

    // Guest-only routes (signin, signup) redirect authenticated users.
    // Admin temporaire → workspace picker. Admin permanent → admin.dashboard.
    if (to.meta.guest && isLoggedIn) {
      isLoading.value = false
      const isTempAdminGuest = authStore.user?.is_temp_admin === true
      return next(authStore.isSuperAdmin && !isTempAdminGuest
        ? { name: 'admin.dashboard' }
        : { name: 'workspaces.select' })
    }

    next()
  } catch (error) {
    isLoading.value = false
    next({ name: 'Signin' })
  }
})

// After each navigation
router.afterEach(() => {
  loading.value = false
})

export { loading }

export default router
