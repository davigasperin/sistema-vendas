<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-center gap-1 py-3">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3 py-1.5 text-xs text-slate-400 border border-slate-200 rounded-lg"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                :class="[
                    link.active
                        ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                ]"
                v-html="link.label"
                preserve-scroll
            />
        </template>
    </div>
</template>
