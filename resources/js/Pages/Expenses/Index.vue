<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import type { Expense, ExpenseCategory, PaginatedData } from '@/types';

const props = defineProps<{
    expenses: PaginatedData<Expense>;
    categories: ExpenseCategory[];
    filters?: { search?: string; status?: string; type?: string };
}>();

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const type = ref(props.filters?.type || '');

function applyFilters() {
    router.get(
        '/expenses',
        { search: search.value, status: status.value, type: type.value },
        { preserveState: true, replace: true }
    );
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function getStatusVariant(statusStr: string) {
    if (statusStr === 'paid') return 'success';
    if (statusStr === 'overdue') return 'danger';
    if (statusStr === 'pending') return 'warning';
    return 'neutral';
}
</script>

<template>
    <AppLayout title="Despesas">
        <Head title="Controle de Despesas e Receitas" />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Despesas</h1>
                <p class="text-sm text-slate-500 mt-0.5">Contas a pagar, despesas quitadas e entradas manuais de receita.</p>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    href="/expenses/report"
                    class="px-4 py-2.5 rounded-xl bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
                >
                    Relatório Financeiro
                </Link>
                <Link
                    href="/expenses/create"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition-colors"
                >
                    + Nova Despesa
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-6 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Buscar descrição..."
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div>
                <select
                    v-model="status"
                    @change="applyFilters"
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Todos os status</option>
                    <option value="pending">Pendente</option>
                    <option value="paid">Pago</option>
                    <option value="overdue">Vencido</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
            <div>
                <select
                    v-model="type"
                    @change="applyFilters"
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Todos os tipos</option>
                    <option value="expense">Despesa</option>
                    <option value="income">Receita</option>
                </select>
            </div>
            <div>
                <button
                    @click="applyFilters"
                    type="button"
                    class="w-full px-4 py-2 rounded-xl bg-slate-900 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
                >
                    Filtrar
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Descrição</th>
                            <th class="py-3 px-4">Categoria</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Vencimento</th>
                            <th class="py-3 px-4">Pagamento</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div>{{ expense.description }}</div>
                                <div class="text-xs font-normal text-slate-400">{{ expense.notes || '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <span
                                    class="inline-block w-2 h-2 rounded-full mr-1.5 align-middle"
                                    :style="{ backgroundColor: expense.category?.color || '#cbd5e1' }"
                                />
                                {{ expense.category?.name || '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <Badge :variant="expense.type === 'income' ? 'info' : 'neutral'" size="sm">
                                    {{ expense.type === 'income' ? 'Receita' : 'Despesa' }}
                                </Badge>
                            </td>
                            <td class="py-3.5 px-4 text-xs">{{ formatDate(expense.due_date) }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ formatDate(expense.paid_date) }}</td>
                            <td class="py-3.5 px-4 text-right font-bold" :class="expense.type === 'income' ? 'text-emerald-600' : 'text-slate-900'">
                                {{ formatMoney(expense.amount) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <Badge :variant="getStatusVariant(expense.status)">
                                    {{ expense.status === 'paid' ? 'Pago' : expense.status === 'overdue' ? 'Vencido' : expense.status === 'pending' ? 'Pendente' : 'Cancelado' }}
                                </Badge>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <Link
                                    v-if="expense.status !== 'paid'"
                                    :href="`/expenses/${expense.id}/mark-paid`"
                                    method="patch"
                                    as="button"
                                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 transition-colors"
                                    @click.prevent="
                                        router.patch(
                                            `/expenses/${expense.id}/mark-paid`,
                                            {},
                                            { preserveScroll: true }
                                        )
                                    "
                                >
                                    Baixar
                                </Link>
                                <Link
                                    :href="`/expenses/${expense.id}/edit`"
                                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td colspan="8" class="py-8 text-center text-slate-400 text-sm">Nenhuma despesa ou receita cadastrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-slate-100 px-4">
                <Pagination :links="expenses.meta?.links || []" />
            </div>
        </div>
    </AppLayout>
</template>
