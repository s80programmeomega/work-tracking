<!-- resources\js\components\layout\AppSidebar.vue -->
<template>
    <aside
        dusk="app-sidebar"
        :class="[
            'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 text-gray-900 dark:text-white h-screen transition-all duration-300 ease-in-out z-40 border-r border-gray-200 dark:border-gray-800',
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
        <!-- Logo Section -->
        <div
            :class="[
                'py-8 flex',
                !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start',
            ]"
        >
            <router-link to="/">
                <template v-if="isExpanded || isHovered || isMobileOpen">
                    <img :src="Logo" alt="Logo" width="150" height="40" />
                </template>
                <template v-else>
                    <img :src="Icon" alt="Logo" width="32" height="32" />
                </template>
            </router-link>
        </div>

        <!-- Workspace Selector — only shown when user has at least one workspace -->
        <div
            v-if="
                (isExpanded || isHovered || isMobileOpen) &&
                workspaces.length > 0
            "
            class="mb-4 px-2"
        >
            <button
                dusk="workspace-selector-btn"
                @click="showWorkspaceSelector = !showWorkspaceSelector"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
                <div
                    class="w-8 h-8 rounded-lg bg-[#1E3A5F] flex items-center justify-center text-white font-semibold text-sm"
                >
                    {{ currentWorkspaceInitials }}
                </div>
                <div class="flex-1 text-left overflow-hidden">
                    <p
                        class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                    >
                        {{ currentWorkspace?.nom || $t('sidebar.my_workspace') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('workspaces.projects') }}: {{ workspaceProjectCount }}
                    </p>
                </div>
                <ChevronDownIcon
                    :class="[
                        'w-4 h-4 text-gray-400 transition-transform',
                        { 'rotate-180': showWorkspaceSelector },
                    ]"
                />
            </button>

            <!-- Workspace Dropdown avec Filtre Dashboard -->
            <transition name="fade-slide">
                <div
                    v-if="showWorkspaceSelector"
                    class="mt-2 py-2 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700"
                >
                    <!-- Section Filtre Dashboard -->
                    <div
                        class="px-3 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700"
                    >
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"
                        >
                            {{ $t('sidebar.dashboard_filter') }}
                        </p>
                        <button
                            @click="selectWorkspaceForDashboard('all')"
                            :class="[
                                'w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs font-medium transition-colors mb-1',
                                selectedDashboardWorkspace === 'all'
                                    ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <GlobeIcon class="w-3 h-3" />
                            <span>{{ $t('sidebar.all_workspaces') }}</span>
                            <CheckIcon
                                v-if="selectedDashboardWorkspace === 'all'"
                                class="w-3 h-3 ml-auto"
                            />
                        </button>
                    </div>

                    <!-- Section Mes Workspaces -->
                    <div
                        class="px-3 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700"
                    >
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"
                        >
                            {{ $t('sidebar.my_workspaces') }}
                        </p>
                    </div>

                    <div class="max-h-60 overflow-y-auto">
                        <!-- CORRECTION : Utiliser filteredWorkspaces et vérifier null -->
                        <button
                            v-for="workspace in filteredWorkspaces"
                            :key="workspace?.id || 'null'"
                            @click="handleSelectWorkspace(workspace)"
                            class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group"
                        >
                            <div
                                class="w-8 h-8 rounded-lg bg-[#1E3A5F] flex items-center justify-center text-white font-semibold text-sm"
                            >
                                {{ getWorkspaceInitials(workspace?.nom) }}
                            </div>
                            <div class="flex-1 text-left">
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    {{ workspace?.nom || $t('sidebar.workspace_unknown') }}
                                </p>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('workspaces.projects') }}: {{ workspace?.projets_count || 0 }}
                                </p>
                            </div>

                            <!-- Indicateur workspace actuel -->
                            <CheckIcon
                                v-if="currentWorkspace?.id === workspace?.id"
                                class="w-4 h-4 text-brand-500"
                            />

                            <!-- Bouton filtre dashboard pour ce workspace -->
                            <div
                                role="button"
                                tabindex="0"
                                @click.stop="
                                    selectWorkspaceForDashboard(workspace?.id)
                                "
                                :class="[
                                    'p-1 rounded transition-colors cursor-pointer',
                                    selectedDashboardWorkspace === workspace?.id
                                        ? 'bg-brand-500 text-white'
                                        : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-brand-500 hover:text-white',
                                ]"
                                :title="
                                    selectedDashboardWorkspace === workspace?.id
                                        ? $t('sidebar.filter_active')
                                        : $t('sidebar.filter_dashboard')
                                "
                            >
                                <FilterIcon class="w-3 h-3" />
                            </div>
                        </button>
                    </div>

                    <!-- Actions -->
                    <div
                        class="px-3 pt-2 mt-2 border-t border-gray-200 dark:border-gray-700 space-y-2"
                    >
                        <router-link
                            v-if="isSuperAdmin"
                            to="/admin/workspaces"
                            dusk="view-all-workspaces-btn"
                            class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
                        >
                            <span>{{ $t('sidebar.view_all_workspaces') }}</span>
                            <ArrowRightIcon class="w-3 h-3 ml-auto" />
                        </router-link>

                        <router-link
                            to="/workspaces/select"
                            dusk="switch-workspace-btn"
                            class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
                        >
                            <SwitchIcon class="w-4 h-4" />
                            {{ $t('sidebar.switch_workspace') }}
                        </router-link>

                        <router-link
                            to="/workspaces/create"
                            class="flex items-center gap-2 text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" />
                            {{ $t('sidebar.create_workspace') }}
                        </router-link>

                        <!-- Indicateur filtre actif -->
                        <div
                            v-if="
                                selectedDashboardWorkspace &&
                                selectedDashboardWorkspace !== 'all'
                            "
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded"
                        >
                            <FilterIcon class="w-3 h-3" />
                            <span>{{ $t('sidebar.dashboard_filtered') }}</span>
                            <button
                                @click="clearDashboardFilter"
                                class="ml-auto text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300"
                                :title="$t('sidebar.clear_filter')"
                            >
                                <XIcon class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Navigation Menu avec gestion des permissions -->
        <div
            class="flex flex-col flex-1 overflow-y-auto duration-300 ease-linear"
        >
            <nav dusk="sidebar-nav" class="mb-6" @click.capture="handleNavClick">
                <div class="flex flex-col gap-4">
                    <div
                        v-for="(menuGroup, groupIndex) in filteredMenuGroups"
                        :key="groupIndex"
                    >
                        <div
                            v-if="groupIndex > 0"
                            class="border-t border-gray-200 dark:border-gray-700 my-3"
                        ></div>
                        <h2
                            :class="[
                                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                                !isExpanded && !isHovered
                                    ? 'lg:justify-center'
                                    : 'justify-start',
                            ]"
                        >
                            <template
                                v-if="isExpanded || isHovered || isMobileOpen"
                            >
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
                                            'menu-item-active': isSubmenuOpen(
                                                groupIndex,
                                                index,
                                            ),
                                            'menu-item-inactive':
                                                !isSubmenuOpen(
                                                    groupIndex,
                                                    index,
                                                ),
                                        },
                                        !isExpanded && !isHovered
                                            ? 'lg:justify-center'
                                            : 'justify-between',
                                    ]"
                                >
                                    <div class="flex flex-row gap-3">
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
                                            v-if="
                                                isExpanded ||
                                                isHovered ||
                                                isMobileOpen
                                            "
                                            class="menu-item-text flex-1"
                                        >
                                            {{ item.name }}
                                        </span>
                                    </div>
                                    <span
                                        v-if="
                                            item.badge &&
                                            (isExpanded ||
                                                isHovered ||
                                                isMobileOpen)
                                        "
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400"
                                    >
                                        {{ item.badge }}
                                    </span>
                                    <ChevronDownIcon
                                        v-if="
                                            isExpanded ||
                                            isHovered ||
                                            isMobileOpen
                                        "
                                        :class="[
                                            'ml-2 w-4 h-4 transition-transform duration-200',
                                            {
                                                'rotate-180 text-brand-500':
                                                    isSubmenuOpen(
                                                        groupIndex,
                                                        index,
                                                    ),
                                            },
                                        ]"
                                    />
                                </button>

                                <!-- Lien externe (ouvre dans un nouvel onglet) -->
                                <a
                                    v-else-if="item.href"
                                    :href="typeof item.href === 'function' ? item.href() : item.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    :class="[
                                        'menu-item group menu-item-inactive',
                                        !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
                                    ]"
                                >
                                    <span class="menu-item-icon-inactive">
                                        <component :is="item.icon" />
                                    </span>
                                    <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text flex-1">
                                        {{ item.name }}
                                    </span>
                                </a>

                                <!-- Simple link item -->
                                <router-link
                                    v-else-if="item.path"
                                    :to="item.path"
                                    :class="[
                                        'menu-item group',
                                        {
                                            'menu-item-active': isActive(
                                                item.path,
                                            ),
                                            'menu-item-inactive': !isActive(
                                                item.path,
                                            ),
                                        },
                                        !isExpanded && !isHovered
                                            ? 'lg:justify-center'
                                            : 'justify-start',
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
                                        v-if="
                                            isExpanded ||
                                            isHovered ||
                                            isMobileOpen
                                        "
                                        class="menu-item-text flex-1"
                                    >
                                        {{ item.name }}
                                    </span>
                                    <span
                                        v-if="
                                            item.badge &&
                                            (isExpanded ||
                                                isHovered ||
                                                isMobileOpen)
                                        "
                                        class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </router-link>

                                <!-- Submenu items avec permissions -->
                                <transition
                                    @enter="startTransition"
                                    @after-enter="endTransition"
                                    @leave="leaveTransition"
                                >
                                    <div
                                        class="submenu-collapse"
                                        v-show="
                                            isSubmenuOpen(groupIndex, index) &&
                                            (isExpanded ||
                                                isHovered ||
                                                isMobileOpen)
                                        "
                                    >
                                        <ul class="mt-1 space-y-1 ml-9">
                                            <li
                                                v-for="subItem in getFilteredSubItems(
                                                    item.subItems,
                                                )"
                                                :key="subItem.name"
                                            >
                                                <router-link
                                                    :to="subItem.path"
                                                    :class="[
                                                        'menu-dropdown-item',
                                                        {
                                                            'menu-dropdown-item-active':
                                                                isActive(
                                                                    subItem.path,
                                                                ),
                                                            'menu-dropdown-item-inactive':
                                                                !isActive(
                                                                    subItem.path,
                                                                ),
                                                        },
                                                    ]"
                                                >
                                                    <span class="flex-1">{{
                                                        subItem.name
                                                    }}</span>
                                                    <span
                                                        class="flex items-center gap-1 ml-auto"
                                                    >
                                                        <span
                                                            v-if="subItem.new"
                                                            :class="[
                                                                'menu-dropdown-badge',
                                                                {
                                                                    'menu-dropdown-badge-active':
                                                                        isActive(
                                                                            subItem.path,
                                                                        ),
                                                                    'menu-dropdown-badge-inactive':
                                                                        !isActive(
                                                                            subItem.path,
                                                                        ),
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

            <!-- Bottom Actions -->
            <div
                v-if="isExpanded || isHovered || isMobileOpen"
                class="pt-4 pb-6 border-t border-gray-200 dark:border-gray-700 space-y-2"
                @click.capture="handleNavClick"
            >
                <router-link
                    v-if="canManageSettings || isSuperAdmin"
                    :to="
                        currentWorkspace?.id
                            ? {
                                  name: 'workspaces.settings',
                                  params: { id: currentWorkspace.id },
                              }
                            : '/workspaces'
                    "
                    :class="[
                        'menu-item menu-item-inactive group',
                        !isExpanded && !isHovered
                            ? 'lg:justify-center'
                            : 'justify-start',
                    ]"
                >
                    <SettingsIcon
                        class="menu-item-icon-inactive w-5 h-5 shrink-0"
                    />
                    <span class="menu-item-text flex-1">{{ $t('navigation.settings') }}</span>
                </router-link>
                <router-link
                    v-if="
                        (canManageSubscription || isSuperAdmin) &&
                        currentWorkspace?.id
                    "
                    :to="{ name: 'subscription.plans' }"
                    :class="[
                        'menu-item menu-item-inactive group',
                        !isExpanded && !isHovered
                            ? 'lg:justify-center'
                            : 'justify-start',
                    ]"
                >
                    <svg
                        class="menu-item-icon-inactive w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                    <span class="menu-item-text flex-1">{{ $t('navigation.subscription') }}</span>
                </router-link>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, h } from "vue";
