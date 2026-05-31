<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Quick presets -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="preset in presets"
                :key="preset.key"
                @click="applyPreset(preset.key)"
                :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all duration-150',
                    activePreset === preset.key
                        ? 'bg-brand-500 border-brand-500 text-white'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-brand-400 hover:text-brand-600 dark:hover:text-brand-400',
                ]"
            >
                {{ preset.label }}
            </button>
        </div>

        <!-- Divider -->
        <span class="hidden sm:block w-px h-5 bg-gray-200 dark:bg-gray-700"></span>

        <!-- Custom range inputs -->
        <div class="flex items-center gap-2">
            <input
                type="date"
                v-model="customFrom"
                @change="applyCustom"
                :max="customTo || undefined"
                class="px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
            />
            <span class="text-xs text-gray-400 dark:text-gray-500">→</span>
            <input
                type="date"
                v-model="customTo"
                @change="applyCustom"
                :min="customFrom || undefined"
                class="px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
            />

            <!-- Clear -->
            <button
                v-if="activePreset || customFrom || customTo"
                @click="clear"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Effacer le filtre"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const emit = defineEmits(['change'])

const activePreset = ref(null)
const customFrom = ref('')
const customTo = ref('')

const presets = [
    { key: 'today', label: "Aujourd'hui" },
    { key: 'week', label: 'Cette semaine' },
    { key: 'month', label: 'Ce mois' },
    { key: 'year', label: 'Cette année' },
]

function toIso(date) {
    return date.toISOString().split('T')[0]
}

function applyPreset(key) {
    activePreset.value = key
    customFrom.value = ''
    customTo.value = ''

    const now = new Date()
    let from, to

    if (key === 'today') {
        from = toIso(now)
        to = toIso(now)
    } else if (key === 'week') {
        const day = now.getDay() || 7
        const monday = new Date(now)
        monday.setDate(now.getDate() - day + 1)
        const sunday = new Date(monday)
        sunday.setDate(monday.getDate() + 6)
        from = toIso(monday)
        to = toIso(sunday)
    } else if (key === 'month') {
        from = toIso(new Date(now.getFullYear(), now.getMonth(), 1))
        to = toIso(new Date(now.getFullYear(), now.getMonth() + 1, 0))
    } else if (key === 'year') {
        from = toIso(new Date(now.getFullYear(), 0, 1))
        to = toIso(new Date(now.getFullYear(), 11, 31))
    }

    emit('change', { from, to, preset: key })
}

function applyCustom() {
    if (!customFrom.value && !customTo.value) return
    activePreset.value = null
    emit('change', { from: customFrom.value || null, to: customTo.value || null, preset: null })
}

function clear() {
    activePreset.value = null
    customFrom.value = ''
    customTo.value = ''
    emit('change', { from: null, to: null, preset: null })
}
</script>
