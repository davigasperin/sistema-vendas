<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
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
        <Head title="Gerenciamento de Clientes" />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
                <p class="text-sm text-slate-500 mt-0.5">Base cadastral de clientes e histórico de compras.</p>
            </div>
            <Link
                href="/customers/create"
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition-colors"
            >
                + Novo Cliente
            </Link>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total de Clientes</span>
                <span class="text-lg font-bold text-slate-900">{{ totalCustomers }}</span>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Novos no Mês</span>
                <span class="text-lg font-bold text-emerald-600">+{{ newThisMonth }}</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-6 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:max-w-md">
                <input
                    v-model="search"
                    @keyup.enter="handleSearch"
                    type="text"
                    placeholder="Buscar por nome, e-mail ou telefone..."
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <button
                @click="handleSearch"
                type="button"
                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-100 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
            >
                Filtrar
            </button>
        </div>

        <!-- Table -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Nome</th>
                            <th class="py-3 px-4">Contato</th>
                            <th class="py-3 px-4">Nascimento / Idade</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div>{{ customer.name }}</div>
                                <div class="text-xs font-normal text-slate-400">{{ customer.address || 'Endereço não informado' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-xs text-slate-800">{{ customer.email || '-' }}</div>
                                <div class="text-xs text-slate-400">{{ customer.phone || '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div>{{ formatDate(customer.birth_date) }}</div>
                                <span v-if="customer.age" class="text-slate-400">({{ customer.age }} anos)</span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <Link
                                    :href="`/customers/${customer.id}`"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                                >
                                    Histórico
                                </Link>
                                <Link
                                    :href="`/customers/${customer.id}/edit`"
                                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="4" class="py-8 text-center text-slate-400 text-sm">Nenhum cliente cadastrado.</td>
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
