<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Modal from '@/Components/UI/Modal.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import type { Product, PaginatedData } from '@/types';

const props = defineProps<{
    products: PaginatedData<Product>;
    totalProducts: number;
    activeProducts: number;
    lowStock: number;
    filters?: { search?: string; active?: string };
}>();

const search = ref(props.filters?.search || '');
const adjustingProduct = ref<Product | null>(null);
const adjustmentAmount = ref(0);
const isSubmittingAdjustment = ref(false);

function handleSearch() {
    router.get('/products', { search: search.value }, { preserveState: true, replace: true });
}

function openAdjustModal(product: Product) {
    adjustingProduct.value = product;
    adjustmentAmount.value = 0;
}

function submitAdjustment() {
    if (!adjustingProduct.value || adjustmentAmount.value === 0) return;

    isSubmittingAdjustment.value = true;
    router.patch(
        `/products/${adjustingProduct.value.id}/adjust-stock`,
        { adjustment: adjustmentAmount.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmittingAdjustment.value = false;
                adjustingProduct.value = null;
            },
        }
    );
}

function toggleActive(product: Product) {
    router.patch(`/products/${product.id}/toggle-active`, {}, { preserveScroll: true });
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
</script>

<template>
    <AppLayout title="Produtos">
        <Head title="Catálogo de Produtos" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Produtos</h1>
                <p class="text-xs text-slate-500 mt-1">Gerenciamento de estoque, precificação e disponibilidade para vendas.</p>
            </div>
            <Link href="/products/create">
                <AppButton variant="primary" size="md">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Cadastrar Produto
                </AppButton>
            </Link>
        </div>

        <!-- Quick Counters -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="p-4 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase block">Catálogo Total</span>
                    <span class="text-xl font-semibold text-slate-900 tabular-nums">{{ totalProducts }} itens</span>
                </div>
                <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase block">Disponíveis para Venda</span>
                    <span class="text-xl font-semibold text-slate-900 tabular-nums">{{ activeProducts }} ativos</span>
                </div>
                <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase block">Estoque Crítico</span>
                    <span class="text-xl font-semibold tabular-nums" :class="lowStock > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ lowStock }} itens
                    </span>
                </div>
                <div class="h-9 w-9 rounded-lg flex items-center justify-center" :class="lowStock > 0 ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-400'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:max-w-md relative">
                <input
                    v-model="search"
                    @keyup.enter="handleSearch"
                    type="text"
                    placeholder="Buscar produto por nome..."
                    class="w-full rounded-lg border border-slate-200 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors"
                />
            </div>
            <AppButton variant="secondary" size="sm" @click="handleSearch">
                Filtrar Resultados
            </AppButton>
        </div>

        <!-- Data Table -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="uppercase bg-slate-50/80 text-slate-500 font-medium border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Produto</th>
                            <th class="py-3 px-4">Preço Oficial</th>
                            <th class="py-3 px-4 text-center">Nível de Estoque</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Ações Rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ product.name }}</div>
                                <div class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ product.description || 'Sem descrição cadastrada.' }}</div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900 tabular-nums">
                                {{ formatMoney(product.price) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tabular-nums"
                                    :class="[
                                        product.stock <= 0
                                            ? 'text-rose-600'
                                            : product.stock <= product.low_stock_threshold
                                            ? 'text-amber-600'
                                            : 'text-slate-500',
                                    ]"
                                >
                                    {{ product.stock }} unidades
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button
                                    type="button"
                                    @click="toggleActive(product)"
                                    class="cursor-pointer transition-opacity hover:opacity-80"
                                    title="Clique para alternar status"
                                >
                                    <Badge :variant="product.active ? 'success' : 'neutral'" size="sm">
                                        {{ product.active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                </button>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button
                                    type="button"
                                    @click="openAdjustModal(product)"
                                    class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors cursor-pointer"
                                >
                                    Ajustar Estoque
                                </button>
                                <Link
                                    :href="`/products/${product.id}`"
                                    class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Histórico
                                </Link>
                                <Link
                                    :href="`/products/${product.id}/edit`"
                                    class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td colspan="5">
                                <EmptyState
                                    title="Nenhum produto cadastrado"
                                    description="Cadastre seu primeiro produto para iniciar as operações de venda."
                                    action-label="+ Cadastrar Agora"
                                    @action="router.get('/products/create')"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-slate-100 px-4">
                <Pagination :links="products.meta?.links || []" />
            </div>
        </div>

        <!-- Stock Adjust Modal -->
        <Modal
            :show="adjustingProduct !== null"
            :title="`Ajuste de Estoque: ${adjustingProduct?.name || ''}`"
            @close="adjustingProduct = null"
        >
            <div class="space-y-4 text-xs">
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-100 flex justify-between items-center">
                    <span class="text-slate-500 font-medium">Saldo Atual em Prateleira:</span>
                    <span class="font-semibold text-slate-900 tabular-nums text-sm">{{ adjustingProduct?.stock }} unidades</span>
                </div>

                <div>
                    <label class="block text-slate-700 font-medium mb-1">Quantidade a Ajustar</label>
                    <input
                        v-model.number="adjustmentAmount"
                        type="number"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold tabular-nums text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Ex: 5 (entrada) ou -2 (baixa)"
                    />
                    <span class="text-[11px] text-slate-400 mt-1 block">
                        Valores positivos adicionam ao estoque; valores negativos realizam baixa com auditoria.
                    </span>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <AppButton variant="secondary" size="sm" @click="adjustingProduct = null">
                        Cancelar
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="sm"
                        :loading="isSubmittingAdjustment"
                        :disabled="adjustmentAmount === 0"
                        @click="submitAdjustment"
                    >
                        Confirmar Movimentação
                    </AppButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
