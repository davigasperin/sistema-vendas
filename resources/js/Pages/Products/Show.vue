<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Product } from '@/types';

defineProps<{
    product: Product;
    totalSold: number;
    totalRevenue: number;
}>();

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
</script>

<template>
    <AppLayout :title="product.name">
        <Head :title="`Produto: ${product.name}`" />

        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ product.name }}</h1>
                    <Badge :variant="product.active ? 'success' : 'neutral'">
                        {{ product.active ? 'Ativo' : 'Inativo' }}
                    </Badge>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/products/${product.id}/edit`"
                        class="px-4 py-2 rounded-lg bg-slate-800 text-xs font-semibold text-white hover:bg-slate-900 transition-colors"
                    >
                        Editar
                    </Link>
                    <Link
                        href="/products"
                        class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors ml-2"
                    >
                        &larr; Voltar
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="p-5 rounded-xl border border-slate-200 bg-white">
                    <span class="text-xs font-medium text-slate-500">Estoque Atual</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ product.stock }} unidades</p>
                </div>
                <div class="p-5 rounded-xl border border-slate-200 bg-white">
                    <span class="text-xs font-medium text-slate-500">Total Vendido</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ totalSold }} unidades</p>
                </div>
                <div class="p-5 rounded-xl border border-slate-200 bg-white">
                    <span class="text-xs font-medium text-slate-500">Faturamento Acumulado</span>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">{{ formatMoney(totalRevenue) }}</p>
                </div>
            </div>

            <!-- Details Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Informações do Item</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Preço de Venda</span>
                        <span class="font-semibold text-slate-900">{{ formatMoney(product.price) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Limite de Estoque Baixo</span>
                        <span class="font-semibold text-slate-900">{{ product.low_stock_threshold }} unidades</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs text-slate-400 block">Descrição</span>
                        <p class="text-slate-600 mt-1">{{ product.description || 'Nenhuma descrição cadastrada.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