import { useRoute, useRouter } from "vue-router";
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
    XIcon,
    StarIcon,
    ArrowRightIcon,
} from "../../icons";

import BoxCubeIcon from "@/icons/BoxCubeIcon.vue";
import FolderIcon from "@/icons/FolderIcon.vue";
import SupportIcon from "@/icons/SupportIcon.vue";
import TaskIcon from "@/icons/TaskIcon.vue";
import ClipboardCheckIcon from "@/icons/ClipboardCheckIcon.vue";
import UsersIcon from "@/icons/UsersIcon.vue";
import ShieldIcon from "@/icons/ShieldIcon.vue";
import BuildingOfficeIcon from "@/icons/BuildingOfficeIcon.vue";
import DocsIcon from "@/icons/DocsIcon.vue";
import { useI18n } from "vue-i18n";
import { useSidebar } from "@/composables/useSidebar";
import api from "@/api/axios";
import { useAuthStore } from "@/stores/authStore";
import { useWorkspace } from "@/composables/useWorkspace";
import { useWorkspacePermissions } from "@/composables/useWorkspacePermissions";

const Logo = new URL("@/assets/images/logo/logo-transparent.png", import.meta.url).href;
const Icon = new URL("@/assets/images/logo/icon-transparent.png", import.meta.url).href;

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { isExpanded, isMobileOpen, isHovered, openSubmenu, toggleMobileSidebar } = useSidebar();
const authStore = useAuthStore();

