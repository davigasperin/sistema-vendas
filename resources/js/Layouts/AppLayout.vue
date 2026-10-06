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
    {
        name: 'Dashboard',
        href: '/dashboard',
        current: page.url === '/dashboard' || page.url === '/',
        icon: 'dashboard',
    },
    {
        name: 'Vendas',
        href: '/sales',
        current: page.url.startsWith('/sales'),
        icon: 'sales',
    },
    {
        name: 'Caixa',
        href: '/cashier',
        current: page.url.startsWith('/cashier'),
        icon: 'cashier',
    },
    {
        name: 'Contas a receber',
        href: '/receivables',
        current: page.url.startsWith('/receivables'),
        icon: 'receivables',
    },
    {
        name: 'Produtos',
        href: '/products',
        current: page.url.startsWith('/products'),
        icon: 'products',
    },
    {
        name: 'Clientes',
        href: '/customers',
        current: page.url.startsWith('/customers'),
        icon: 'customers',
    },
    {
        name: 'Despesas',
        href: '/expenses',
        current: page.url.startsWith('/expenses'),
        icon: 'expenses',
    },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
  <div class="min-h-screen bg-slate-50/60 flex flex-col md:flex-row text-slate-900 antialiased font-sans selection:bg-slate-900 selection:text-white">
    <Toast />

    <!-- Sidebar for Desktop -->
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-slate-950 border-r border-slate-800/60 z-30 select-none">
      <!-- Brand -->
      <div class="flex h-16 shrink-0 items-center px-5 border-b border-slate-800/60">
        <div class="flex items-baseline gap-2">
          <span class="text-sm font-semibold text-white tracking-tight">Sistema de Vendas</span>
          <span class="text-[10px] font-medium text-slate-500 uppercase">ERP</span>
        </div>
      </div>

      <!-- Navigation Links -->
      <div class="flex flex-1 flex-col overflow-y-auto px-3 py-4 justify-between">
        <nav class="space-y-1">
          <div v-for="item in navigation" :key="item.name" class="space-y-1">
            <Link
              :href="item.href"
              class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-colors duration-150"
              :class="[
                item.current
                  ? 'bg-slate-800 text-white'
                  : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200',
              ]"
            >
              <!-- Icons -->
              <svg
                v-if="item.icon === 'dashboard'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'sales'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'cashier'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'receivables'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 8h6m-6 4h6m-6 4h4M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'products'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'customers'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>

              <svg
                v-else-if="item.icon === 'expenses'"
                class="h-4 w-4 shrink-0 transition-colors"
                :class="item.current ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"
                />
              </svg>

              <span>{{ item.name }}</span>
            </Link>

            <!-- Vendas Submenu -->
            <div
              v-if="item.name === 'Vendas' && item.current"
              class="ml-7 pl-2 border-l border-slate-800/80 space-y-0.5 py-1"
            >
              <Link
                href="/sales"
                class="block px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors"
                :class="page.url === '/sales' || (page.url.startsWith('/sales') && !page.url.startsWith('/sales/report')) ? 'text-white bg-slate-800/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'"
              >
                Histórico & Pedidos
              </Link>
              <Link
                href="/sales/report"
                class="block px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors"
                :class="page.url.startsWith('/sales/report') ? 'text-white bg-slate-800/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'"
              >
                Relatório de Vendas
              </Link>
            </div>
          </div>
        </nav>

        <!-- User Profile & Action -->
        <div class="pt-4 border-t border-slate-800/60">
          <div class="px-3 py-2.5 rounded-lg">
            <div class="flex items-center justify-between">
              <span class="text-[11px] text-slate-500">Perfil Ativo</span>
              <Badge
                variant="neutral"
                size="sm"
              >
                {{ page.props.auth.user?.role_label || 'Vendedor' }}
              </Badge>
            </div>
            <p class="text-xs font-semibold text-white mt-1.5 truncate">
              {{ page.props.auth.user?.name }}
            </p>
            <p class="text-[11px] text-slate-400 truncate">
              {{ page.props.auth.user?.email }}
            </p>
          </div>

          <button
            type="button"
            class="mt-2.5 w-full flex items-center justify-center gap-2 rounded-lg text-slate-400 hover:text-slate-200 px-3 py-2 text-xs font-medium transition-colors cursor-pointer"
            @click="logout"
          >
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
            Encerrar Sessão
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex flex-1 flex-col md:pl-64 min-w-0">
      <!-- Topbar with Breadcrumbs and Quick Status -->
      <header class="hidden md:flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-8 sticky top-0 z-20">
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span class="text-slate-400">Sistema</span>
          <span>/</span>
          <span class="text-slate-900 font-medium capitalize">{{ page.url.split('/')[1] || 'Dashboard' }}</span>
        </div>

        <div class="flex items-center gap-3">
          <div
            v-if="page.props.auth.user?.current_cash_shift"
            class="flex items-center gap-2 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 border border-emerald-200"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
            Caixa Aberto (R$ {{ Number(page.props.auth.user.current_cash_shift.initial_amount || 0).toFixed(2).replace('.', ',') }})
          </div>
          <div
            v-else
            class="flex items-center gap-2 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 border border-slate-200"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" />
            Caixa Fechado
          </div>
          <div class="flex items-center gap-2 text-[11px] text-slate-400">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
            Operação Online
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="md:hidden flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sticky top-0 z-20">
        <div class="flex items-baseline gap-2">
          <span class="text-sm font-semibold text-slate-900 tracking-tight">Sistema de Vendas</span>
          <span class="text-[10px] font-medium text-slate-400 uppercase">ERP</span>
        </div>
        <button
          type="button"
          class="p-2 rounded-lg text-slate-600 hover:bg-slate-100"
          aria-label="Menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
      </header>

      <!-- Mobile Drawer -->
      <div
        v-if="mobileMenuOpen"
        class="md:hidden bg-slate-950 px-4 py-4 space-y-1.5 border-b border-slate-800"
      >
        <div v-for="item in navigation" :key="item.name" class="space-y-1">
          <Link
            :href="item.href"
            class="block px-3 py-2 rounded-lg text-xs font-medium"
            :class="[
              item.current ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900',
            ]"
            @click="mobileMenuOpen = false"
          >
            {{ item.name }}
          </Link>
          <div
            v-if="item.name === 'Vendas' && item.current"
            class="ml-4 pl-2 border-l border-slate-800 space-y-1 py-1"
          >
            <Link
              href="/sales"
              class="block px-2 py-1 text-xs text-slate-400 hover:text-white"
              :class="{ 'text-white font-medium': page.url === '/sales' || (page.url.startsWith('/sales') && !page.url.startsWith('/sales/report')) }"
              @click="mobileMenuOpen = false"
            >
              Histórico & Pedidos
            </Link>
            <Link
              href="/sales/report"
              class="block px-2 py-1 text-xs text-slate-400 hover:text-white"
              :class="{ 'text-white font-medium': page.url.startsWith('/sales/report') }"
              @click="mobileMenuOpen = false"
            >
              Relatório de Vendas
            </Link>
          </div>
        </div>
        <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-xs">
          <span class="text-slate-400">{{ page.props.auth.user?.name }}</span>
          <button
            class="text-rose-400 font-semibold cursor-pointer"
            @click="logout"
          >
            Sair
          </button>
        </div>
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
