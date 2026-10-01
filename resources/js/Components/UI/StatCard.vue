<script setup lang="ts">
defineProps<{
    title: string;
    value: string | number;
    subtitle?: string;
    variant?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'default';
}>();
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500">{{ title }}</span>
            <slot name="icon" />
        </div>
        <div class="mt-3 flex items-baseline">
            <span class="text-2xl font-bold tracking-tight text-slate-900">{{ value }}</span>
        </div>
        <p v-if="subtitle" class="mt-1 text-xs text-slate-500">{{ subtitle }}</p>
    </div>
</template>
