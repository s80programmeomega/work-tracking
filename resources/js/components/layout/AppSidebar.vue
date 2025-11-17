<template>
    <aside
        :class="[
            'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
            {
                'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
                'lg:w-[90px]': !isExpanded && !isHovered,
                'translate-x-0 w-[290px]': isMobileOpen,
                '-translate-x-full': !isMobileOpen,
                'lg:translate-x-0': true,
            },
        ]"
        @mouseenter="!isExpanded && (isHovered = true)"
        @mouseleave="isHovered = false"
    >
        <!-- Logo Section (inchangé) -->
        <div
            :class="[
                'py-8 flex',
                !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start',
            ]"
        >
            <router-link to="/">
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="dark:hidden"
                    src="@images/logo/Logo.png"
                    alt="Logo"
                    width="150"
                    height="40"
                />
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="hidden dark:block"
                    src="@images/logo/Logo-dark.jpg"
                    alt="Logo"
                    width="150"
                    height="40"
                />
                <img
                    v-else
                    src="@images/logo/icon.jpg"
                    alt="Logo"
                    width="32"
                    height="32"
                />
            </router-link>
        </div>

        <!-- Workspace Selector (inchangé) -->
        <div
            v-if="isExpanded || isHovered || isMobileOpen"
            class="mb-4 px-2" >
            <button
                @click="showWorkspaceSelector = !showWorkspaceSelector"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" >
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-semibold text-sm">
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
                <ChevronDownIcon
                    :class="[
                        'w-4 h-4 text-gray-400 transition-transform',
                        { 'rotate-180': showWorkspaceSelector }
                    ]"
                />
            </button>
            
            <!-- Workspace Dropdown -->
            <transition name="fade-slide">
                <div
                    v-if="showWorkspaceSelector"
                    class="mt-2 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700" >
                    <div class="px-3 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                            Mes Workspaces
                        </p>
                    </div>
                    <button
                        v-for="workspace in workspaces"
                        :key="workspace.id"
                        @click="selectWorkspace(workspace)"
                        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" >
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-semibold text-sm">
                            {{ getWorkspaceInitials(workspace.nom) }}
                        </div>
                        <div class="flex-1 text-left">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ workspace.nom }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ workspace.projets_count || 0 }} projets
                            </p>
                        </div>
                        <CheckIcon
                            v-if="currentWorkspace?.id === workspace.id"
                            class="w-4 h-4 text-brand-500"
                        />
                    </button>
                    <div class="px-3 pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
                        <router-link
                            to="/workspaces/create"
                            class="flex items-center gap-2 text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Créer un workspace
                        </router-link>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Navigation Menu avec gestion des permissions -->
        <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
            <nav class="mb-6">
                <div class="flex flex-col gap-4">
                    <div
                        v-for="(menuGroup, groupIndex) in filteredMenuGroups"
                        :key="groupIndex"
                    >
                        <h2
                            :class="[
                                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                                !isExpanded && !isHovered
                                    ? 'lg:justify-center'
                                    : 'justify-start',
                            ]"
                        >
                            <template v-if="isExpanded || isHovered || isMobileOpen">
                                {{ menuGroup.title }}
                            </template>
                            <HorizontalDots v-else />
                        </h2>
                        <ul class="flex flex-col gap-1.5">
                            <li
                                v-for="(item, index) in menuGroup.items"
                                :key="item.name"
                            >
                                <!-- Item with submenu -->
                                <button
                                    v-if="item.subItems"
                                    @click="toggleSubmenu(groupIndex, index)"
                                    :class="[
                                        'menu-item group w-full',
                                        {
                                            'menu-item-active': isSubmenuOpen(groupIndex, index),
                                            'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                                        },
                                        !isExpanded && !isHovered
                                            ? 'lg:justify-center'
                                            : 'lg:justify-start',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            isSubmenuOpen(groupIndex, index)
                                                ? 'menu-item-icon-active'
                                                : 'menu-item-icon-inactive',
                                        ]"
                                    >
                                        <component :is="item.icon" />
                                    </span>
                                    <span
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        class="menu-item-text flex-1"
                                    >
                                        {{ item.name }}
                                    </span>
                                    <span
                                        v-if="item.badge && (isExpanded || isHovered || isMobileOpen)"
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400"
                                    >
                                        {{ item.badge }}
                                    </span>
                                    <ChevronDownIcon
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        :class="[
                                            'ml-2 w-4 h-4 transition-transform duration-200',
                                            {
                                                'rotate-180 text-brand-500': isSubmenuOpen(groupIndex, index),
                                            },
                                        ]"
                                    />
                                </button>

                                <!-- Simple link item -->
                                <router-link
                                    v-else-if="item.path"
                                    :to="item.path"
                                    :class="[
                                        'menu-item group',
                                        {
                                            'menu-item-active': isActive(item.path),
                                            'menu-item-inactive': !isActive(item.path),
                                        },
                                    ]"
                                >
                                    <span
                                        :class="[
                                            isActive(item.path)
                                                ? 'menu-item-icon-active'
                                                : 'menu-item-icon-inactive',
                                        ]"
                                    >
                                        <component :is="item.icon" />
                                    </span>
                                    <span
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        class="menu-item-text flex-1"
                                    >
                                        {{ item.name }}
                                    </span>
                                    <span
                                        v-if="item.badge && (isExpanded || isHovered || isMobileOpen)"
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </router-link>

                                <!-- Submenu items avec permissions -->
                                <transition
                                    @enter="startTransition"
                                    @after-enter="endTransition"
                                    @before-leave="startTransition"
                                    @after-leave="endTransition"
                                >
                                    <div
                                        v-show="
                                            isSubmenuOpen(groupIndex, index) &&
                                            (isExpanded || isHovered || isMobileOpen)
                                        "
                                    >
                                        <ul class="mt-1 space-y-1 ml-9">
                                            <li
                                                v-for="subItem in getFilteredSubItems(item.subItems)"
                                                :key="subItem.name"
                                            >
                                                <router-link
                                                    :to="subItem.path"
                                                    :class="[
                                                        'menu-dropdown-item',
                                                        {
                                                            'menu-dropdown-item-active': isActive(subItem.path),
                                                            'menu-dropdown-item-inactive': !isActive(subItem.path),
                                                        },
                                                    ]"
                                                >
                                                    <span class="flex-1">{{ subItem.name }}</span>
                                                    <span class="flex items-center gap-1 ml-auto">
                                                        <span
                                                            v-if="subItem.new"
                                                            :class="[
                                                                'menu-dropdown-badge',
                                                                {
                                                                    'menu-dropdown-badge-active': isActive(subItem.path),
                                                                    'menu-dropdown-badge-inactive': !isActive(subItem.path),
                                                                },
                                                            ]"
                                                        >
                                                            new
                                                        </span>
                                                        <span
                                                            v-if="subItem.count"
                                                            class="px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                                        >
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
        <div
            v-if="isExpanded || isHovered || isMobileOpen"
            class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2"
        >
            <router-link
                to="/settings"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
                <SettingsIcon class="w-5 h-5" />
                <span>Paramètres</span>
            </router-link>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
    GridIcon,
    CalenderIcon,
    UserCircleIcon,
    ChatIcon,
    MailIcon,
    ChevronDownIcon,
    HorizontalDots,
    ListIcon,
} from '../../icons';
import BoxCubeIcon from '@/icons/BoxCubeIcon.vue';
import CheckIcon from '@/icons/CheckIcon.vue';
import PlusIcon from '@/icons/PlusIcon.vue';
import SettingsIcon from '@/icons/SettingsIcon.vue';
import FolderIcon from '@/icons/FolderIcon.vue';
import TaskIcon from '@/icons/TaskIcon.vue';
import ClipboardCheckIcon from '@/icons/ClipboardCheckIcon.vue';
import UsersIcon from '@/icons/UsersIcon.vue';
import { useSidebar } from '@/composables/useSidebar';
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth';  

