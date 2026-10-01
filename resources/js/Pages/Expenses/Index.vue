<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
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
        <Head title="Controle Financeiro e Despesas" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Despesas & Receitas</h1>
                <p class="text-xs text-slate-500 mt-1">Controle de contas a pagar, quitação de despesas e entradas manuais de caixa.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <Link href="/expenses/report">
                    <AppButton variant="secondary" size="md">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Relatório Consolidado
                    </AppButton>
                </Link>
                <Link href="/expenses/create">
                    <AppButton variant="primary" size="md">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Lançamento
                    </AppButton>
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 mb-6 shadow-2xs grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Buscar por descrição..."
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all"
                />
            </div>
            <div>
                <select
                    v-model="status"
                    @change="applyFilters"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 cursor-pointer"
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
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 cursor-pointer"
                >
                    <option value="">Todos os tipos</option>
                    <option value="expense">Despesas (Saída)</option>
                    <option value="income">Receitas (Entrada Manual)</option>
                </select>
            </div>
            <div>
                <AppButton variant="secondary" size="sm" class="w-full py-2" @click="applyFilters">
                    Filtrar Lançamentos
                </AppButton>
            </div>
        </div>

        <!-- Data Table -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="uppercase bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200/80">
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
                        <tr
                            v-for="expense in expenses.data"
                            :key="expense.id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="py-3 px-4 font-bold text-slate-900">
                                <div>{{ expense.description }}</div>
                                <div class="text-[11px] text-slate-400 font-normal mt-0.5">{{ expense.notes || '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium">
                                <span
                                    class="inline-block w-2 h-2 rounded-full mr-1.5 align-middle"
                                    :style="{ backgroundColor: expense.category?.color || '#94a3b8' }"
                                />
                                {{ expense.category?.name || '-' }}
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <Badge :variant="expense.type === 'income' ? 'info' : 'neutral'" size="sm">
                                    {{ expense.type === 'income' ? 'Receita' : 'Despesa' }}
                                </Badge>
                            </td>
                            <td class="py-3 px-4 tabular-nums">{{ formatDate(expense.due_date) }}</td>
                            <td class="py-3 px-4 tabular-nums text-slate-500">{{ formatDate(expense.paid_date) }}</td>
                            <td
                                class="py-3 px-4 text-right font-bold tabular-nums"
                                :class="expense.type === 'income' ? 'text-emerald-600' : 'text-slate-900'"
                            >
                                {{ formatMoney(expense.amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <Badge :variant="getStatusVariant(expense.status)" size="sm">
                                    {{ expense.status === 'paid' ? 'Pago' : expense.status === 'overdue' ? 'Vencido' : expense.status === 'pending' ? 'Pendente' : 'Cancelado' }}
                                </Badge>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button
                                    v-if="expense.status !== 'paid'"
                                    type="button"
                                    @click="router.patch(`/expenses/${expense.id}/mark-paid`, {}, { preserveScroll: true })"
                                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 transition-colors cursor-pointer"
                                >
                                    Quitar
                                </button>
                                <Link
                                    :href="`/expenses/${expense.id}/edit`"
                                    class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td colspan="8">
                                <EmptyState
                                    title="Nenhum lançamento encontrado"
                                    description="Cadastre despesas ou entradas manuais de receita para controlar suas contas."
                                    action-label="+ Novo Lançamento"
                                    @action="router.get('/expenses/create')"
                                />
                            </td>
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
