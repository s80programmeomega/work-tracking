<template>
    <div class="relative" ref="dropdownRef">
        <button
            class="flex items-center text-gray-700 dark:text-gray-400"
            dusk="user-menu-toggle"
            @click.prevent="toggleDropdown"
        >
            <span class="mr-3 flex items-center justify-center overflow-hidden rounded-full h-11 w-11 bg-brand-500 text-white">
                <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    :alt="userName"
                    class="h-full w-full object-cover"
                />
                <span v-else class="text-sm font-semibold">{{ userInitials }}</span>
            </span>

            <span class="block mr-1 font-medium text-theme-sm">{{
                userName
            }}</span>

            <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
        </button>

        <!-- Dropdown Start -->
        <transition name="fade-pop">
        <div
            v-if="dropdownOpen"
            class="absolute right-0 mt-4.25 flex w-65 flex-col rounded-3 border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900"
        >
            <div>
                <span
                    class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400"
                >
                    {{ userName }}
                </span>
                <span
                    class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400"
                >
                    {{ userEmail }}
                </span>
            </div>

            <ul
                class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800"
            >
                <li v-for="item in menuItems" :key="item.href">
                    <router-link
                        :to="item.href"
                        class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-3 group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    >
                        <!-- SVG icon would go here -->
                        <component
                            :is="item.icon"
                            class="text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200"
                        />
                        {{ item.text }}
                    </router-link>
                </li>
            </ul>
            <router-link
                to="/signin"
                @click="signOut"
                dusk="user-menu-signout"
                class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-3 group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            >
                <LogoutIcon
                    class="text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200"
                />
                {{ $t('user_menu.sign_out') }}
            </router-link>
        </div>
        </transition>
        <!-- Dropdown End -->
    </div>
</template>

<script setup>
import {
    UserCircleIcon,
    ChevronDownIcon,
    LogoutIcon,
    SettingsIcon,
    InfoCircleIcon,
} from "@/icons";
import { RouterLink, useRouter } from "vue-router";
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useAuth } from "@/composables/useAuth";

const { t } = useI18n();
const router = useRouter();
const { logout, user } = useAuth();

const dropdownOpen = ref(false);
const dropdownRef = ref(null);

const userName = computed(() => user.value?.nom || "Utilisateur");
const userEmail = computed(() => user.value?.email || "");

// Avatar de l'utilisateur connecté (UserResource.avatar = avatar_url). À défaut,
// on affiche les initiales — la navbar reflète ainsi la photo de profil mise à jour.
const avatarUrl = computed(() => user.value?.avatar || null);
const userInitials = computed(() => {
    if (user.value?.initials) {
        return user.value.initials;
    }
    const p = (user.value?.prenom || "").charAt(0);
    const n = (user.value?.nom || "").charAt(0);
    return (p + n).toUpperCase() || "U";
});

const menuItems = computed(() => [
    { href: "/profile", icon: UserCircleIcon, text: t('user_menu.edit_profile') },
    { href: "/settings", icon: SettingsIcon, text: t('user_menu.account_settings') },
    { href: "/support", icon: InfoCircleIcon, text: t('user_menu.support') },
]);

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const signOut = async () => {
    try {
        await logout();
        closeDropdown();
        router.push("/signin");
    } catch (error) {
        console.error("Erreur lors de la déconnexion:", error);
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>
