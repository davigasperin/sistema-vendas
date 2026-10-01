<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Badge from '@/Components/UI/Badge.vue';
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

defineProps<{
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
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard Executivo" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                <p class="text-sm text-slate-500 mt-0.5">Visão geral do desempenho de vendas, estoque e fluxo financeiro.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <Link
                    href="/sales/create"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition-colors"
                >
                    + Nova Venda
                </Link>
            </div>
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <StatCard
                title="Vendas Hoje"
                :value="salesStats.today"
                subtitle="Pedidos emitidos hoje"
            />
            <StatCard
                title="Faturamento (Mês)"
                :value="formatMoney(salesStats.month)"
                :subtitle="`Ticket médio: ${formatMoney(salesStats.averageThisMonth)}`"
            />
            <StatCard
                title="Balanço Líquido (Mês)"
                :value="formatMoney(financialSummary.balance)"
                :subtitle="`Receitas: ${formatMoney(financialSummary.income.total)}`"
            />
            <StatCard
                title="Contas Vencidas"
                :value="financialSummary.overdue_count"
                :subtitle="financialSummary.overdue_count > 0 ? 'Requer atenção imediata' : 'Nenhuma conta em atraso'"
            />
        </div>

        <!-- Financial Summary Banner -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-emerald-900">Receitas do Mês</span>
                    <Badge variant="success">{{ formatMoney(financialSummary.income.total) }}</Badge>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-emerald-700">
                    <span>Vendas Faturadas: {{ formatMoney(financialSummary.income.sales) }}</span>
                    <span>Receitas Manuais: {{ formatMoney(financialSummary.income.manual) }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-rose-900">Despesas do Mês</span>
                    <Badge variant="danger">{{ formatMoney(financialSummary.expenses.paid) }}</Badge>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-rose-700">
                    <span>Pagas: {{ formatMoney(financialSummary.expenses.paid) }}</span>
                    <span>Pendentes a Pagar: {{ formatMoney(financialSummary.expenses.pending) }}</span>
                </div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Latest Sales -->
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-bold text-slate-900">Últimas Vendas</h2>
                    <Link href="/sales" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Ver todas</Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-3">#ID</th>
                                <th class="py-3 px-3">Cliente</th>
                                <th class="py-3 px-3">Forma Pagto</th>
                                <th class="py-3 px-3 text-right">Total</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="sale in latestSales" :key="sale.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-3 font-semibold text-slate-900">#{{ sale.id }}</td>
                                <td class="py-3.5 px-3 truncate max-w-[150px]">{{ sale.customer?.name || 'Cliente Avulso' }}</td>
                                <td class="py-3.5 px-3">{{ sale.payment_method?.name || '-' }}</td>
                                <td class="py-3.5 px-3 text-right font-semibold text-slate-900">{{ formatMoney(sale.total_amount) }}</td>
                                <td class="py-3.5 px-3 text-center">
                                    <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'">
                                        {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="!latestSales.length">
                                <td colspan="5" class="py-8 text-center text-slate-400 text-sm">Nenhuma venda registrada ainda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions Stream -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 mb-5">Movimentações Recentes</h2>
                <div class="space-y-4">
                    <div
                        v-for="(tx, idx) in recentTransactions"
                        :key="idx"
                        class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/60"
                    >
                        <div class="truncate mr-3">
                            <p class="text-xs font-semibold text-slate-900 truncate">{{ tx.description }}</p>
                            <p class="text-[11px] text-slate-400">{{ formatDate(tx.date) }}</p>
                        </div>
                        <span
                            class="text-xs font-bold shrink-0"
                            :class="tx.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            {{ tx.type === 'income' ? '+' : '-' }} {{ formatMoney(tx.amount) }}
                        </span>
                    </div>
                    <div v-if="!recentTransactions.length" class="py-6 text-center text-slate-400 text-xs">
                        Nenhuma movimentação no período.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
