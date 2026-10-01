<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Toast from '@/Components/UI/Toast.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const mobileMenuOpen = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: 'LayoutDashboard', current: page.url === '/dashboard' || page.url === '/' },
    { name: 'Vendas', href: '/sales', icon: 'ShoppingCart', current: page.url.startsWith('/sales') },
    { name: 'Produtos', href: '/products', icon: 'Package', current: page.url.startsWith('/products') },
    { name: 'Clientes', href: '/customers', icon: 'Users', current: page.url.startsWith('/customers') },
    { name: 'Despesas', href: '/expenses', icon: 'Receipt', current: page.url.startsWith('/expenses') },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col md:flex-row">
        <Toast />

        <!-- Sidebar for Desktop -->
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-slate-900 border-r border-slate-800 z-30">
            <div class="flex h-16 shrink-0 items-center px-6 gap-3 border-b border-slate-800">
                <div class="h-8 w-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                    SV
                </div>
                <span class="text-base font-bold tracking-tight text-white">Sistema de Vendas</span>
            </div>

            <div class="flex flex-1 flex-col overflow-y-auto px-4 py-6 justify-between">
                <nav class="space-y-1.5">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                        :class="[
                            item.current
                                ? 'bg-blue-600 text-white shadow-sm font-semibold'
                                : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200',
                        ]"
                    >
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>

                <div class="pt-4 border-t border-slate-800">
                    <div class="px-3 py-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-400">Usuário</span>
                            <Badge variant="info" size="sm">{{ page.props.auth.user?.role_label }}</Badge>
                        </div>
                        <p class="text-sm font-semibold text-white mt-1 truncate">{{ page.props.auth.user?.name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ page.props.auth.user?.email }}</p>
                    </div>
                    <button
                        type="button"
                        @click="logout"
                        class="mt-2 w-full flex items-center justify-center gap-2 rounded-lg bg-slate-800/80 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-rose-900/40 hover:text-rose-300 transition-colors"
                    >
                        Encerrar Sessão
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Container -->
        <div class="flex flex-1 flex-col md:pl-64">
            <!-- Mobile Topbar -->
            <header class="md:hidden flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-xs sticky top-0 z-20">
                <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-md bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                        SV
                    </div>
                    <span class="text-sm font-bold text-slate-900">Sistema de Vendas</span>
                </div>
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>

            <!-- Mobile Drawer -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-slate-900 px-4 py-4 space-y-2 border-b border-slate-800">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="block px-3 py-2 rounded-lg text-sm font-medium"
                    :class="[
                        item.current ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800',
                    ]"
                    @click="mobileMenuOpen = false"
                >
                    {{ item.name }}
                </Link>
                <div class="pt-3 border-t border-slate-800 flex justify-between items-center">
                    <span class="text-xs text-slate-400">{{ page.props.auth.user?.name }}</span>
                    <button @click="logout" class="text-xs text-rose-400 font-medium">Sair</button>
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