const route = useRoute();
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();
const authStore = useAuthStore();

// Workspace management
const showWorkspaceSelector = ref(false);
const currentWorkspace = ref(null);
const workspaces = ref([]);
const loading = ref(false);

// CORRECTION : Utiliser le getter isSuperAdmin du store
const isSuperAdmin = computed(() => {
    return authStore.isSuperAdmin;
});

const currentWorkspaceInitials = computed(() => {
    if (!currentWorkspace.value) return 'MW';
    return getWorkspaceInitials(currentWorkspace.value.nom);
});

const workspaceProjectCount = computed(() => {
    return currentWorkspace.value?.projets_count || 0;
});

const getWorkspaceInitials = (name) => {
    return name
        ?.split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) || 'MW';
};

const selectWorkspace = async (workspace) => {
    try {
        const response = await api.post(`/workspaces/switch/${workspace.id}`, {}, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });

        currentWorkspace.value = response.data.workspace;
        authStore.setCurrentWorkspace(response.data.current_workspace_id);
        showWorkspaceSelector.value = false;

        window.dispatchEvent(new CustomEvent('workspace-changed', {
            detail: { workspace }
        }));

    } catch (error) {
        console.error('Erreur lors du changement de workspace :', error);
    }
};

// Menu structure
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
                    { name: 'Créer un projet', path: '/projets/create', new: true },
                ],
            },
            {
                icon: ListIcon,
                name: 'Activités',
                subItems: [
                    { name: 'Toutes les activités', path: '/activites/all/activity', superAdminOnly: true },
                    { name: 'Mes activités', path: '/activites/mes-activites' },
                    { name: 'En retard', path: '/activites/en-retard', count: 5 },
                ],
            },
            {
                icon: TaskIcon,
                name: 'Tâches',
                subItems: [
                    { name: 'Toutes les tâches', path: '/taches' },
                    { name: 'Mes tâches', path: '/taches/mes-taches' },
                    { name: 'Assignées à moi', path: '/taches/assignees' },
                    { name: 'En attente', path: '/taches/en-attente', count: 12 },
                    { name: 'En retard', path: '/taches/en-retard', count: 3 },
                ],
            },
        ],
    },
    {
        title: 'Évaluation & Validation',
        items: [
            {
                icon: ClipboardCheckIcon,
                name: 'Validations',
                badge: '8',
                subItems: [
                    { name: 'En attente N1', path: '/validations/n1', count: 5 },
                    { name: 'En attente N2', path: '/validations/n2', count: 3 },
                    { name: 'Historique', path: '/validations/historique' },
                ],
            },
            {
                icon: ClipboardCheckIcon,
                name: 'Évaluations',
                subItems: [
                    { name: 'Tableau de bord', path: '/evaluations/dashboard' },
                    { name: 'Rapport hebdomadaire', path: '/evaluations/rapport-hebdomadaire' },
                    { name: 'Fiches d\'évaluation', path: '/evaluations/fiches' },
                    { name: 'Performance d\'équipe', path: '/evaluations/performance' },
                ],
            },
        ],
    },
    {
        title: 'Collaboration',
        items: [
            {
                icon: ChatIcon,
                name: 'Équipes',
                subItems: [
                    { name: 'Mes équipes', path: '/teams' },
                    { name: 'Messages', path: '/teams/messages', count: 24 },
                    { name: 'Annonces', path: '/teams/announcements' },
                    { name: 'Ressources', path: '/teams/resources' },
                ],
            },
            {
                icon: UsersIcon,
                name: 'Utilisateurs',
                subItems: [
                    { name: 'Tous les utilisateurs', path: '/users', superAdminOnly: true },
                    { name: 'Invitations', path: '/users/invitations', count: 2 },
                    { name: 'Permissions', path: '/users/permissions', superAdminOnly: true },
                ],
            },
            {
                icon: MailIcon,
                name: 'Notifications',
                path: '/notifications',
                badge: '15',
            },
        ],
    },
    {
        title: 'Autres',
        items: [
            {
                icon: CalenderIcon,
                name: 'Calendrier',
                path: '/calendar',
            },
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

const isActive = (path) => {
    return route.path === path || route.path.startsWith(path + '/');
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
    
    if (!menuGroups.value[groupIndex]?.items?.[itemIndex]) return false;
    
    return (
        openSubmenu.value === key ||
        (isAnySubmenuRouteActive.value &&
            menuGroups.value[groupIndex].items[itemIndex].subItems?.some((subItem) =>
                isActive(subItem.path)
            ))
    );
};

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
    try {
        const response = await api.get('/workspaces/user-workspaces', {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });
        workspaces.value = response.data.data || [];
        
        // CORRECTION : Gestion sécurisée du workspace courant
        const currentWorkspaceId = authStore.user?.current_workspace_id;
        currentWorkspace.value = workspaces.value.find(w => w.id === currentWorkspaceId) || workspaces.value[0] || null;
        
    } catch (error) {
        console.error('Erreur lors du chargement des workspaces :', error);
        workspaces.value = [];
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
</style>