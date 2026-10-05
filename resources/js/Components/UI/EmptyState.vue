<script setup lang="ts">
import AppButton from '@/Components/UI/AppButton.vue';

interface Props {
    title: string;
    description?: string;
    actionLabel?: string;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'action'): void;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
        <div class="h-12 w-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
            <slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-xs text-slate-500 max-w-sm">{{ description }}</p>
        <div v-if="actionLabel" class="mt-4">
            <AppButton size="sm" @click="emit('action')">
                {{ actionLabel }}
            </AppButton>
        </div>
    </div>
</template>
