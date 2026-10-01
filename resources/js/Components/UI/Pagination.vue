<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();

function cleanLabel(label: string): string {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Próximo')
        .trim();
}
</script>

<template>
    <div v-if="links && links.length > 3" class="flex items-center justify-between py-3 text-xs text-slate-500">
        <div class="flex items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <span
                    v-if="link.url === null"
                    class="px-2.5 py-1 text-slate-300 select-none"
                >
                    {{ cleanLabel(link.label) }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    class="px-2.5 py-1 rounded-md transition-colors"
                    :class="[
                        link.active
                            ? 'bg-slate-900 text-white font-semibold'
                            : 'hover:bg-slate-100 text-slate-700',
                    ]"
                    preserve-scroll
                >
                    {{ cleanLabel(link.label) }}
                </Link>
            </template>
        </div>
    </div>
</template>
