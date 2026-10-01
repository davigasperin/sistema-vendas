<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Modal from '@/Components/UI/Modal.vue';
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
        <Head title="Gerenciamento de Produtos" />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Produtos</h1>
                <p class="text-sm text-slate-500 mt-0.5">Catálogo de itens, níveis de estoque e controle de disponibilidade.</p>
            </div>
            <Link
                href="/products/create"
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition-colors"
            >
                + Novo Produto
            </Link>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total Cadastrado</span>
                <span class="text-lg font-bold text-slate-900">{{ totalProducts }}</span>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Produtos Ativos</span>
                <Badge variant="success">{{ activeProducts }}</Badge>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Estoque Crítico</span>
                <Badge :variant="lowStock > 0 ? 'danger' : 'neutral'">{{ lowStock }}</Badge>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-6 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:max-w-md relative">
                <input
                    v-model="search"
                    @keyup.enter="handleSearch"
                    type="text"
                    placeholder="Buscar por nome do produto..."
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <button
                @click="handleSearch"
                type="button"
                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-100 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
            >
                Filtrar
            </button>
        </div>

        <!-- Table -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Nome</th>
                            <th class="py-3 px-4">Preço</th>
                            <th class="py-3 px-4 text-center">Estoque</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div>{{ product.name }}</div>
                                <div class="text-xs font-normal text-slate-400 line-clamp-1">{{ product.description || 'Sem descrição' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">{{ formatMoney(product.price) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                    :class="product.stock <= product.low_stock_threshold ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800'"
                                >
                                    {{ product.stock }} un
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button
                                    type="button"
                                    @click="toggleActive(product)"
                                    class="cursor-pointer transition-opacity hover:opacity-80"
                                >
                                    <Badge :variant="product.active ? 'success' : 'neutral'">
                                        {{ product.active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <button
                                    type="button"
                                    @click="openAdjustModal(product)"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                                >
                                    Ajustar Estoque
                                </button>
                                <Link
                                    :href="`/products/${product.id}`"
                                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Detalhes
                                </Link>
                                <Link
                                    :href="`/products/${product.id}/edit`"
                                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">Nenhum produto encontrado.</td>
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
            <div class="space-y-4">
                <p class="text-xs text-slate-500">
                    Estoque atual: <strong class="text-slate-900">{{ adjustingProduct?.stock }}</strong> unidades.
                    Informe um valor positivo para adicionar ou negativo para reduzir.
                </p>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Quantidade de Ajuste</label>
                    <input
                        v-model.number="adjustmentAmount"
                        type="number"
                        class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ex: 5 ou -2"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <button
                        type="button"
                        @click="adjustingProduct = null"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="submitAdjustment"
                        :disabled="isSubmittingAdjustment || adjustmentAmount === 0"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ isSubmittingAdjustment ? 'Salvando...' : 'Confirmar Ajuste' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
