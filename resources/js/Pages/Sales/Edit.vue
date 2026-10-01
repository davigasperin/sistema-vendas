<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Sale, Customer, PaymentMethod, Product, SaleItem } from '@/types';

interface ItemRow {
    product_id: number;
    name: string;
    price: number;
    stock: number;
    quantity: number;
}

const props = defineProps<{
    sale: Sale;
    customers: Customer[];
    products: Product[];
    paymentMethods: PaymentMethod[];
}>();

const selectedProductId = ref<number | ''>('');
const selectedQuantity = ref<number>(1);

const items = ref<ItemRow[]>(
    (props.sale.items || []).map((item: SaleItem) => ({
        product_id: item.product_id,
        name: item.product?.name || `Produto #${item.product_id}`,
        price: Number(item.unit_price),
        stock: item.product?.stock || 0,
        quantity: item.quantity,
    }))
);

const form = useForm({
    customer_id: props.sale.customer_id || ('' as number | ''),
    payment_method_id: props.sale.payment_method_id,
    discount: Number(props.sale.discount),
    installments: props.sale.installments,
    notes: props.sale.notes || '',
    items: [] as { product_id: number; quantity: number }[],
    installment_amounts: [] as number[],
    installment_dates: [] as string[],
});

const currentProduct = computed(() => props.products.find((p) => p.id === selectedProductId.value) || null);

function addItem() {
    if (!currentProduct.value || selectedQuantity.value < 1) return;

    const existingIndex = items.value.findIndex((i) => i.product_id === currentProduct.value!.id);
    if (existingIndex >= 0) {
        items.value[existingIndex].quantity += selectedQuantity.value;
    } else {
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

const subtotal = computed(() => items.value.reduce((acc, item) => acc + item.price * item.quantity, 0));
const totalAmount = computed(() => Math.max(0, subtotal.value - Number(form.discount || 0)));

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
        list.push({ number: i + 1, amount: cents / 100, date: d.toISOString().split('T')[0] });
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
    form.items = items.value.map((i) => ({ product_id: i.product_id, quantity: i.quantity }));
    form.put(`/sales/${props.sale.id}`);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
</script>

<template>
    <AppLayout :title="`Editar Venda #${sale.id}`">
        <Head :title="`Editar Venda #${sale.id}`" />

        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar Venda #{{ sale.id }}</h1>
                    <Badge variant="warning">Editando</Badge>
                </div>
                <Link href="/sales" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                    &larr; Voltar
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">1. Cliente e Pagamento</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Cliente</label>
                                <select v-model="form.customer_id" class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Cliente Avulso</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Forma de Pagamento *</label>
                                <select v-model="form.payment_method_id" class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">2. Itens</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Adicionar Produto</label>
                                <select v-model="selectedProductId" class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Selecione...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (Estoque: {{ p.stock }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Qtd</label>
                                <input v-model.number="selectedQuantity" type="number" min="1" class="w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <button type="button" @click="addItem" :disabled="!selectedProductId" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-40 transition-colors">
                                + Adicionar
                            </button>
                        </div>

                        <table class="w-full text-left text-sm text-slate-600 mt-4">
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
                                <tr v-for="(item, idx) in items" :key="item.product_id">
                                    <td class="py-2.5 px-3 font-semibold text-slate-800">{{ item.name }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ item.quantity }}</td>
                                    <td class="py-2.5 px-3 text-right">{{ formatMoney(item.price) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ formatMoney(item.price * item.quantity) }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                    </td>
                                </tr>
                                <tr v-if="!items.length">
                                    <td colspan="5" class="py-6 text-center text-slate-400 text-xs">Nenhum item na venda.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-5 sticky top-8">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Resumo</h2>
                        <div class="flex justify-between text-slate-500 text-sm">
                            <span>Subtotal</span>
                            <span class="font-semibold text-slate-900">{{ formatMoney(subtotal) }}</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Desconto (R$)</label>
                            <input v-model.number="form.discount" type="number" step="0.01" min="0" class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                            <span class="text-base font-bold text-slate-900">Total</span>
                            <span class="text-2xl font-extrabold text-blue-600">{{ formatMoney(totalAmount) }}</span>
                        </div>

                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Parcelas</label>
                            <select v-model.number="form.installments" class="w-full rounded-xl border-slate-200 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || !items.length"
                            class="w-full rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
