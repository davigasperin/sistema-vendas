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
            variant === 'success' ? 'bg-emerald-50 text-emerald-600' : '',
            variant === 'danger' ? 'bg-rose-50 text-rose-600' : '',
            variant === 'warning' ? 'bg-amber-50 text-amber-600' : '',
            variant === 'info' ? 'bg-blue-50 text-blue-600' : '',
            variant === 'neutral' ? 'bg-slate-100 text-slate-600' : '',
        ]"
    >
        <slot />
    </span>
</template>
