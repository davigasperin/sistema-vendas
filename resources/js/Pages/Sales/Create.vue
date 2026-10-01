<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Customer, PaymentMethod, Product } from '@/types';

interface ItemRow {
    product_id: number;
    name: string;
    price: number;
    stock: number;
    quantity: number;
}

const props = defineProps<{
    customers: Customer[];
    paymentMethods: PaymentMethod[];
    products: Product[];
}>();

const searchQuery = ref('');
const selectedProductId = ref<number | ''>('');
const selectedQuantity = ref<number>(1);
const items = ref<ItemRow[]>([]);

const form = useForm({
    customer_id: '' as number | '',
    payment_method_id: props.paymentMethods[0]?.id || ('' as number | ''),
    discount: 0,
    installments: 1,
    notes: '',
    items: [] as { product_id: number; quantity: number }[],
    installment_amounts: [] as number[],
    installment_dates: [] as string[],
});

// Filter products based on search
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products.slice(0, 10);
    const q = searchQuery.value.toLowerCase();
    return props.products
        .filter((p) => p.name.toLowerCase().includes(q))
        .slice(0, 10);
});

const currentProduct = computed(() => {
    return props.products.find((p) => p.id === selectedProductId.value) || null;
});

function selectQuickProduct(p: Product) {
    selectedProductId.value = p.id;
    searchQuery.value = p.name;
}

function addItem() {
    if (!currentProduct.value || selectedQuantity.value < 1) return;

    const existingIndex = items.value.findIndex((i) => i.product_id === currentProduct.value!.id);
    if (existingIndex >= 0) {
        const newQty = items.value[existingIndex].quantity + selectedQuantity.value;
        if (newQty > currentProduct.value.stock) {
            alert(`Estoque insuficiente! Disponível: ${currentProduct.value.stock}`);
            return;
        }
        items.value[existingIndex].quantity = newQty;
    } else {
        if (selectedQuantity.value > currentProduct.value.stock) {
            alert(`Estoque insuficiente! Disponível: ${currentProduct.value.stock}`);
            return;
        }
        items.value.push({
            product_id: currentProduct.value.id,
            name: currentProduct.value.name,
            price: Number(currentProduct.value.price),
            stock: currentProduct.value.stock,
            quantity: selectedQuantity.value,
        });
    }

    selectedProductId.value = '';
    searchQuery.value = '';
    selectedQuantity.value = 1;
}

function updateQuantity(index: number, delta: number) {
    const item = items.value[index];
    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        removeItem(index);
        return;
    }
    if (newQty > item.stock) {
        alert(`Estoque máximo disponível: ${item.stock}`);
        return;
    }
    item.quantity = newQty;
}

function removeItem(index: number) {
    items.value.splice(index, 1);
}

const subtotal = computed(() => {
    return items.value.reduce((acc, item) => acc + item.price * item.quantity, 0);
});

const totalAmount = computed(() => {
    return Math.max(0, subtotal.value - Number(form.discount || 0));
});

// Precise cents distribution
const calculatedInstallments = computed(() => {
    const count = Number(form.installments) || 1;
    const totalCents = Math.round(totalAmount.value * 100);
    const baseCents = Math.floor(totalCents / count);
    const remainder = totalCents % count;

    const list: { number: number; amount: number; date: string }[] = [];
    const today = new Date();

    for (let i = 0; i < count; i++) {
        const cents = baseCents + (i < remainder ? 1 : 0);
        const d = new Date(today);
        d.setMonth(d.getMonth() + i + 1);

        list.push({
            number: i + 1,
            amount: cents / 100,
            date: d.toISOString().split('T')[0],
        });
    }

    return list;
});

watch(
    calculatedInstallments,
    (newList) => {
        form.installment_amounts = newList.map((i) => i.amount);
        form.installment_dates = newList.map((i) => i.date);
    },
    { immediate: true }
);

