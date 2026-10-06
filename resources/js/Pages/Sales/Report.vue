<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { SalesReportData } from '@/types';

const props = defineProps<{
    report: SalesReportData;
    startDate: string;
    endDate: string;
}>();

const start = ref(props.startDate);
const end = ref(props.endDate);

function formatDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function applyDateRange(startStr: string, endStr: string) {
    start.value = startStr;
    end.value = endStr;
    filterPeriod();
}

function setFilterToday() {
    const today = new Date();
    const str = formatDateInput(today);
    applyDateRange(str, str);
}

function setFilterThisWeek() {
    const now = new Date();
    const dayOfWeek = now.getDay();
    const diffToMonday = (dayOfWeek + 6) % 7;
    const startOfWeek = new Date(now);
    startOfWeek.setDate(now.getDate() - diffToMonday);
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    applyDateRange(formatDateInput(startOfWeek), formatDateInput(endOfWeek));
}

function setFilterThisMonth() {
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    applyDateRange(formatDateInput(startOfMonth), formatDateInput(endOfMonth));
}

function setFilterLast30Days() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 29);

    applyDateRange(formatDateInput(thirtyDaysAgo), formatDateInput(today));
}

function filterPeriod() {
    router.get('/sales/report', { start_date: start.value, end_date: end.value }, { preserveState: true, replace: true });
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    const [year, month, day] = dateStr.split('-');
    if (year && month && day) {
        return `${day}/${month}/${year}`;
    }
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function printReport() {
    window.print();
}

function exportCsv() {
    const rows: string[][] = [];

    rows.push(['RELATORIO ANALITICO DE VENDAS']);
    rows.push([`Periodo: ${formatDate(props.report.period.start_date)} ate ${formatDate(props.report.period.end_date)}`]);
    rows.push([]);

    rows.push(['INDICADORES GERAIS']);
    rows.push(['Total de Vendas', 'Faturamento Bruto', 'Descontos Concedidos', 'Faturamento Liquido', 'Ticket Medio']);
    rows.push([
        String(props.report.totals.total_sales),
        props.report.totals.gross_amount.toFixed(2).replace('.', ','),
        props.report.totals.discount_amount.toFixed(2).replace('.', ','),
        props.report.totals.net_amount.toFixed(2).replace('.', ','),
        props.report.totals.average_ticket.toFixed(2).replace('.', ','),
    ]);
    rows.push([]);

    rows.push(['FORMAS DE PAGAMENTO']);
    rows.push(['Forma de Pagamento', 'Qtd Transacoes', 'Total (R$)', 'Percentual (%)']);
    for (const pm of props.report.payment_methods) {
        rows.push([
            pm.name,
            String(pm.count),
            pm.total_amount.toFixed(2).replace('.', ','),
            `${pm.percentage.toFixed(1).replace('.', ',')}%`,
        ]);
    }
    rows.push([]);

    rows.push(['TOP 10 PRODUTOS MAIS VENDIDOS']);
    rows.push(['Posicao', 'Produto', 'Qtd Vendida', 'Faturamento (R$)', 'Preco Medio (R$)']);
    props.report.top_products.forEach((p, idx) => {
        rows.push([
            `#${idx + 1}`,
            p.name,
            String(p.quantity),
            p.revenue.toFixed(2).replace('.', ','),
            p.average_price.toFixed(2).replace('.', ','),
        ]);
    });
    rows.push([]);

    rows.push(['DESEMPENHO POR OPERADOR / VENDEDOR']);
    rows.push(['Operador', 'Email', 'Vendas Concluidas', 'Total Faturado (R$)', 'Descontos (R$)', 'Ticket Medio (R$)']);
    for (const s of props.report.sellers) {
        rows.push([
            s.name,
            s.email,
            String(s.sales_count),
            s.total_amount.toFixed(2).replace('.', ','),
            s.total_discount.toFixed(2).replace('.', ','),
            s.average_ticket.toFixed(2).replace('.', ','),
        ]);
    }
    rows.push([]);

    rows.push(['HISTORICO DIARIO']);
    rows.push(['Data', 'Qtd Vendas', 'Faturamento Liquido (R$)', 'Desconto (R$)', 'Ticket Medio (R$)']);
    for (const d of props.report.daily_sales) {
        rows.push([
            formatDate(d.date),
            String(d.sales_count),
            d.total_amount.toFixed(2).replace('.', ','),
            d.total_discount.toFixed(2).replace('.', ','),
            d.average_ticket.toFixed(2).replace('.', ','),
        ]);
    }

    const csvContent = '\uFEFF' + rows.map((r) => r.map((c) => `"${c.replace(/"/g, '""')}"`).join(';')).join('\n');
    const blob = new window.Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `relatorio_vendas_${props.report.period.start_date}_a_${props.report.period.end_date}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
</script>

<template>
    <AppLayout title="Relatório de Vendas">
        <Head title="Relatório Analítico de Vendas" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Relatório de Vendas</h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Inteligência comercial, distribuição de pagamentos e análise de desempenho por período.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 print:hidden">
                    <AppButton variant="secondary" size="md" @click="exportCsv">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar CSV
                    </AppButton>
                    <AppButton variant="secondary" size="md" @click="printReport">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Imprimir
                    </AppButton>
                    <Link href="/sales">
                        <AppButton variant="primary" size="md">
                            &larr; Voltar para Vendas
                        </AppButton>
                    </Link>
                </div>
            </div>

            <!-- Date Filter Filter & Quick Buttons -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 print:hidden">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase mr-1">Atalhos:</span>
                        <button
                            type="button"
                            @click="setFilterToday"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition-colors cursor-pointer"
                        >
                            Hoje
                        </button>
                        <button
                            type="button"
                            @click="setFilterThisWeek"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition-colors cursor-pointer"
                        >
                            Esta Semana
                        </button>
                        <button
                            type="button"
                            @click="setFilterThisMonth"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition-colors cursor-pointer"
                        >
                            Este Mês
                        </button>
                        <button
                            type="button"
                            @click="setFilterLast30Days"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition-colors cursor-pointer"
                        >
                            Últimos 30 Dias
                        </button>
                    </div>

                    <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 uppercase mb-0.5">De</label>
                                <input
                                    v-model="start"
                                    type="date"
                                    class="rounded-lg border-slate-200 px-3 py-1.5 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 uppercase mb-0.5">Até</label>
                                <input
                                    v-model="end"
                                    type="date"
                                    class="rounded-lg border-slate-200 px-3 py-1.5 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>
                        <AppButton variant="primary" size="sm" class="w-full sm:w-auto mt-4 sm:mt-3.5" @click="filterPeriod">
                            Filtrar
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-500 uppercase">Total de Vendas</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ report.totals.total_sales }}</p>
                    <p class="text-xs text-slate-400 mt-1">Vendas concluídas</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-500 uppercase">Faturamento Bruto</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ formatMoney(report.totals.gross_amount) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Antes dos descontos</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-500 uppercase">Descontos</span>
                    <p class="text-2xl font-semibold text-rose-600 mt-2">{{ formatMoney(report.totals.discount_amount) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Total abatido</p>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5">
                    <span class="text-xs font-medium text-emerald-800 uppercase">Faturamento Líquido</span>
                    <p class="text-2xl font-semibold text-emerald-700 mt-2">{{ formatMoney(report.totals.net_amount) }}</p>
                    <p class="text-xs text-emerald-600/80 mt-1">Valor efetivo apurado</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-500 uppercase">Ticket Médio</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ formatMoney(report.totals.average_ticket) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Por venda concluída</p>
                </div>
            </div>

            <!-- Two-column grid: Payment Methods & Top Products -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Payment Methods Distribution -->
                <div class="lg:col-span-5 rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Formas de Pagamento</h2>
                            <p class="text-xs text-slate-500">Distribuição financeira do período</p>
                        </div>
                        <Badge variant="neutral">{{ report.payment_methods.length }} métodos</Badge>
                    </div>

                    <div v-if="report.payment_methods.length" class="space-y-4">
                        <div
                            v-for="pm in report.payment_methods"
                            :key="pm.id"
                            class="space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-800">{{ pm.name }}</span>
                                <div class="text-right space-x-2">
                                    <span class="font-semibold text-slate-900">{{ formatMoney(pm.total_amount) }}</span>
                                    <span class="text-slate-400">({{ pm.percentage }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-slate-900 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${pm.percentage}%` }"
                                />
                            </div>
                            <div class="text-[11px] text-slate-400 text-right">
                                {{ pm.count }} transação(ões)
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-400 text-xs">
                        Nenhum pagamento registrado no período.
                    </div>
                </div>

                <!-- Top 10 Products -->
                <div class="lg:col-span-7 rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Top 10 Produtos Mais Vendidos</h2>
                            <p class="text-xs text-slate-500">Ordenados pelo volume de unidades comercializadas</p>
                        </div>
                        <Badge variant="info">Top {{ report.top_products.length }}</Badge>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="uppercase bg-slate-50 text-slate-500 font-medium border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Produto</th>
                                    <th class="py-2.5 px-3 text-center">Qtd</th>
                                    <th class="py-2.5 px-3 text-right">Preço Médio</th>
                                    <th class="py-2.5 px-3 text-right">Faturamento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(product, idx) in report.top_products"
                                    :key="product.id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="py-2.5 px-3 font-semibold text-slate-400">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-900">{{ product.name }}</td>
                                    <td class="py-2.5 px-3 text-center font-semibold text-slate-800">{{ product.quantity }}</td>
                                    <td class="py-2.5 px-3 text-right tabular-nums text-slate-500">{{ formatMoney(product.average_price) }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold tabular-nums text-slate-900">{{ formatMoney(product.revenue) }}</td>
                                </tr>
                                <tr v-if="!report.top_products.length">
                                    <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                        Nenhum item comercializado no período.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Operators / Sellers Performance -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Desempenho por Operador / Vendedor</h2>
                        <p class="text-xs text-slate-500">Métricas individuais de vendas, faturamento e concessão de descontos</p>
                    </div>
                    <Badge variant="neutral">{{ report.sellers.length }} vendedores</Badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="uppercase bg-slate-50 text-slate-500 font-medium border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Operador</th>
                                <th class="py-2.5 px-4">E-mail</th>
                                <th class="py-2.5 px-4 text-center">Vendas Concluídas</th>
                                <th class="py-2.5 px-4 text-right">Descontos Concedidos</th>
                                <th class="py-2.5 px-4 text-right">Ticket Médio</th>
                                <th class="py-2.5 px-4 text-right">Faturamento Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="seller in report.sellers"
                                :key="seller.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ seller.name }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ seller.email }}</td>
                                <td class="py-3 px-4 text-center font-medium text-slate-800">{{ seller.sales_count }}</td>
                                <td class="py-3 px-4 text-right tabular-nums text-rose-600">{{ formatMoney(seller.total_discount) }}</td>
                                <td class="py-3 px-4 text-right tabular-nums text-slate-700">{{ formatMoney(seller.average_ticket) }}</td>
                                <td class="py-3 px-4 text-right font-semibold tabular-nums text-emerald-700">{{ formatMoney(seller.total_amount) }}</td>
                            </tr>
                            <tr v-if="!report.sellers.length">
                                <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                    Nenhuma operação registrada para o período selecionado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Evolution -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Evolução Diária do Período</h2>
                        <p class="text-xs text-slate-500">Detalhamento dia a dia das vendas registradas</p>
                    </div>
                    <Badge variant="neutral">{{ report.daily_sales.length }} dias com vendas</Badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="uppercase bg-slate-50 text-slate-500 font-medium border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Data</th>
                                <th class="py-2.5 px-4 text-center">Vendas</th>
                                <th class="py-2.5 px-4 text-right">Total Descontos</th>
                                <th class="py-2.5 px-4 text-right">Ticket Médio</th>
                                <th class="py-2.5 px-4 text-right">Faturamento Líquido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="day in report.daily_sales"
                                :key="day.date"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-2.5 px-4 font-semibold text-slate-900">{{ formatDate(day.date) }}</td>
                                <td class="py-2.5 px-4 text-center font-medium text-slate-800">{{ day.sales_count }}</td>
                                <td class="py-2.5 px-4 text-right tabular-nums text-slate-500">{{ formatMoney(day.total_discount) }}</td>
                                <td class="py-2.5 px-4 text-right tabular-nums text-slate-700">{{ formatMoney(day.average_ticket) }}</td>
                                <td class="py-2.5 px-4 text-right font-semibold tabular-nums text-slate-900">{{ formatMoney(day.total_amount) }}</td>
                            </tr>
                            <tr v-if="!report.daily_sales.length">
                                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                    Nenhuma venda diária encontrada no período.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
