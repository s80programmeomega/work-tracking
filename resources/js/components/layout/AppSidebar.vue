<!-- resources\js\components\layout\AppSidebar.vue -->
<template>
    <aside :class="[
        'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-40  border-r border-gray-100 dark:border-gray-800 shadow-sm',
        {
            'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
            'lg:w-[90px]': !isExpanded && !isHovered,
            'translate-x-0 w-[290px]': isMobileOpen,
            '-translate-x-full': !isMobileOpen,
            'lg:translate-x-0': true,
        },
    ]" @mouseenter="!isExpanded && (isHovered = true)" @mouseleave="isHovered = false">
        <!-- Logo Section (inchangé) -->
        <!-- Logo Section -->
        <div :class="[
            'py-8 flex',
            !isExpanded && !isHovered
                ? 'lg:justify-center'
                : 'justify-start',
        ]">
            <router-link to="/">
                <img v-if="isExpanded || isHovered || isMobileOpen" class="dark:hidden" :src="Logo" alt="Logo"
                    width="150" height="40" />
                <img v-if="isExpanded || isHovered || isMobileOpen" class="hidden dark:block" :src="LogoDark" alt="Logo"
                    width="150" height="40" />
                <img v-else :src="Icon" alt="Logo" width="32" height="32" />
            </router-link>
        </div>

        <!-- Workspace Selector — only shown when user has at least one workspace -->
        <div v-if="(isExpanded || isHovered || isMobileOpen) && workspaces.length > 0" class="mb-4 px-2">
            <button @click="showWorkspaceSelector = !showWorkspaceSelector"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ currentWorkspaceInitials }}
                </div>
                <div class="flex-1 text-left overflow-hidden">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                        {{ currentWorkspace?.nom || 'Mon Workspace' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ workspaceProjectCount }} projets
                    </p>
                </div>
                <ChevronDownIcon :class="[
                    'w-4 h-4 text-gray-400 transition-transform',
                    { 'rotate-180': showWorkspaceSelector }
                ]" />
            </button>

            <!-- Workspace Dropdown avec Filtre Dashboard -->
            <transition name="fade-slide">
                <div v-if="showWorkspaceSelector"
                    class="mt-2 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                    <!-- Section Filtre Dashboard -->
                    <div class="px-3 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">
                            Filtre Dashboard
                        </p>
                        <button @click="selectWorkspaceForDashboard('all')" :class="[
                            'w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs font-medium transition-colors mb-1',
                            selectedDashboardWorkspace === 'all'
                                ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]">
                            <GlobeIcon class="w-3 h-3" />
                            <span>Tous les workspaces</span>
                            <CheckIcon v-if="selectedDashboardWorkspace === 'all'" class="w-3 h-3 ml-auto" />
                        </button>
                    </div>

                    <!-- Section Mes Workspaces -->
                    <div class="px-3 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                            Mes Workspaces
                        </p>
                    </div>

                    <div class="max-h-60 overflow-y-auto">
                        <!-- CORRECTION : Utiliser filteredWorkspaces et vérifier null -->
                        <button v-for="workspace in filteredWorkspaces" :key="workspace?.id || 'null'"
                            @click="handleSelectWorkspace(workspace)"
                            class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                            <div
                                class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-semibold text-sm">
                                {{ getWorkspaceInitials(workspace?.nom) }}
                            </div>
                            <div class="flex-1 text-left">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ workspace?.nom || 'Workspace inconnu' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ workspace?.projets_count || 0 }} projets
                                </p>
                            </div>

                            <!-- Indicateur workspace actuel -->
                            <CheckIcon v-if="currentWorkspace?.id === workspace?.id" class="w-4 h-4 text-brand-500" />

                            <!-- Bouton filtre dashboard pour ce workspace -->
                            <div role="button" tabindex="0" @click.stop="selectWorkspaceForDashboard(workspace?.id)"
                                :class="[
                                    'p-1 rounded transition-colors cursor-pointer',
                                    selectedDashboardWorkspace === workspace?.id
                                        ? 'bg-brand-500 text-white'
                                        : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-brand-500 hover:text-white'
                                ]"
                                :title="selectedDashboardWorkspace === workspace?.id ? 'Filtre actif' : 'Filtrer le dashboard'">
                                <FilterIcon class="w-3 h-3" />
                            </div>

                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="px-3 pt-2 mt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
                        <router-link to="/workspaces/create"
                            class="flex items-center gap-2 text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                            <PlusIcon class="w-4 h-4" />
                            Créer un workspace
                        </router-link>

                        <!-- Indicateur filtre actif -->
                        <div v-if="selectedDashboardWorkspace && selectedDashboardWorkspace !== 'all'"
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded">
                            <FilterIcon class="w-3 h-3" />
                            <span>Dashboard filtré</span>
                            <button @click="clearDashboardFilter"
                                class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                title="Effacer le filtre">
                                <XIcon class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Navigation Menu avec gestion des permissions -->
        <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
            <nav class="mb-6">
                <div class="flex flex-col gap-4">
                    <div v-for="(menuGroup, groupIndex) in filteredMenuGroups" :key="groupIndex">
                        <h2 :class="[
                            'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                            !isExpanded && !isHovered
                                ? 'lg:justify-center'
                                : 'justify-start',
                        ]">
                            <template v-if="isExpanded || isHovered || isMobileOpen">
                                {{ menuGroup.title }}
                            </template>
                            <HorizontalDots v-else />
                        </h2>
                        <ul class="flex flex-col gap-1.5">
                            <li v-for="(item, index) in menuGroup.items" :key="item.name">
                                <!-- Item with submenu -->
                                <button v-if="item.subItems" @click="toggleSubmenu(groupIndex, index)" :class="[
                                    'menu-item group w-full',
                                    {
                                        'menu-item-active': isSubmenuOpen(groupIndex, index),
                                        'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                                    },
                                    !isExpanded && !isHovered
                                        ? 'lg:justify-center'
                                        : 'lg:justify-start',
                                ]">
                                    <span :class="[
                                        isSubmenuOpen(groupIndex, index)
                                            ? 'menu-item-icon-active'
                                            : 'menu-item-icon-inactive',
                                    ]">
                                        <component :is="item.icon" />
                                    </span>
                                    <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text flex-1">
                                        {{ item.name }}
                                    </span>
                                    <span v-if="item.badge && (isExpanded || isHovered || isMobileOpen)"
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                                        {{ item.badge }}
                                    </span>
                                    <ChevronDownIcon v-if="isExpanded || isHovered || isMobileOpen" :class="[
                                        'ml-2 w-4 h-4 transition-transform duration-200',
                                        {
                                            'rotate-180 text-brand-500': isSubmenuOpen(groupIndex, index),
                                        },
                                    ]" />
                                </button>

                                <!-- Simple link item -->
                                <router-link v-else-if="item.path" :to="item.path" :class="[
                                    'menu-item group',
                                    {
                                        'menu-item-active': isActive(item.path),
                                        'menu-item-inactive': !isActive(item.path),
                                    },
                                ]">
                                    <span :class="[
                                        isActive(item.path)
                                            ? 'menu-item-icon-active'
                                            : 'menu-item-icon-inactive',
                                    ]">
                                        <component :is="item.icon" />
                                    </span>
                                    <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text flex-1">
                                        {{ item.name }}
                                    </span>
                                    <span v-if="item.badge && (isExpanded || isHovered || isMobileOpen)"
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                                        {{ item.badge }}
                                    </span>
                                </router-link>

                                <!-- Submenu items avec permissions -->
                                <transition @enter="startTransition" @after-enter="endTransition"
                                    @before-leave="startTransition" @after-leave="endTransition">
                                    <div v-show="isSubmenuOpen(groupIndex, index) &&
                                        (isExpanded || isHovered || isMobileOpen)
                                        ">
                                        <ul class="mt-1 space-y-1 ml-9">
                                            <li v-for="subItem in getFilteredSubItems(item.subItems)"
                                                :key="subItem.name">
                                                <router-link :to="subItem.path" :class="[
                                                    'menu-dropdown-item',
                                                    {
                                                        'menu-dropdown-item-active': isActive(subItem.path),
                                                        'menu-dropdown-item-inactive': !isActive(subItem.path),
                                                    },
                                                ]">
                                                    <span class="flex-1">{{ subItem.name }}</span>
                                                    <span class="flex items-center gap-1 ml-auto">
                                                        <span v-if="subItem.new" :class="[
                                                            'menu-dropdown-badge',
                                                            {
                                                                'menu-dropdown-badge-active': isActive(subItem.path),
                                                                'menu-dropdown-badge-inactive': !isActive(subItem.path),
                                                            },
                                                        ]">
                                                            new
                                                        </span>
                                                        <span v-if="subItem.count"
                                                            class="px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                            {{ subItem.count }}
                                                        </span>
                                                    </span>
                                                </router-link>
                                            </li>
                                        </ul>
                                    </div>
                                </transition>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Bottom Actions -->
        <div v-if="isExpanded || isHovered || isMobileOpen"
            class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
            <router-link
                v-if="canManageSettings || isSuperAdmin"
                :to="currentWorkspace?.id ? { name: 'workspaces.settings', params: { id: currentWorkspace.id } } : '/workspaces'"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <SettingsIcon class="w-5 h-5" />
                <span>Paramètres</span>
            </router-link>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, h } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    GridIcon,
    CalenderIcon,
    UserCircleIcon,
    ChatIcon,
    MailIcon,
    ChevronDownIcon,
    HorizontalDots,
    ListIcon,
    CheckIcon,
    PlusIcon,
    SettingsIcon,
    XIcon, StarIcon
} from '../../icons';

