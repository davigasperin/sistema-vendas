<script setup lang="ts">
import Modal from '@/Components/UI/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';

interface Props {
    show: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'danger' | 'primary';
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    variant: 'danger',
    loading: false,
});

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <Modal :show="show" :title="title" max-width="md" @close="emit('cancel')">
        <div class="space-y-4">
            <p class="text-sm text-slate-600">{{ message }}</p>
            <div class="flex justify-end gap-2.5 pt-2">
                <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="loading"
                    @click="emit('cancel')"
                >
                    {{ cancelLabel }}
                </AppButton>
                <AppButton
                    :variant="variant"
                    size="sm"
                    :loading="loading"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </AppButton>
            </div>
        </div>
    </Modal>
</template>