function submit() {
    if (!items.value.length) {
        alert('Adicione ao menos um item à venda.');
        return;
    }

    form.items = items.value.map((i) => ({
        product_id: i.product_id,
        quantity: i.quantity,
    }));

    form.post('/sales');
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
</script>

<template>
    <AppLayout title="Nova Venda">
        <Head title="PDV - Nova Venda" />

        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/80">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">Ponto de Venda (PDV)</h1>
                        <p class="text-xs text-slate-500">Emissão ágil de pedido com concorrência e autoridade de preço do servidor.</p>
                    </div>
                </div>

                <Link
                    href="/sales"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar para Histórico
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Catalog and Cart Builder -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Customer & Payment Setup -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5">Cliente (Opcional)</label>
                                <select
                                    v-model="form.customer_id"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
                                >
                                    <option value="">Consumidor Final (Avulso)</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5">Forma de Pagamento *</label>
                                <select
                                    v-model="form.payment_method_id"
                                    required
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer font-medium"
                                >
                                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Quick Selection -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs space-y-4">
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">1. Adicionar Produtos</span>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                            <div class="sm:col-span-8 relative">
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Buscar por Nome</label>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Digite o nome do produto..."
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all"
                                />

                                <!-- Live Autocomplete dropdown -->
                                <div
                                    v-if="searchQuery && filteredProducts.length"
                                    class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl border border-slate-200 shadow-lg z-30 max-h-48 overflow-y-auto divide-y divide-slate-100"
                                >
                                    <button
                                        v-for="p in filteredProducts"
                                        :key="p.id"
                                        type="button"
                                        @click="selectQuickProduct(p)"
                                        class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center justify-between text-xs transition-colors cursor-pointer"
                                    >
                                        <span class="font-medium text-slate-800">{{ p.name }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400">Estoque: {{ p.stock }}</span>
                                            <span class="font-bold text-slate-900 tabular-nums">{{ formatMoney(p.price) }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Quantidade</label>
                                <input
                                    v-model.number="selectedQuantity"
                                    type="number"
                                    min="1"
                                    :max="currentProduct?.stock || 999"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-center font-bold tabular-nums focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <AppButton
                                    variant="primary"
                                    size="sm"
                                    class="w-full py-2"
                                    :disabled="!selectedProductId"
                                    @click="addItem"
                                >
                                    + Inserir
                                </AppButton>
                            </div>
                        </div>

                        <div v-if="currentProduct" class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-100 flex items-center justify-between text-xs text-blue-900">
                            <span>Item Selecionado: <strong>{{ currentProduct.name }}</strong></span>
                            <span>Valor Oficial: <strong>{{ formatMoney(currentProduct.price) }}</strong> | Disponível: <strong>{{ currentProduct.stock }}</strong></span>
                        </div>
                    </div>

                    <!-- Items Cart Table -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                            <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">2. Itens no Pedido ({{ items.length }})</span>
                            <span class="text-xs text-slate-400">Preço recalculado pelo servidor</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="uppercase bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-4">Item</th>
                                        <th class="py-2.5 px-4 text-center w-28">Quantidade</th>
                                        <th class="py-2.5 px-4 text-right">Unitário</th>
                                        <th class="py-2.5 px-4 text-right">Subtotal</th>
                                        <th class="py-2.5 px-4 text-center w-12">Remover</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-slate-900">
                                            {{ item.name }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="updateQuantity(idx, -1)"
                                                    class="h-6 w-6 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors cursor-pointer"
                                                >
                                                    -
                                                </button>
                                                <span class="w-8 text-center font-bold tabular-nums text-slate-900">{{ item.quantity }}</span>
                                                <button
                                                    type="button"
                                                    @click="updateQuantity(idx, 1)"
                                                    class="h-6 w-6 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors cursor-pointer"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-right tabular-nums">{{ formatMoney(item.price) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-900 tabular-nums">{{ formatMoney(item.price * item.quantity) }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <button
                                                type="button"
                                                @click="removeItem(idx)"
                                                class="text-rose-400 hover:text-rose-600 font-bold text-sm cursor-pointer p-1"
                                                title="Remover item"
                                            >
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!items.length">
                                        <td colspan="5" class="py-10 text-center text-slate-400">
                                            Nenhum produto adicionado. Use o campo acima para buscar e inserir produtos.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary & Checkout Card -->
                <div class="space-y-5">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs space-y-5 sticky top-8">
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block border-b border-slate-100 pb-3">Resumo Financeiro</span>

                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between text-slate-500 font-medium">
                                <span>Subtotal Bruto</span>
                                <span class="font-bold text-slate-900 tabular-nums">{{ formatMoney(subtotal) }}</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Desconto Aplicado (R$)</label>
                                <input
                                    v-model.number="form.discount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="subtotal"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900 font-bold tabular-nums focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                                    placeholder="0,00"
                                />
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-slate-900">Total Líquido</span>
                                <span class="text-2xl font-black text-blue-600 tabular-nums tracking-tight">{{ formatMoney(totalAmount) }}</span>
                            </div>
                        </div>

                        <!-- Installments Options -->
                        <div class="pt-4 border-t border-slate-100 space-y-2.5">
                            <label class="block text-[11px] font-semibold text-slate-500">Parcelamento</label>
                            <select
                                v-model.number="form.installments"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 font-semibold focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 cursor-pointer"
                            >
                                <option v-for="n in 12" :key="n" :value="n">
                                    {{ n }}x {{ n === 1 ? 'à vista' : 'mensais' }}
                                </option>
                            </select>

                            <!-- Breakdown preview without cent loss -->
                            <div v-if="form.installments > 1" class="rounded-xl bg-slate-50 p-3 space-y-1 text-[11px] border border-slate-100">
                                <div class="font-semibold text-slate-700 mb-1">Cronograma de Vencimentos:</div>
                                <div
                                    v-for="inst in calculatedInstallments"
                                    :key="inst.number"
                                    class="flex justify-between text-slate-600 tabular-nums"
                                >
                                    <span>{{ inst.number }}ª Parcela ({{ inst.date }})</span>
                                    <span class="font-bold text-slate-900">{{ formatMoney(inst.amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Observações Internas</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Anotações de balcão..."
                                class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 placeholder:text-slate-400"
                            />
                        </div>

                        <!-- Submit Button -->
                        <AppButton
                            variant="primary"
                            size="lg"
                            class="w-full py-3"
                            :loading="form.processing"
                            :disabled="!items.length"
                            @click="submit"
                        >
                            Finalizar e Emitir Pedido
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
