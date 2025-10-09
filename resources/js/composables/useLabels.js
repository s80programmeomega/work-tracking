import { computed } from 'vue';
import { useLabelStore } from '../stores/labelStore';

export function useLabels() {
    const labelStore = useLabelStore();

    const labels = computed(() => labelStore.orderedLabels);
    const loading = computed(() => labelStore.loading);
    const error = computed(() => labelStore.error);

    const fetchLabels = async () => {
        console.log('Fetching labels, token:', localStorage.getItem('auth_token')?.substring(0, 20) + '...');
        await labelStore.fetchLabels();
    };

    const createLabel = async (data) => {
        return await labelStore.createLabel(data);
    };

    const updateLabel = async (id, data) => {
        return await labelStore.updateLabel(id, data);
    };

    const deleteLabel = async (id) => {
        await labelStore.deleteLabel(id);
    };

    const reorderLabels = async (orderedIds) => {
        await labelStore.reorderLabels(orderedIds);
    };

    const getLabelById = (id) => {
        return labelStore.getLabelById(id);
    };

    return {
        labels,
        loading,
        error,
        fetchLabels,
        createLabel,
        updateLabel,
        deleteLabel,
        reorderLabels,
        getLabelById
    };
}