import BoxCubeIcon from '@/icons/BoxCubeIcon.vue';
import FolderIcon from '@/icons/FolderIcon.vue';
import TaskIcon from '@/icons/TaskIcon.vue';
import ClipboardCheckIcon from '@/icons/ClipboardCheckIcon.vue';
import UsersIcon from '@/icons/UsersIcon.vue';
import { useSidebar } from '@/composables/useSidebar';
import api from '@/api/axios'
import { useAuthStore } from '@/stores/authStore';
import { useWorkspace } from '@/composables/useWorkspace';
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions';


const LogoDark = new URL('@/assets/images/logo/Logo-dark.jpg', import.meta.url).href
const Logo = new URL('@/assets/images/logo/Logo.png', import.meta.url).href
const Icon = new URL('@/assets/images/logo/icon.jpg', import.meta.url).href

const route = useRoute();
const router = useRouter();
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();
const authStore = useAuthStore();

// ✅ Utiliser le composable workspace
const {
    currentWorkspace,
    currentWorkspaceId,
    workspaces,
    loading: workspaceLoading,
    selectWorkspace,
    fetchWorkspaces,
    onWorkspaceChanged,
    initializeCurrentWorkspace
} = useWorkspace();

const { canManageSettings } = useWorkspacePermissions(currentWorkspace);