// ✅ Utiliser le composable workspace
const {
    currentWorkspace,
    currentWorkspaceId,
    workspaces,
    loading: workspaceLoading,
    selectWorkspace,
    fetchWorkspaces,
    fetchWorkspace,
    onWorkspaceChanged,
    initializeCurrentWorkspace,
} = useWorkspace();

const {
    canManageSettings,
    canManageSubscription,
    canViewPendingValidations,
    canViewEvaluationScore,
    canViewFicheEvaluation,
    canViewAllTasks,
    canSubmitResult,
    canViewEvaluationDashboard,
    canViewWorkspaceTaches,
    canReadHelpArticles,
    canViewMembers,
} = useWorkspacePermissions(currentWorkspace);

// Workspace management
const showWorkspaceSelector = ref(false);
// const currentWorkspace = ref(null);
// const workspaces = ref([]);
// const loading = ref(false);

const selectedDashboardWorkspace = ref("all"); // 'all' ou workspace_id

// Badge de messages non lus dans les équipes
const totalUnreadChat = ref(0)
const fetchUnreadChat = async () => {
    try {
        const { data } = await api.get('/teams/unread-total')
        totalUnreadChat.value = data.total ?? 0
    } catch {
        totalUnreadChat.value = 0
    }
}

// CORRECTION : Utiliser le getter isSuperAdmin du store
const isSuperAdmin = computed(() => {
    return authStore.isSuperAdmin;
});

