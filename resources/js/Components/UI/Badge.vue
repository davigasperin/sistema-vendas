<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'success' | 'danger' | 'warning' | 'info' | 'neutral';
        size?: 'sm' | 'md';
    }>(),
    {
        variant: 'neutral',
        size: 'md',
    }
);
</script>

<template>
    <span
        class="inline-flex items-center font-medium rounded-full"
        :class="[
            size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
            variant === 'success' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : '',
            variant === 'danger' ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20' : '',
            variant === 'warning' ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : '',
            variant === 'info' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' : '',
            variant === 'neutral' ? 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/10' : '',
        ]"
    >
        <slot />
    </span>
</template>
