<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Customer, Sale } from '@/types';

interface CustomerWithSales extends Customer {
    sales?: Sale[];
}

defineProps<{
    customer: CustomerWithSales;
}>();

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}
</script>

<template>
    <AppLayout :title="customer.name">
        <Head :title="`Cliente: ${customer.name}`" />

        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">{{ customer.name }}</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ customer.email || 'Sem e-mail' }} &bull; {{ customer.phone || 'Sem telefone' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/customers/${customer.id}/edit`"
                        class="px-4 py-2 rounded-lg bg-slate-800 text-xs font-semibold text-white hover:bg-slate-900 transition-colors"
                    >
                        Editar
                    </Link>
                    <Link
                        href="/customers"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ml-2"
                    >
                        &larr; Voltar
                    </Link>
                </div>
            </div>

            <!-- Customer Details Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 mb-8">
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">Dados Cadastrais</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Endereço</span>
                        <span class="font-medium text-slate-800">{{ customer.address || 'Não cadastrado' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Nascimento / Idade</span>
                        <span class="font-medium text-slate-800">
                            {{ formatDate(customer.birth_date) }}
                            <span v-if="customer.age" class="text-slate-400">({{ customer.age }} anos)</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Total de Compras</span>
                        <span class="font-semibold text-slate-900">{{ customer.sales?.length || 0 }} pedidos</span>
                    </div>
                </div>
            </div>

            <!-- Purchases History -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-base font-semibold text-slate-900">Histórico de Compras</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500 font-medium border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">#ID Venda</th>
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4">Itens</th>
                                <th class="py-3 px-4 text-right">Valor Total</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="sale in customer.sales || []" :key="sale.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">#{{ sale.id }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ formatDate(sale.created_at) }}</td>
                                <td class="py-3.5 px-4 text-xs">
                                    <div v-for="item in sale.items || []" :key="item.id" class="line-clamp-1">
                                        {{ item.quantity }}x {{ item.product?.name || 'Item' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900">{{ formatMoney(sale.total_amount) }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'">
                                        {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
                                    </Badge>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <Link :href="`/sales/${sale.id}`" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                        Ver Pedido
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!customer.sales?.length">
                                <td colspan="6" class="py-8 text-center text-slate-400 text-sm">Nenhuma compra registrada para este cliente.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