// Affichage contextuel des workspaces dans la sidebar selon le rôle et le contexte
const filteredWorkspaces = computed(() => {
    const all = (workspaces.value || []).filter((w) => w?.id);

    // Règle 1 : super_admin → 3 premiers workspaces uniquement
    if (isSuperAdmin.value) {
        return all.slice(0, 3);
    }

    const userId = authStore.user?.id;

    // Règle 3 : non-propriétaire du workspace courant → workspace courant uniquement
    if (currentWorkspace.value && currentWorkspace.value.owner_id !== userId) {
        return all.filter((w) => w.id === currentWorkspace.value?.id);
    }

    // Règle 2 : propriétaire → tous les workspaces dont l'utilisateur est propriétaire
    return all.filter((w) => w.owner_id === userId);
});

// Icônes supplémentaires pour le filtre — définies en fonctions de rendu
// (h()) plutôt qu'avec template:'<svg…>' car le build de Vue utilisé par
// Vite est runtime-only et ne sait pas compiler un template à la volée.
const svgAttrs = {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    "stroke-width": 2,
    "stroke-linecap": "round",
    "stroke-linejoin": "round",
};

const FilterIcon = {
    props: ["className"],
    render() {
        return h("svg", { ...svgAttrs, class: this.className }, [
            h("polygon", {
                points: "22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3",
            }),
        ]);
    },
};

const GlobeIcon = {
    props: ["className"],
    render() {
        return h("svg", { ...svgAttrs, class: this.className }, [
            h("circle", { cx: 12, cy: 12, r: 10 }),
            h("line", { x1: 2, y1: 12, x2: 22, y2: 12 }),
            h("path", {
                d: "M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z",
            }),
        ]);
    },
};

const SwitchIcon = {
    render() {
        return h("svg", { ...svgAttrs }, [
            h("path", { d: "M7 16V4m0 0L3 8m4-4l4 4" }),
            h("path", { d: "M17 8v12m0 0l4-4m-4 4l-4-4" }),
        ]);
    },
};

