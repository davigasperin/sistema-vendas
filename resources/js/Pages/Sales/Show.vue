<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Sale } from '@/types';

const props = defineProps<{
    sale: Sale;
}>();

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function cancelSale() {
    if (!confirm('Deseja realmente cancelar esta venda? O estoque dos itens será estornado automaticamente.')) {
        return;
    }

    router.delete(`/sales/${props.sale.id}`);
}
</script>

<template>
    <AppLayout :title="`Venda #${sale.id}`">
        <Head :title="`Venda #${sale.id}`" />

        <div class="max-w-5xl mx-auto space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Venda #{{ sale.id }}</h1>
                    <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'">
                        {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
                    </Badge>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        :href="`/sales/${sale.id}/pdf`"
                        target="_blank"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
                    >
                        Baixar PDF
                    </a>
                    <button
                        v-if="sale.status === 'completed'"
                        type="button"
                        @click="cancelSale"
                        class="px-4 py-2 rounded-xl bg-rose-50 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors"
                    >
                        Cancelar Venda
                    </button>
                    <Link
                        href="/sales"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ml-2"
                    >
                        &larr; Voltar
                    </Link>
                </div>
            </div>

            <!-- Details Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Cliente</span>
                    <p class="text-base font-bold text-slate-900 mt-1">{{ sale.customer?.name || 'Cliente Avulso' }}</p>
                    <p class="text-xs text-slate-500">{{ sale.customer?.email || sale.customer?.phone || '-' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Forma de Pagamento</span>
                    <p class="text-base font-bold text-slate-900 mt-1">{{ sale.payment_method?.name || '-' }}</p>
                    <p class="text-xs text-slate-500">{{ sale.installments }}x parcela(s)</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Líquido</span>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ formatMoney(sale.total_amount) }}</p>
                    <p v-if="sale.discount > 0" class="text-xs text-emerald-600">Desconto: {{ formatMoney(sale.discount) }}</p>
                </div>
            </div>

            <!-- Items -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">Itens do Pedido</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500">
                            <tr>
                                <th class="py-3 px-4">Produto</th>
                                <th class="py-3 px-4 text-center">Quantidade</th>
                                <th class="py-3 px-4 text-right">Valor Unitário</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in sale.items || []" :key="item.id">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ item.product?.name || 'Item' }}</td>
                                <td class="py-3 px-4 text-center">{{ item.quantity }}</td>
                                <td class="py-3 px-4 text-right">{{ formatMoney(item.unit_price) }}</td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">{{ formatMoney(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Installments -->
            <div v-if="sale.sale_installments?.length" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">Detalhamento de Parcelas</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                        v-for="inst in sale.sale_installments"
                        :key="inst.id"
                        class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between"
                    >
                        <div>
                            <span class="text-xs font-bold text-slate-800">{{ inst.installment_number }}ª Parcela</span>
                            <p class="text-xs text-slate-400">Vencimento: {{ formatDate(inst.due_date) }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-slate-900 block">{{ formatMoney(inst.amount) }}</span>
                            <Badge :variant="inst.is_paid ? 'success' : 'warning'" size="sm">
                                {{ inst.is_paid ? 'Paga' : 'Pendente' }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