// Workspace management
const showWorkspaceSelector = ref(false);
// const currentWorkspace = ref(null);
// const workspaces = ref([]);
// const loading = ref(false);

const selectedDashboardWorkspace = ref('all'); // 'all' ou workspace_id

// CORRECTION : Utiliser le getter isSuperAdmin du store
const isSuperAdmin = computed(() => {
    return authStore.isSuperAdmin;
});

// Computed property avec fallback
const filteredWorkspaces = computed(() => {
    return (workspaces.value || [])
        .filter(workspace => workspace && workspace.id)
        .map(workspace => ({
            id: workspace.id,
            nom: workspace.nom || 'Workspace sans nom',
            projets_count: workspace.projets_count ?? workspace.projet_count ?? 0,
        }))
})

// Icônes supplémentaires pour le filtre — définies en fonctions de rendu
// (h()) plutôt qu'avec template:'<svg…>' car le build de Vue utilisé par
// Vite est runtime-only et ne sait pas compiler un template à la volée.
const svgAttrs = {
    xmlns: 'http://www.w3.org/2000/svg',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': 2,
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
};

const FilterIcon = {
    props: ['className'],
    render() {
        return h('svg', { ...svgAttrs, class: this.className }, [
            h('polygon', { points: '22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3' }),
        ]);
    },
};

