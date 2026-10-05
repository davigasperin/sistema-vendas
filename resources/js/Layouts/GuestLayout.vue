<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Toast from '@/Components/UI/Toast.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden select-none">
        <Head :title="title ? `${title} - Sistema de Vendas` : 'Sistema de Vendas'" />
        <Toast />

        <!-- Background Ambient Glow (Subtle and restrained) -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="w-[600px] h-[600px] bg-slate-500/10 rounded-full blur-3xl" />
            <div class="w-[400px] h-[400px] bg-slate-400/10 rounded-full blur-2xl -translate-y-24" />
        </div>

        <!-- Header / Brand -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
            <Link href="/" class="inline-flex items-center gap-2 group">
                <span class="text-lg font-semibold text-white tracking-tight">Sistema de Vendas</span>
                <span class="text-xs font-medium text-slate-500">ERP</span>
            </Link>
        </div>

        <!-- Main Card Area -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 relative z-10">
            <div class="bg-slate-900/90 backdrop-blur-md border border-slate-800/80 rounded-xl p-6 sm:p-8 shadow-2xl shadow-black/40">
                <slot />
            </div>

            <!-- Footer Meta -->
            <div class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ new Date().getFullYear() }} Sistema de Vendas. Todos os direitos reservados.
            </div>
        </div>
    </div>
</template>
