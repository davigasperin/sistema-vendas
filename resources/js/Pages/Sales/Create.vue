<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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

const currentProduct = computed(() => {
    return props.products.find((p) => p.id === selectedProductId.value) || null;
});

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
    selectedQuantity.value = 1;
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

// Calculate exact installment distribution without losing cents
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
        <Head title="Registrar Nova Venda" />

        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Nova Venda</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Emissão ágil de pedido com recálculo seguro e concorrência travada.</p>
                </div>
                <Link
                    href="/sales"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    &larr; Voltar para Vendas
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Form (Items & Customer) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer and Payment -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">1. Dados do Cliente e Pagamento</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Cliente</label>
                                <select
                                    v-model="form.customer_id"
                                    class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Cliente Avulso (Não identificado)</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Forma de Pagamento *</label>
                                <select
                                    v-model="form.payment_method_id"
                                    required
                                    class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Items Selection -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">2. Adicionar Itens</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Produto</label>
                                <select
                                    v-model="selectedProductId"
                                    class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Selecione um produto...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} - {{ formatMoney(p.price) }} (Estoque: {{ p.stock }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Quantidade</label>
                                <input
                                    v-model.number="selectedQuantity"
                                    type="number"
                                    min="1"
                                    :max="currentProduct?.stock || 999"
                                    class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <button
                                    type="button"
                                    @click="addItem"
                                    :disabled="!selectedProductId"
                                    class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-40 transition-colors"
                                >
                                    + Adicionar
                                </button>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mt-4 border border-slate-100 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">Item</th>
                                        <th class="py-2.5 px-3 text-center">Qtd</th>
                                        <th class="py-2.5 px-3 text-right">Unitário</th>
                                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                                        <th class="py-2.5 px-3 text-center">Remover</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(item, idx) in items" :key="idx">
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">{{ item.name }}</td>
                                        <td class="py-2.5 px-3 text-center">{{ item.quantity }}</td>
                                        <td class="py-2.5 px-3 text-right">{{ formatMoney(item.price) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                            {{ formatMoney(item.price * item.quantity) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <button
                                                type="button"
                                                @click="removeItem(idx)"
                                                class="text-rose-500 hover:text-rose-700 font-bold"
                                            >
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!items.length">
                                        <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                            Nenhum produto adicionado ao pedido.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Observações da Venda</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Informações adicionais, detalhes de entrega ou pagamento..."
                        />
                    </div>
                </div>

                <!-- Sidebar Summary and Installments -->
                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-5 sticky top-8">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Resumo Financeiro</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-slate-500">
                                <span>Subtotal Bruto</span>
                                <span class="font-semibold text-slate-900">{{ formatMoney(subtotal) }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Desconto (R$)</label>
                                <input
                                    v-model.number="form.discount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="subtotal"
                                    class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                                <span class="text-base font-bold text-slate-900">Total Líquido</span>
                                <span class="text-2xl font-extrabold text-blue-600">{{ formatMoney(totalAmount) }}</span>
                            </div>
                        </div>

                        <!-- Installments Section -->
                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Número de Parcelas</label>
                            <select
                                v-model.number="form.installments"
                                class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option v-for="n in 12" :key="n" :value="n">
                                    {{ n }}x {{ n === 1 ? 'à vista' : '' }}
                                </option>
                            </select>

                            <div v-if="form.installments > 1" class="rounded-xl bg-slate-50 p-3 space-y-1.5 text-xs border border-slate-100">
                                <div class="font-semibold text-slate-700 mb-1">Previsão das Parcelas:</div>
                                <div
                                    v-for="inst in calculatedInstallments"
                                    :key="inst.number"
                                    class="flex justify-between text-slate-600"
                                >
                                    <span>{{ inst.number }}ª Parcela ({{ inst.date }})</span>
                                    <span class="font-medium text-slate-900">{{ formatMoney(inst.amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || !items.length"
                            class="w-full rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Processando Venda...' : 'Finalizar Venda' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