const GlobeIcon = {
    props: ['className'],
    render() {
        return h('svg', { ...svgAttrs, class: this.className }, [
            h('circle', { cx: 12, cy: 12, r: 10 }),
            h('line', { x1: 2, y1: 12, x2: 22, y2: 12 }),
            h('path', { d: 'M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z' }),
        ]);
    },
};

const currentWorkspaceInitials = computed(() => {
    if (!currentWorkspace.value) return 'MW';
    return getWorkspaceInitials(currentWorkspace.value.nom);
});

const workspaceProjectCount = computed(() => {
    return currentWorkspace.value?.projets_count ?? currentWorkspace.value?.projet_count ?? 0;
});

const getWorkspaceInitials = (name) => {
    return name
        ?.split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) || 'MW';
};


// ✅ Fonction de sélection de workspace
const handleSelectWorkspace = async (workspace) => {
    if (currentWorkspace.value?.id === workspace.id) {
        console.log('Sidebar: Même workspace, aucune action');
        showWorkspaceSelector.value = false;
        return;
    }

    console.log('Sidebar: Changement de workspace vers:', workspace.nom);

    try {
        await selectWorkspace(workspace);
        showWorkspaceSelector.value = false;
        console.log('✅ Sidebar: Workspace changé avec succès');
    } catch (error) {
        console.error('❌ Sidebar: Erreur lors du changement de workspace:', error);
    }
};

// ✅ Fonction pour filtrer le dashboard
const selectWorkspaceForDashboard = (workspaceId) => {
    console.log('🔄 Filtrage dashboard pour workspace:', workspaceId);

    selectedDashboardWorkspace.value = workspaceId;

    // Émettre un événement pour le dashboard
    window.dispatchEvent(new CustomEvent('dashboard-filter-changed', {
        detail: { workspaceId }
    }));

    // Si on est sur le dashboard, recharger les données
    if (route.path === '/') {
        router.go(0); // Rechargement simple
    }

    showWorkspaceSelector.value = false;
};

// ✅ Effacer le filtre dashboard
const clearDashboardFilter = () => {
    selectedDashboardWorkspace.value = 'all';
    window.dispatchEvent(new CustomEvent('dashboard-filter-changed', {
        detail: { workspaceId: 'all' }
    }));
};



// ✅ Écoute des changements externes
let unsubscribeWorkspaceListener = null;

const handleWorkspaceChange = (event) => {
    console.log('Sidebar: Changement externe détecté', event.detail);
    // Le currentWorkspace est déjà mis à jour par le composable
    // Fermer le dropdown si ouvert
    showWorkspaceSelector.value = false;
};


