<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import type { Customer, PaginatedData } from '@/types';

const props = defineProps<{
    customers: PaginatedData<Customer>;
    totalCustomers: number;
    newThisMonth: number;
    recentCustomers?: Customer[];
    filters?: { search?: string };
}>();

const search = ref(props.filters?.search || '');

function handleSearch() {
    router.get('/customers', { search: search.value }, { preserveState: true, replace: true });
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}
</script>

<template>
    <AppLayout title="Clientes">
        <Head title="Base de Clientes" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
                <p class="text-xs text-slate-500 mt-1">Cadastro de clientes, contatos e histórico de compras realizadas.</p>
            </div>
            <Link href="/customers/create">
                <AppButton variant="primary" size="md">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Cadastrar Cliente
                </AppButton>
            </Link>
        </div>

        <!-- Quick Counters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="p-4 rounded-2xl border border-slate-200/80 bg-white shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Base Total</span>
                    <span class="text-xl font-bold text-slate-900 tabular-nums">{{ totalCustomers }} clientes</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>

            <div class="p-4 rounded-2xl border border-slate-200/80 bg-white shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Novos no Mês</span>
                    <span class="text-xl font-bold text-emerald-600 tabular-nums">+{{ newThisMonth }}</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 mb-6 shadow-2xs flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:max-w-md">
                <input
                    v-model="search"
                    @keyup.enter="handleSearch"
                    type="text"
                    placeholder="Buscar por nome, e-mail ou telefone..."
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all"
                />
            </div>
            <AppButton variant="secondary" size="sm" @click="handleSearch">
                Filtrar
            </AppButton>
        </div>

        <!-- Data Table -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="uppercase bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200/80">
                        <tr>
                            <th class="py-3 px-4">Nome do Cliente</th>
                            <th class="py-3 px-4">Contato & Endereço</th>
                            <th class="py-3 px-4">Nascimento / Idade</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ customer.name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">ID: #{{ customer.id }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-slate-700 font-medium">{{ customer.email || 'Sem e-mail' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ customer.phone || 'Sem telefone' }} &bull; {{ customer.address || 'Sem endereço' }}</div>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <div class="font-medium text-slate-700 tabular-nums">{{ formatDate(customer.birth_date) }}</div>
                                <span v-if="customer.age" class="text-[11px] text-slate-400">({{ customer.age }} anos)</span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <Link
                                    :href="`/customers/${customer.id}`"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                                >
                                    Histórico
                                </Link>
                                <Link
                                    :href="`/customers/${customer.id}/edit`"
                                    class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="4">
                                <EmptyState
                                    title="Nenhum cliente encontrado"
                                    description="Cadastre seu primeiro cliente para rastrear compras e histórico."
                                    action-label="+ Cadastrar Cliente"
                                    @action="router.get('/customers/create')"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-slate-100 px-4">
                <Pagination :links="customers.meta?.links || []" />
            </div>
        </div>
    </AppLayout>
</template>
