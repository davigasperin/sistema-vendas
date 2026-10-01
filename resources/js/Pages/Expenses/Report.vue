<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Expense } from '@/types';

interface SummaryData {
    period: { start: string; end: string };
    income: { sales: number; manual: number; total: number };
    expenses: { paid: number; pending: number };
    balance: number;
    overdue_count: number;
}

const props = defineProps<{
    summary: SummaryData;
    startDate: string;
    endDate: string;
    overdueExpenses: Expense[];
}>();

const start = ref(props.startDate);
const end = ref(props.endDate);

function filterPeriod() {
    router.get('/expenses/report', { start_date: start.value, end_date: end.value }, { preserveState: true });
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}
</script>

<template>
    <AppLayout title="Relatório Financeiro">
        <Head title="Relatório Financeiro" />

        <div class="max-w-5xl mx-auto space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Relatório Financeiro</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Balanço consolidado de receitas, despesas e pendências por período.</p>
                </div>
                <Link
                    href="/expenses"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    &larr; Voltar para Despesas
                </Link>
            </div>

            <!-- Date Filter Filter -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col sm:flex-row gap-4 items-end justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full sm:max-w-md">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">De</label>
                        <input
                            v-model="start"
                            type="date"
                            class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Até</label>
                        <input
                            v-model="end"
                            type="date"
                            class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-xs focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <button
                    type="button"
                    @click="filterPeriod"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 text-xs font-semibold text-white hover:bg-slate-800 transition-colors"
                >
                    Atualizar Relatório
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-6 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Total de Receitas</span>
                    <p class="text-2xl font-black text-emerald-700 mt-2">{{ formatMoney(summary.income.total) }}</p>
                    <div class="mt-2 text-xs text-emerald-600 space-y-0.5">
                        <p>Vendas: {{ formatMoney(summary.income.sales) }}</p>
                        <p>Manuais: {{ formatMoney(summary.income.manual) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-6 shadow-xs">
                    <span class="text-xs font-semibold text-rose-800 uppercase tracking-wider">Despesas Pagas</span>
                    <p class="text-2xl font-black text-rose-700 mt-2">{{ formatMoney(summary.expenses.paid) }}</p>
                    <div class="mt-2 text-xs text-rose-600">
                        <p>Pendentes a Pagar: {{ formatMoney(summary.expenses.pending) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Resultado Líquido</span>
                    <p
                        class="text-2xl font-black mt-2"
                        :class="summary.balance >= 0 ? 'text-blue-600' : 'text-rose-600'"
                    >
                        {{ formatMoney(summary.balance) }}
                    </p>
                    <p class="text-xs text-slate-400 mt-2">Receitas menos despesas quitadas</p>
                </div>
            </div>

            <!-- Overdue List -->
            <div v-if="overdueExpenses.length" class="rounded-2xl border border-rose-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-rose-100 pb-3">
                    <h3 class="text-base font-bold text-rose-900">Contas Vencidas que Requerem Pagamento</h3>
                    <Badge variant="danger">{{ overdueExpenses.length }} em atraso</Badge>
                </div>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="exp in overdueExpenses"
                        :key="exp.id"
                        class="py-3 flex items-center justify-between"
                    >
                        <div>
                            <span class="text-sm font-semibold text-slate-900">{{ exp.description }}</span>
                            <span class="text-xs text-rose-500 block">Venceu em: {{ formatDate(exp.due_date) }}</span>
                        </div>
                        <span class="text-sm font-bold text-rose-700">{{ formatMoney(exp.amount) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