const menuGroups = computed(() => [
    {
        title: 'Principal',
        items: [
            {
                icon: GridIcon,
                name: 'Dashboard',
                path: '/',
            },
            {
                icon: BoxCubeIcon,
                name: 'Workspaces',
                path: '/workspaces',
            },
        ],
    },
    {
        title: 'Gestion de Projets',
        items: [
            {
                icon: FolderIcon,
                name: 'Projets',
                subItems: [
                    { name: 'Tableau de bord', path: '/projets/list/all', superAdminOnly: true },
                    { name: 'Mes projets', path: '/projets/mes-projets' },
                    { name: 'Projets archivés', path: '/projets/archives' },
                    // { name: 'Créer un projet', path: '/projets/create', new: true },
                ],
            },
            {
                icon: ListIcon,
                name: 'Activités',
                subItems: [
                    { name: 'Toutes les activités', path: '/activites/all/activity', superAdminOnly: true },
                    { name: 'Mes activités', path: '/activites/mes-activites' },
                    // { name: 'En retard', path: '/activites/en-retard', count: 5 },
                ],
            },
            // ==================== MISE À JOUR DE AppSidebar.vue ====================

            // {
            //     icon: TaskIcon,
            //     name: 'Tâches',
            //     subItems: [
            //         {
            //             name: 'Toutes les tâches',
            //             path: '/taches',
            //             superAdminOnly: false
            //         },
            //         {
            //             name: 'Mes tâches assignées',
            //             path: '/taches/assignees',
            //             description: 'Tâches où je suis intervenant',
            //             icon: '👤'
            //         },
            //         // ✅ NOUVEAU : Tâches où je suis responsable
            //         {
            //             name: 'Mes tâches en responsabilité',
            //             path: '/taches/responsable',
            //             description: 'Tâches dont je suis le responsable',
            //             icon: '👑',
            //             badge: 'new', // Optionnel : badge "nouveau"
            //             badgeColor: 'purple'
            //         },
            //         {
            //             name: 'Tâches en attente de validation',
            //             path: '/taches/resultats/en-attente',
            //             icon: '⏳'
            //         },
            //         {
            //             name: 'En Attente de Collègues',
            //             path: '/taches/waiting-colleagues',
            //             icon: '🤝'
            //         },
            //         {
            //             name: 'Vue Coordination',
            //             path: '/taches/coordination',
            //             icon: '🎯'
            //         },
            //     ],
            // },

            // ==================== ALTERNATIVE : Groupement par rôle ====================

            {
                icon: TaskIcon,
                name: 'Tâches',
                subItems: [
                    {
                        name: 'Toutes les tâches',
                        path: '/taches',
                        superAdminOnly: false
                    }, 
                    
                    {
                        name: 'En tant que responsable',
                        path: '/taches/responsable',
                        icon: '👑',
                        badge: 'new'
                    },
                    {
                        name: 'En tant qu\'intervenant',
                        path: '/taches/assignees',
                        icon: '👤'
                    },
 
                    {
                        name: 'Mes validations en attente',
                        path: '/mes-validations',
                        icon: '⏳'
                    },
                    // {
                    //     name: 'En attente de collègues',
                    //     path: '/taches/waiting-colleagues',
                    //     icon: '🤝'
                    // },
                    // {
                    //     name: 'Vue coordination',
                    //     path: '/taches/coordination',
                    //     icon: '🎯'
                    // },
                ],
            },
        ],
    },
    {
        title: 'Évaluation & Validation',
        items: [
            // {
            //     icon: ClipboardCheckIcon,
            //     name: 'Validations',
            //     badge: '8',
            //     subItems: [
            //         { name: 'En attente N1', path: '/validations/n1', count: 5 },
            //         { name: 'En attente N2', path: '/validations/n2', count: 3 },
            //         { name: 'Historique', path: '/validations/historique' },
            //     ],
            // },
            {
                icon: ClipboardCheckIcon,
                name: 'Évaluations',
                subItems: [
                    { name: 'Tableau de bord', path: '/evaluations/dashboard' },
                    { name: 'Validations à traiter', path: '/validations/a-traiter' },
                    // { name: 'Rapport hebdomadaire', path: '/evaluations/rapport-hebdomadaire' },
                    { name: 'Fiches d\'évaluation', path: '/evaluations/fiches' },
                    // { name: 'Performance d\'équipe', path: '/evaluations/performance' },
                ],
            },
        ],
    },
    // {
    //     title: 'Documentation & Ressources',
    //     items: [
    //         {
    //             icon: XIcon,
    //             name: 'Documents',
    //             subItems: [
    //                 { name: 'Tous les Documents', path: '/documents' },
    //                 { name: 'Mes Documents', path: '/documents/me' }, // Optionnel
    //                 { name: 'Partagés avec moi', path: '/documents/shared' }, // Optionnel
    //             ],
    //         },
    //     ],
    // },
    {
        title: 'Collaboration',
        items: [
            // {
            //     icon: ChatIcon,
            //     name: 'Équipes',
            //     subItems: [
            //         { name: 'Mes équipes', path: '/teams' },
            //         { name: 'Messages', path: '/teams/messages', count: 24 },
            //         { name: 'Annonces', path: '/teams/announcements' },
            //         { name: 'Ressources', path: '/teams/resources' },
            //     ],
            // },
            // {
            //     icon: UsersIcon,
            //     name: 'Utilisateurs',
            //     subItems: [
            //         { name: 'Tous les utilisateurs', path: '/users', superAdminOnly: true },
            //         { name: 'Invitations', path: '/users/invitations', count: 2 },
            //         { name: 'Permissions', path: '/users/permissions', superAdminOnly: true },
            //     ],
            // },
            {
                icon: MailIcon,
                name: 'Notifications',
                path: '/notifications',
                badge: '',
            },
        ],
    },
    {
        title: 'Autres',
        items: [
            // {
            //     icon: CalenderIcon,
            //     name: 'Calendrier',
            //     path: '/calendar',
            // },
            {
                icon: UserCircleIcon,
                name: 'Mon Profil',
                path: '/profile',
            },
        ],
    },
]);