const currentWorkspaceInitials = computed(() => {
    if (!currentWorkspace.value) return "MW";
    return getWorkspaceInitials(currentWorkspace.value.nom);
});

const workspaceProjectCount = computed(() => {
    return (
        currentWorkspace.value?.projets_count ??
        currentWorkspace.value?.projet_count ??
        0
    );
});

const getWorkspaceInitials = (name) => {
    return (
        name
            ?.split(" ")
            .map((word) => word[0])
            .join("")
            .toUpperCase()
            .slice(0, 2) || "MW"
    );
};

// ✅ Fonction de sélection de workspace
const handleSelectWorkspace = async (workspace) => {
    if (currentWorkspace.value?.id === workspace.id) {
        console.log("Sidebar: Même workspace, aucune action");
        showWorkspaceSelector.value = false;
        return;
    }

    console.log("Sidebar: Changement de workspace vers:", workspace.nom);

    try {
        await selectWorkspace(workspace);
        showWorkspaceSelector.value = false;
        console.log("✅ Sidebar: Workspace changé avec succès");
    } catch (error) {
        console.error(
            "❌ Sidebar: Erreur lors du changement de workspace:",
            error,
        );
    }
};

// ✅ Fonction pour filtrer le dashboard
const selectWorkspaceForDashboard = (workspaceId) => {
    console.log("🔄 Filtrage dashboard pour workspace:", workspaceId);

    selectedDashboardWorkspace.value = workspaceId;

    // Émettre un événement pour le dashboard
    window.dispatchEvent(
        new CustomEvent("dashboard-filter-changed", {
            detail: { workspaceId },
        }),
    );

    // Si on est sur le dashboard, recharger les données
    if (route.path === "/") {
        router.go(0); // Rechargement simple
    }

    showWorkspaceSelector.value = false;
};

// ✅ Effacer le filtre dashboard
const clearDashboardFilter = () => {
    selectedDashboardWorkspace.value = "all";
    window.dispatchEvent(
        new CustomEvent("dashboard-filter-changed", {
            detail: { workspaceId: "all" },
        }),
    );
};

// ✅ Écoute des changements externes
let unsubscribeWorkspaceListener = null;

const handleWorkspaceChange = (event) => {
    console.log("Sidebar: Changement externe détecté", event.detail);
    // Le currentWorkspace est déjà mis à jour par le composable
    // Fermer le dropdown si ouvert
    showWorkspaceSelector.value = false;
};

