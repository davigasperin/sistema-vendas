<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import type { Sale, Customer, PaymentMethod, PaginatedData } from '@/types';

const props = defineProps<{
    sales: PaginatedData<Sale>;
    customers: Customer[];
    paymentMethods: PaymentMethod[];
    filters?: {
        customer_id?: string;
        payment_method_id?: string;
        date_from?: string;
        date_to?: string;
        trashed?: string;
    };
}>();

const customerId = ref(props.filters?.customer_id || '');
const paymentMethodId = ref(props.filters?.payment_method_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyFilters() {
    router.get(
        '/sales',
        {
            customer_id: customerId.value,
            payment_method_id: paymentMethodId.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    customerId.value = '';
    paymentMethodId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/sales');
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}
</script>

<template>
    <AppLayout title="Vendas">
        <Head title="Gerenciamento de Vendas" />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Vendas</h1>
                <p class="text-sm text-slate-500 mt-0.5">Histórico completo de pedidos, faturamento e emissão de notas/PDF.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <Link href="/sales/report">
                    <AppButton variant="secondary" size="md">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Relatório
                    </AppButton>
                </Link>
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

        <!-- Filter Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Cliente</label>
                    <select
                        v-model="customerId"
                        class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <option value="">Todos os clientes</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Forma de Pagamento</label>
                    <select
                        v-model="paymentMethodId"
                        class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <option value="">Todas as formas</option>
                        <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Data Inicial</label>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Data Final</label>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-100">
                <AppButton variant="secondary" size="sm" @click="resetFilters">
                    Limpar
                </AppButton>
                <AppButton variant="primary" size="sm" @click="applyFilters">
                    Filtrar Resultados
                </AppButton>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">#ID</th>
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Pagamento</th>
                            <th class="py-3 px-4">Parcelamento</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-slate-900">#{{ sale.id }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ formatDate(sale.created_at) }}</td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                {{ sale.customer?.name || 'Cliente Avulso' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">{{ sale.payment_method?.name || '-' }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                {{ sale.installments }}x
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-slate-900">
                                {{ formatMoney(sale.total_amount) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'">
                                    {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
                                </Badge>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a
                                    :href="`/sales/${sale.id}/pdf`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                                >
                                    PDF
                                </a>
                                <Link
                                    :href="`/sales/${sale.id}`"
                                    class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                                >
                                    Ver Detalhes
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!sales.data.length">
                            <td colspan="8" class="py-8 text-center text-slate-400 text-sm">Nenhuma venda encontrada com os filtros aplicados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-slate-100 px-4">
                <Pagination :links="sales.links || []" />
            </div>
        </div>
    </AppLayout>
</template>