// CORRECTION : Filtrer les menus selon les permissions avec sécurité
const filteredMenuGroups = computed(() => {
    if (!menuGroups.value) return [];

    return menuGroups.value.map(group => ({
        ...group,
        items: (group.items || []).filter(item => {
            if (!item) return false;

            // Si l'item a des subItems, on vérifie s'il en reste après filtrage
            if (item.subItems) {
                const filteredSubItems = getFilteredSubItems(item.subItems);
                return filteredSubItems.length > 0;
            }
            // Pour les items simples, on vérifie la permission
            return !item.superAdminOnly || isSuperAdmin.value;
        })
    })).filter(group => group.items && group.items.length > 0);
});

// CORRECTION : Filtrer les sous-items selon les permissions avec sécurité
const getFilteredSubItems = (subItems) => {
    if (!subItems || !Array.isArray(subItems)) return [];

    return subItems.filter(subItem => {
        if (!subItem) return false;
        return !subItem.superAdminOnly || isSuperAdmin.value;
    });
};


// OU encore mieux : une solution par route spécifique
const isActive = (path) => {
    // Exact match only. Prefix matching caused two links to be active
    // simultaneously (e.g. on /taches/22 both the "Toutes les tâches" subitem
    // with path /taches AND the actual route lit up). The parent dropdown is
    // opened separately via syncOpenSubmenuFromRoute, so prefix matching is
    // no longer needed.
    return route.path === path;
};

const toggleSubmenu = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isAnySubmenuRouteActive = computed(() => {
    if (!menuGroups.value) return false;

    return menuGroups.value.some((group) =>
        group.items?.some(
            (item) =>
                item.subItems &&
                item.subItems.some((subItem) => isActive(subItem.path))
        )
    );
});

const isSubmenuOpen = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    return openSubmenu.value === key;
};

// Auto-open the submenu that contains the active route (only on initial mount + route changes),
// so the openSubmenu ref stays the single source of truth and toggleSubmenu can always close it.
const syncOpenSubmenuFromRoute = () => {
    if (!menuGroups.value) return;
    for (let g = 0; g < menuGroups.value.length; g++) {
        const items = menuGroups.value[g]?.items ?? [];
        for (let i = 0; i < items.length; i++) {
            if (items[i].subItems?.some((s) => isActive(s.path))) {
                openSubmenu.value = `${g}-${i}`;
                return;
            }
        }
    }
};

watch(() => route.path, syncOpenSubmenuFromRoute);

const startTransition = (el) => {
    el.style.height = 'auto';
    const height = el.scrollHeight;
    el.style.height = '0px';
    el.offsetHeight;
    el.style.height = height + 'px';
};

const endTransition = (el) => {
    el.style.height = '';
};

onMounted(async () => {
    console.log('🚀 Montage du Sidebar');

    try {
        // Charger les workspaces
        if (workspaces.value.length === 0) {
            await fetchWorkspaces();
        }

        // Initialiser le workspace courant
        await initializeCurrentWorkspace();

        // Écouter les changements
        unsubscribeWorkspaceListener = onWorkspaceChanged(handleWorkspaceChange);

        console.log('✅ Sidebar initialisé, workspace courant:', currentWorkspace.value?.nom);

        // Open the submenu matching the current route on initial load
        syncOpenSubmenuFromRoute();
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation du sidebar:', error);
    }
});

onBeforeUnmount(() => {
    if (unsubscribeWorkspaceListener) {
        unsubscribeWorkspaceListener();
    }
});

</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Style pour la scrollbar du dropdown */
.max-h-60::-webkit-scrollbar {
    width: 4px;
}

.max-h-60::-webkit-scrollbar-track {
    background: transparent;
}

.max-h-60::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.dark .max-h-60::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .max-h-60::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>