const menuGroups = computed(() => [
    {
        title: t('sidebar.principal'),
        items: [
            {
                icon: GridIcon,
                name: t('navigation.dashboard'),
                path: "/",
            },
            {
                icon: BoxCubeIcon,
                name: t('navigation.workspaces'),
                path: "/workspaces",
            },
        ],
    },
    {
        title: t('sidebar.project_management'),
        items: [
            {
                icon: FolderIcon,
                name: t('navigation.projects'),
                subItems: [
                    {
                        name: t('sidebar.all_projects'),
                        path: "/projets/list/all",
                        superAdminOnly: true,
                    },
                    { name: t('sidebar.my_projects'), path: "/projets/mes-projets", superAdminHidden: true },
                    { name: t('sidebar.archived_projects'), path: "/projets/archives" },
                ],
            },
            {
                icon: ListIcon,
                name: t('navigation.activities'),
                subItems: [
                    {
                        name: t('sidebar.all_activities'),
                        path: "/activites/all/activity",
                        superAdminOnly: true,
                    },
                    { name: t('sidebar.my_activities'), path: "/activites/mes-activites", superAdminHidden: true },
                ],
            },
            // ==================== MISE À JOUR DE AppSidebar.vue ====================

            // {
            //     icon: TaskIcon,
            //     name: t('navigation.tasks'),
            //     subItems: [
            //         {
            //             name: t('sidebar.all_tasks'),
            //             path: '/taches',
            //             superAdminOnly: false
            //         },
            //         ...
            //     ],
            // },

            // ==================== ALTERNATIVE : Groupement par rôle ====================

            {
                icon: TaskIcon,
                name: t('navigation.tasks'),
                subItems: [
                    {
                        name: t('sidebar.all_tasks'),
                        path: "/taches",
                        requiresPermission: "canViewAllTasks",
                    },
                    {
                        name: t('sidebar.as_responsible'),
                        path: "/taches/responsable",
                        icon: "👑",
                        badge: "new",
                        superAdminHidden: true,
                    },
                    {
                        name: t('sidebar.as_intervenant'),
                        path: "/taches/assignees",
                        icon: "👤",
                        superAdminHidden: true,
                    },
                    {
                        name: t('sidebar.my_pending_validations'),
                        path: "/mes-validations",
                        icon: "⏳",
                        requiresPermission: "canSubmitResult",
                        superAdminHidden: true,
                    },
                ],
            },
        ],
    },
    {
        title: t('sidebar.evaluation_validation'),
        items: [
            {
                icon: ClipboardCheckIcon,
                name: t('navigation.evaluations'),
                subItems: [
                    {
                        name: t('sidebar.evaluation_dashboard'),
                        path: "/evaluations/tableau-de-bord",
                        requiresPermission: "canViewEvaluationDashboard",
                    },
                    {
                        name: t('sidebar.validations_to_process'),
                        path: "/validations/a-traiter",
                        requiresPermission: "canViewPendingValidations",
                    },
                    {
                        name: t('sidebar.evaluation_sheets'),
                        path: "/evaluations/fiches",
                        requiresPermission: "canViewFicheEvaluation",
                    },
                    {
                        name: t('sidebar.workspace_tasks'),
                        path: "/workspace/taches",
                        requiresPermission: "canViewWorkspaceTaches",
                    },
                    {
                        name: t('sidebar.workspace_users'),
                        path: "/workspace/users",
                        requiresPermission: "canViewMembers",
                    },
                ],
            },
        ],
    },
    {
        title: t('sidebar.collaboration'),
        items: [
            {
                icon: ChatIcon,
                name: t('navigation.teams'),
                badge: totalUnreadChat.value > 0 ? String(totalUnreadChat.value > 99 ? '99+' : totalUnreadChat.value) : '',
                subItems: [
                    { name: t('sidebar.my_teams'), path: "/teams", superAdminHidden: true },
                ],
            },
            {
                icon: UsersIcon,
                name: t('navigation.users'),
                subItems: [
                    { name: t('sidebar.invitations'), path: "/users/invitations" },
                ],
            },
            {
                icon: MailIcon,
                name: t('navigation.notifications'),
                path: "/notifications",
                badge: "",
            },
            {
                icon: SupportIcon,
                name: t('sidebar.support'),
                path: "/support",
            },
        ],
    },
    {
        title: t('sidebar.docs_resources'),
        items: [
            {
                icon: FolderIcon,
                name: t('navigation.documents'),
                subItems: [
                    { name: t('sidebar.all_documents'), path: "/documents" },
                    { name: t('help.nav'), path: "/help" },
                ],
            },
        ],
    },
    {
        title: t('sidebar.administration'),
        superAdminOnly: true,
        items: [
            {
                icon: ShieldIcon,
                name: t('navigation.platform_dashboard'),
                path: "/admin/dashboard",
                superAdminOnly: true,
            },
            {
                icon: BuildingOfficeIcon,
                name: t('navigation.workspaces'),
                path: "/admin/workspaces",
                superAdminOnly: true,
            },
            {
                icon: BoxCubeIcon,
                name: t('navigation.plans'),
                path: "/admin/plans",
                superAdminOnly: true,
            },
            {
                icon: UsersIcon,
                name: t('navigation.users'),
                path: "/admin/users",
                superAdminOnly: true,
            },
            {
                icon: ShieldIcon,
                name: t('navigation.roles_permissions'),
                path: "/admin/roles",
                superAdminOnly: true,
            },
            {
                icon: DocsIcon,
                name: t('sidebar.logs'),
                path: '/admin/logs',
                superAdminOnly: true,
            },
        ],
    },
    {
        title: t('sidebar.other'),
        items: [
            {
                icon: UserCircleIcon,
                name: t('navigation.profile'),
                path: "/profile",
                superAdminHidden: true,
            },
        ],
    },
]);

