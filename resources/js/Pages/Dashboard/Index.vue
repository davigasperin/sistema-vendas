<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Badge from '@/Components/UI/Badge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import type { Sale, PaymentMethod } from '@/types';

interface FinancialSummary {
    period: { start: string; end: string };
    income: { sales: number; manual: number; total: number };
    expenses: { paid: number; pending: number };
    balance: number;
    overdue_count: number;
}

interface SalesStats {
    today: number;
    month: number;
    total: number;
    average: number;
    week: number;
    averageThisMonth: number;
    lowStockCount?: number;
    overdueInstallmentsCount?: number;
}

interface RecentTransaction {
    type: 'income' | 'expense';
    description: string;
    amount: number;
    date: string;
    status: string;
}

const props = defineProps<{
    salesStats: SalesStats;
    latestSales: Sale[];
    paymentMethods: PaymentMethod[];
    financialSummary: FinancialSummary;
    recentTransactions: RecentTransaction[];
}>();

function formatMoney(value: number | string): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

// Calculate percentages for visual ratio bar
const totalVolume = props.financialSummary.income.total + props.financialSummary.expenses.paid;
const incomePercentage = totalVolume > 0 ? Math.round((props.financialSummary.income.total / totalVolume) * 100) : 50;
const expensePercentage = 100 - incomePercentage;
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Painel Geral" />

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
                <p class="text-xs text-slate-500 mt-1">Métricas em tempo real de vendas, fluxo de caixa e estoques.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <Link href="/sales/create">
                    <AppButton variant="primary" size="md">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova Venda
                    </AppButton>
                </Link>
            </div>
        </div>

        <!-- 4 Primary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard
                title="Vendas Hoje"
                :value="salesStats.today"
                subtitle="Pedidos processados"
            >
                <template #icon>
                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </template>
            </StatCard>

            <StatCard
                title="Faturamento do Mês"
                :value="formatMoney(salesStats.month)"
                :subtitle="`Ticket Médio: ${formatMoney(salesStats.averageThisMonth)}`"
            >
                <template #icon>
                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>
            </StatCard>

            <StatCard
                title="Resultado Líquido"
                :value="formatMoney(financialSummary.balance)"
                :subtitle="`Receitas: ${formatMoney(financialSummary.income.total)}`"
            >
                <template #icon>
                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </template>
            </StatCard>

            <StatCard
                title="Contas Vencidas"
                :value="financialSummary.overdue_count"
                :subtitle="financialSummary.overdue_count > 0 ? 'Lançamentos pendentes' : 'Nenhuma conta em atraso'"
            >
                <template #icon>
                    <div
                        class="h-8 w-8 rounded-lg flex items-center justify-center"
                        :class="financialSummary.overdue_count > 0 ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-400'"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>
            </StatCard>
        </div>

        <!-- Cashflow Health Bar & Breakdown -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Composição do Fluxo Financeiro</h2>
                    <p class="text-xs text-slate-500">Proporção entre receitas arrecadadas e despesas quitadas no período.</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                        Receitas ({{ incomePercentage }}%)
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-rose-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500" />
                        Despesas ({{ expensePercentage }}%)
                    </span>
                </div>
            </div>

            <!-- Proportional Ratio Bar -->
            <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden flex gap-1 p-0.5">
                <div
                    class="h-full bg-emerald-500 rounded-full transition-colors duration-500"
                    :style="{ width: `${incomePercentage}%` }"
                />
                <div
                    class="h-full bg-rose-500 rounded-full transition-colors duration-500"
                    :style="{ width: `${expensePercentage}%` }"
                />
            </div>

            <!-- Detailed Values Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Vendas no Mês</span>
                    <span class="text-slate-900 font-semibold tabular-nums text-sm">{{ formatMoney(financialSummary.income.sales) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Receitas Manuais</span>
                    <span class="text-slate-900 font-semibold tabular-nums text-sm">{{ formatMoney(financialSummary.income.manual) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Despesas Pagas</span>
                    <span class="text-slate-900 font-semibold tabular-nums text-sm">{{ formatMoney(financialSummary.expenses.paid) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Despesas a Vencer</span>
                    <span class="text-slate-900 font-semibold tabular-nums text-sm">{{ formatMoney(financialSummary.expenses.pending) }}</span>
                </div>
            </div>
        </div>

        <!-- 2 Column Section: Latest Sales & Recent Movements -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Latest Sales Table -->
            <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Últimos Pedidos Emitidos</h2>
                        <p class="text-xs text-slate-500">Transações recentes registradas no sistema.</p>
                    </div>
                    <Link
                        href="/sales"
                        class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                    >
                        Ver todas &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="uppercase bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">#ID</th>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3">Forma Pagto</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="sale in latestSales"
                                :key="sale.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <td class="py-3 px-3 font-semibold text-slate-900 tabular-nums">#{{ sale.id }}</td>
                                <td class="py-3 px-3 font-medium text-slate-800 truncate max-w-[160px]">
                                    {{ sale.customer?.name || 'Cliente Avulso' }}
                                </td>
                                <td class="py-3 px-3 text-slate-500">{{ sale.payment_method?.name || '-' }}</td>
                                <td class="py-3 px-3 text-right font-semibold text-slate-900 tabular-nums">
                                    {{ formatMoney(sale.total_amount) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'" size="sm">
                                        {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="!latestSales.length">
                                <td colspan="5" class="py-8 text-center text-slate-400">Nenhuma venda registrada ainda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Stream -->
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-slate-900">Atividade Recente</h2>
                    <p class="text-xs text-slate-500">Últimas entradas e saídas de caixa.</p>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(tx, idx) in recentTransactions"
                        :key="`${tx.type}-${tx.date}-${tx.amount}-${idx}`"
                        class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50/50 hover:bg-slate-100/70 transition-colors"
                    >
                        <div class="truncate mr-3">
                            <p class="text-xs font-semibold text-slate-900 truncate leading-tight">{{ tx.description }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 tabular-nums">{{ formatDate(tx.date) }}</p>
                        </div>
                        <span
                            class="text-xs font-semibold tabular-nums shrink-0"
                            :class="tx.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            {{ tx.type === 'income' ? '+' : '-' }} {{ formatMoney(tx.amount) }}
                        </span>
                    </div>
                    <div v-if="!recentTransactions.length" class="py-8 text-center text-slate-400 text-xs">
                        Nenhuma movimentação recente.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