// CORRECTION : Filtrer les menus selon les permissions avec sécurité
const filteredMenuGroups = computed(() => {
    if (!menuGroups.value) return [];

    return menuGroups.value
        .filter((group) => {
            if (!group) return false;
            if (group.superAdminOnly && !isSuperAdmin.value) return false;
            if (group.superAdminHidden && isSuperAdmin.value) return false;
            return true;
        })
        .map((group) => ({
            ...group,
            items: (group.items || []).filter((item) => {
                if (!item) return false;
                if (item.superAdminHidden && isSuperAdmin.value) return false;

                // Si l'item a des subItems, on vérifie s'il en reste après filtrage
                if (item.subItems) {
                    const filteredSubItems = getFilteredSubItems(item.subItems);
                    return filteredSubItems.length > 0;
                }
                // Pour les items simples, on vérifie la permission
                return !item.superAdminOnly || isSuperAdmin.value;
            }),
        }))
        .filter((group) => group.items && group.items.length > 0);
});

// Filtrer les sous-items selon les permissions avec sécurité
const permissionMap = computed(() => ({
    canViewAllTasks: canViewAllTasks.value,
    canSubmitResult: canSubmitResult.value,
    canViewPendingValidations: canViewPendingValidations.value,
    canViewEvaluationScore: canViewEvaluationScore.value,
    canViewFicheEvaluation: canViewFicheEvaluation.value,
    canViewEvaluationDashboard: canViewEvaluationDashboard.value,
    canViewWorkspaceTaches: canViewWorkspaceTaches.value,
    canReadHelpArticles: canReadHelpArticles.value,
    canViewMembers: canViewMembers.value,
    isSuperAdmin: isSuperAdmin.value,
}));

const getFilteredSubItems = (subItems) => {
    if (!subItems || !Array.isArray(subItems)) return [];

    return subItems.filter((subItem) => {
        if (!subItem) return false;
        if (subItem.superAdminOnly && !isSuperAdmin.value) return false;
        if (subItem.superAdminHidden && isSuperAdmin.value) return false;
        if (subItem.requiresPermission) {
            return permissionMap.value[subItem.requiresPermission] ?? false;
        }
        return true;
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
                item.subItems.some((subItem) => isActive(subItem.path)),
        ),
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

const handleNavClick = (event) => {
    if (!isMobileOpen.value) return;
    const link = event.target.closest('a');
    if (link) {
        toggleMobileSidebar();
    }
};

// Ouverture : 0 → hauteur réelle. La transition CSS (.submenu-collapse) anime.
const startTransition = (el) => {
    el.style.height = '0px';
    requestAnimationFrame(() => {
        el.style.height = el.scrollHeight + 'px';
    });
};

// Fin d'ouverture : libère la hauteur fixe (laisse le contenu se redimensionner).
const endTransition = (el) => {
    el.style.height = '';
};

// Fermeture : hauteur réelle → 0 (sens inverse). On fige d'abord la hauteur
// courante puis on la ramène à 0 au frame suivant pour que la transition CSS joue.
const leaveTransition = (el, done) => {
    el.style.height = el.scrollHeight + 'px';
    requestAnimationFrame(() => {
        el.style.height = '0px';
    });
    // Laisse la transition CSS (300ms) se terminer avant que Vue retire l'élément.
    el.addEventListener('transitionend', done, { once: true });
};

onMounted(async () => {
    console.log("🚀 Montage du Sidebar");

    try {
        // initializeCurrentWorkspace handles fetchWorkspaces internally if needed
        await initializeCurrentWorkspace();

        // Refresh full workspace detail for up-to-date permissions (non-blocking)
        if (currentWorkspace.value?.id) {
            fetchWorkspace(currentWorkspace.value.id)
                .then((fresh) => { if (fresh) authStore.currentWorkspace = fresh; })
                .catch(() => {});
        }

        unsubscribeWorkspaceListener = onWorkspaceChanged(handleWorkspaceChange);

        console.log(
            "✅ Sidebar initialisé, workspace courant:",
            currentWorkspace.value?.nom,
        );

        syncOpenSubmenuFromRoute();

        // Non-blocking — don't delay render for unread badge
        fetchUnreadChat();
    } catch (error) {
        console.error("❌ Erreur lors de l'initialisation du sidebar:", error);
    }
});

onBeforeUnmount(() => {
    if (unsubscribeWorkspaceListener) {
        unsubscribeWorkspaceListener();
    }
});
</script>

<style scoped>
/* Sous-menu repliable : les hooks JS startTransition/endTransition règlent
   la hauteur (0 → scrollHeight), mais sans transition CSS sur `height` le
   changement était instantané. On anime donc explicitement la hauteur. */
.submenu-collapse {
    overflow: hidden;
    transition: height 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

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
