<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import type { Customer, PaymentMethod, Product, CashShift, PageProps } from '@/types';

interface ItemRow {
    product_id: number;
    name: string;
    price: number;
    stock: number;
    quantity: number;
}

interface PaymentRow {
    payment_method_id: number;
    amount: number;
    received_amount?: number;
    change_given?: number;
    notes?: string;
}

const props = defineProps<{
    customers: Customer[];
    paymentMethods: PaymentMethod[];
    products: Product[];
    currentShift?: CashShift | null;
}>();

const page = usePage<PageProps>();

// Elements refs for focus shortcuts
const searchInputRef = ref<HTMLInputElement | null>(null);
const customerSelectRef = ref<HTMLSelectElement | null>(null);
const discountInputRef = ref<HTMLInputElement | null>(null);
const firstPaymentInputRef = ref<HTMLInputElement | null>(null);

// State
const searchQuery = ref('');
const selectedProductId = ref<number | ''>('');
const selectedQuantity = ref<number>(1);
const selectedIndexInDropdown = ref<number>(-1);
const items = ref<ItemRow[]>([]);
const isCheckoutOpen = ref<boolean>(false);
const payments = ref<PaymentRow[]>([]);

const form = useForm({
    customer_id: '' as number | '',
    payment_method_id: props.paymentMethods[0]?.id || ('' as number | ''),
    discount: 0,
    installments: 1,
    notes: '',
    items: [] as { product_id: number; quantity: number }[],
    installment_amounts: [] as number[],
    installment_dates: [] as string[],
    payments: [] as {
        payment_method_id: number;
        amount: number;
        change_given?: number;
        notes?: string | null;
    }[],
});

// Product search & autocomplete
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products.slice(0, 10);
    const q = searchQuery.value.toLowerCase().trim();
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
    selectedIndexInDropdown.value = -1;
}

function handleSearchKeydown(e: KeyboardEvent) {
    if (!filteredProducts.value.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndexInDropdown.value = (selectedIndexInDropdown.value + 1) % filteredProducts.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndexInDropdown.value = (selectedIndexInDropdown.value - 1 + filteredProducts.value.length) % filteredProducts.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (selectedIndexInDropdown.value >= 0 && selectedIndexInDropdown.value < filteredProducts.value.length) {
            selectQuickProduct(filteredProducts.value[selectedIndexInDropdown.value]);
            addItem();
        } else if (currentProduct.value) {
            addItem();
        }
    } else if (e.key === 'Escape') {
        searchQuery.value = '';
        selectedIndexInDropdown.value = -1;
    }
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
    selectedIndexInDropdown.value = -1;
    searchInputRef.value?.focus();
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

function resetSale() {
    if (items.value.length > 0 && !confirm('Deseja realmente limpar o carrinho e reiniciar a venda?')) {
        return;
    }
    items.value = [];
    form.reset();
    form.discount = 0;
    form.installments = 1;
    form.notes = '';
    form.customer_id = '';
    form.payment_method_id = props.paymentMethods[0]?.id || '';
    searchQuery.value = '';
    selectedProductId.value = '';
    selectedQuantity.value = 1;
    isCheckoutOpen.value = false;
    payments.value = [];
    searchInputRef.value?.focus();
}

// Totals calculations in cents
const subtotalCents = computed(() => {
    return items.value.reduce((acc, item) => {
        const unitCents = Math.round(item.price * 100);
        return acc + unitCents * item.quantity;
    }, 0);
});

const subtotal = computed(() => subtotalCents.value / 100);

const discountCents = computed(() => {
    const d = Math.round(Number(form.discount || 0) * 100);
    return Math.min(subtotalCents.value, Math.max(0, d));
});

const totalAmountCents = computed(() => {
    return Math.max(0, subtotalCents.value - discountCents.value);
});

const totalAmount = computed(() => totalAmountCents.value / 100);

// Installments calculation
const calculatedInstallments = computed(() => {
    const count = Number(form.installments) || 1;
    const tCents = totalAmountCents.value;
    const baseCents = Math.floor(tCents / count);
    const remainder = tCents % count;

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

// Payments Management
function isCashMethod(methodId: number): boolean {
    const method = props.paymentMethods.find((pm) => pm.id === methodId);
    if (!method) return false;
    return method.name.toLowerCase().includes('dinheiro') || (method.description?.toLowerCase().includes('dinheiro') ?? false);
}

function openCheckout() {
    if (!items.value.length) {
        alert('Adicione ao menos um item ao pedido antes de finalizar.');
        return;
    }

    if (!payments.value.length) {
        const initialMethodId = Number(form.payment_method_id) || props.paymentMethods[0]?.id || 1;
        const isCash = isCashMethod(initialMethodId);
        payments.value = [
            {
                payment_method_id: initialMethodId,
                amount: totalAmount.value,
                received_amount: isCash ? totalAmount.value : undefined,
                change_given: 0,
            },
        ];
    } else {
        // Recalculate change if any cash method exists
        payments.value.forEach((p) => {
            if (isCashMethod(p.payment_method_id)) {
                calculateChange(p);
            }
        });
    }

    isCheckoutOpen.value = true;
    nextTick(() => {
        firstPaymentInputRef.value?.focus();
    });
}

function addPaymentRow() {
    const remaining = remainingPaymentAmount.value;
    // Find first unused payment method if available
    const usedMethodIds = payments.value.map((p) => p.payment_method_id);
    const availableMethod = props.paymentMethods.find((pm) => !usedMethodIds.includes(pm.id)) || props.paymentMethods[0];

    if (!availableMethod) return;

    const initialAmount = remaining > 0 ? Number(remaining.toFixed(2)) : 0;
    const isCash = isCashMethod(availableMethod.id);

    payments.value.push({
        payment_method_id: availableMethod.id,
        amount: initialAmount,
        received_amount: isCash ? initialAmount : undefined,
        change_given: 0,
    });
}

function removePaymentRow(index: number) {
    if (payments.value.length <= 1) return;
    payments.value.splice(index, 1);
}

function onMethodChange(payment: PaymentRow) {
    if (isCashMethod(payment.payment_method_id)) {
        payment.received_amount = payment.amount;
        payment.change_given = 0;
    } else {
        payment.received_amount = undefined;
        payment.change_given = 0;
    }
}

function calculateChange(payment: PaymentRow) {
    if (!isCashMethod(payment.payment_method_id)) {
        payment.change_given = 0;
        return;
    }

    const appliedCents = Math.round((Number(payment.amount) || 0) * 100);
    const receivedCents = Math.round((Number(payment.received_amount) || 0) * 100);

    if (receivedCents > appliedCents) {
        payment.change_given = (receivedCents - appliedCents) / 100;
    } else {
        payment.change_given = 0;
    }
}

function fillRemaining(payment: PaymentRow) {
    const currentAppliedCents = Math.round((Number(payment.amount) || 0) * 100);
    const otherPaymentsCents = paymentsSumCents.value - currentAppliedCents;
    const neededCents = Math.max(0, totalAmountCents.value - otherPaymentsCents);
    payment.amount = neededCents / 100;

    if (isCashMethod(payment.payment_method_id)) {
        if (!payment.received_amount || payment.received_amount < payment.amount) {
            payment.received_amount = payment.amount;
        }
        calculateChange(payment);
    }
}

const paymentsSumCents = computed(() => {
    return payments.value.reduce((acc, p) => {
        return acc + Math.round((Number(p.amount) || 0) * 100);
    }, 0);
});

const paymentsSum = computed(() => paymentsSumCents.value / 100);

const remainingPaymentCents = computed(() => {
    return totalAmountCents.value - paymentsSumCents.value;
});

const remainingPaymentAmount = computed(() => remainingPaymentCents.value / 100);

const isPaymentValid = computed(() => {
    if (!items.value.length) return false;
    if (payments.value.length === 0) return false;
    if (remainingPaymentCents.value !== 0) return false;

    for (const p of payments.value) {
        const amtCents = Math.round((Number(p.amount) || 0) * 100);
        if (amtCents <= 0) return false;
    }

    return true;
});

function submitCheckout() {
    if (!isPaymentValid.value) return;

    form.items = items.value.map((i) => ({
        product_id: i.product_id,
        quantity: i.quantity,
    }));

    form.payments = payments.value.map((p) => ({
        payment_method_id: p.payment_method_id,
        amount: Number(p.amount),
        change_given: p.change_given || 0,
        notes: p.notes || null,
    }));

    form.payment_method_id = payments.value[0]?.payment_method_id || props.paymentMethods[0]?.id || 1;

    form.post('/sales', {
        onSuccess: () => {
            isCheckoutOpen.value = false;
        },
    });
}

// Global Keyboard Shortcuts Handler
function handleGlobalKeydown(e: KeyboardEvent) {
    const isTyping =
        e.target instanceof HTMLInputElement ||
        e.target instanceof HTMLTextAreaElement ||
        e.target instanceof HTMLSelectElement ||
        (e.target as HTMLElement)?.isContentEditable;

    if (e.key === 'F2') {
        e.preventDefault();
        resetSale();
        return;
    }

    if (e.key === 'F3') {
        e.preventDefault();
        searchInputRef.value?.focus();
        return;
    }

    if (e.key === '/' && !isTyping) {
        e.preventDefault();
        searchInputRef.value?.focus();
        return;
    }

    if (e.key === 'F4') {
        e.preventDefault();
        customerSelectRef.value?.focus();
        return;
    }

    if (e.key === 'F8') {
        e.preventDefault();
        discountInputRef.value?.focus();
        return;
    }

    if (e.key === 'F12') {
        e.preventDefault();
        if (isCheckoutOpen.value) {
            submitCheckout();
        } else {
            openCheckout();
        }
        return;
    }

    if (e.key === 'Escape') {
        if (isCheckoutOpen.value) {
            e.preventDefault();
            isCheckoutOpen.value = false;
            searchInputRef.value?.focus();
        } else if (searchQuery.value) {
            searchQuery.value = '';
            selectedIndexInDropdown.value = -1;
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
    nextTick(() => {
        searchInputRef.value?.focus();
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

const userIsSellerWithoutShift = computed(() => {
    const user = page.props.auth.user;
    if (!user) return false;
    return user.role === 'seller' && !user.current_cash_shift && !props.currentShift;
});
</script>

<template>
  <AppLayout title="Ponto de Venda (PDV)">
    <Head title="PDV - Caixa Rápido" />

    <div class="max-w-6xl mx-auto space-y-4">
      <!-- Warning Banner: Seller without open cash shift -->
      <div
        v-if="userIsSellerWithoutShift"
        class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 flex items-center justify-between shadow-sm"
        role="alert"
      >
        <div class="flex items-center gap-3">
          <svg
            class="h-6 w-6 text-amber-600 shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <div>
            <h2 class="text-xs font-bold uppercase tracking-wide">
              Caixa Fechado
            </h2>
            <p class="text-xs text-amber-700">
              Como vendedor, você precisa abrir um turno de caixa antes de registrar vendas.
            </p>
          </div>
        </div>
        <Link
          href="/cashier"
          class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-colors shrink-0"
        >
          Abrir Caixa Agora
        </Link>
      </div>

      <!-- Header & Keyboard Shortcuts Bar -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-slate-200">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold">
            <svg
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
              />
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold text-slate-900">
              Ponto de Venda (PDV)
            </h1>
            <p class="text-xs text-slate-500">
              Checkout ágil com atalhos de teclado e conferência em centavos.
            </p>
          </div>
        </div>

        <!-- Shortcuts Legend Bar -->
        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 bg-slate-100/80 px-3 py-1.5 rounded-lg border border-slate-200">
          <span class="font-medium text-slate-400 mr-1">Atalhos:</span>
          <button
            type="button"
            class="px-1.5 py-0.5 bg-white rounded border border-slate-300 font-mono text-slate-800 font-bold hover:bg-slate-50"
            @click="resetSale"
          >
            F2
          </button>
          <span>Limpar</span>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="px-1.5 py-0.5 bg-white rounded border border-slate-300 font-mono text-slate-800 font-bold hover:bg-slate-50"
            @click="searchInputRef?.focus()"
          >
            F3 ou /
          </button>
          <span>Buscar</span>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="px-1.5 py-0.5 bg-white rounded border border-slate-300 font-mono text-slate-800 font-bold hover:bg-slate-50"
            @click="customerSelectRef?.focus()"
          >
            F4
          </button>
          <span>Cliente</span>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="px-1.5 py-0.5 bg-white rounded border border-slate-300 font-mono text-slate-800 font-bold hover:bg-slate-50"
            @click="discountInputRef?.focus()"
          >
            F8
          </button>
          <span>Desconto</span>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="px-1.5 py-0.5 bg-slate-900 text-white rounded border border-slate-900 font-mono font-bold hover:bg-slate-800"
            @click="openCheckout"
          >
            F12
          </button>
          <span>Pagar</span>
        </div>
      </div>

      <!-- Main PDV Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Search, Customer & Items -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Customer Select & Quick Options -->
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label
                  for="customer-select"
                  class="block text-xs font-semibold text-slate-700 mb-1"
                >
                  Cliente <span class="font-normal text-slate-400">(F4)</span>
                </label>
                <select
                  id="customer-select"
                  ref="customerSelectRef"
                  v-model="form.customer_id"
                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors cursor-pointer"
                >
                  <option value="">
                    Consumidor Final (Avulso)
                  </option>
                  <option
                    v-for="c in customers"
                    :key="c.id"
                    :value="c.id"
                  >
                    {{ c.name }}
                  </option>
                </select>
              </div>

              <div>
                <label
                  for="default-payment-select"
                  class="block text-xs font-semibold text-slate-700 mb-1"
                >
                  Forma Padrão
                </label>
                <select
                  id="default-payment-select"
                  v-model="form.payment_method_id"
                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors cursor-pointer"
                >
                  <option
                    v-for="pm in paymentMethods"
                    :key="pm.id"
                    :value="pm.id"
                  >
                    {{ pm.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Fast Product Search & Inserter -->
          <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">1. Adicionar Produtos</span>
              <span class="text-[11px] text-slate-400">Pressione Enter para inserir</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
              <div class="sm:col-span-8 relative">
                <label
                  for="product-search-input"
                  class="block text-[11px] font-medium text-slate-500 mb-1"
                >
                  Buscar por Nome ou Código <span class="text-slate-400">(F3 ou /)</span>
                </label>
                <input
                  id="product-search-input"
                  ref="searchInputRef"
                  v-model="searchQuery"
                  type="text"
                  placeholder="Digite o nome do produto..."
                  autocomplete="off"
                  class="w-full rounded-lg border border-slate-200 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors"
                  @keydown="handleSearchKeydown"
                />

                <!-- Autocomplete Dropdown with keyboard highlight -->
                <div
                  v-if="searchQuery && filteredProducts.length"
                  class="absolute left-0 right-0 top-full mt-1 bg-white rounded-lg border border-slate-200 shadow-xl z-30 max-h-56 overflow-y-auto divide-y divide-slate-100"
                >
                  <button
                    v-for="(p, pIdx) in filteredProducts"
                    :key="p.id"
                    type="button"
                    :class="[
                      'w-full text-left px-3.5 py-2.5 flex items-center justify-between text-xs transition-colors cursor-pointer',
                      selectedIndexInDropdown === pIdx ? 'bg-slate-100 text-slate-900 font-semibold' : 'hover:bg-slate-50 text-slate-800'
                    ]"
                    @click="selectQuickProduct(p)"
                  >
                    <div>
                      <span class="font-medium">{{ p.name }}</span>
                      <span class="block text-[10px] text-slate-400">Estoque: {{ p.stock }} un.</span>
                    </div>
                    <div class="text-right font-bold text-slate-900 tabular-nums">
                      {{ formatMoney(p.price) }}
                    </div>
                  </button>
                </div>
              </div>

              <div class="sm:col-span-2">
                <label
                  for="item-quantity-input"
                  class="block text-[11px] font-medium text-slate-500 mb-1"
                >Qtd</label>
                <input
                  id="item-quantity-input"
                  v-model.number="selectedQuantity"
                  type="number"
                  min="1"
                  :max="currentProduct?.stock || 999"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-center font-bold tabular-nums focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                  @keydown.enter.prevent="addItem"
                />
              </div>

              <div class="sm:col-span-2">
                <AppButton
                  variant="primary"
                  size="sm"
                  class="w-full py-2 text-xs font-semibold"
                  :disabled="!selectedProductId"
                  @click="addItem"
                >
                  + Inserir
                </AppButton>
              </div>
            </div>

            <div
              v-if="currentProduct"
              class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-900"
            >
              <span>Selecionado: <strong>{{ currentProduct.name }}</strong></span>
              <span>Valor: <strong>{{ formatMoney(currentProduct.price) }}</strong> | Estoque: <strong>{{ currentProduct.stock }}</strong></span>
            </div>
          </div>

          <!-- Items Cart Table -->
          <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
              <span class="text-xs font-bold text-slate-900 uppercase">
                2. Itens no Pedido ({{ items.length }})
              </span>
              <button
                v-if="items.length"
                type="button"
                class="text-xs text-rose-600 hover:text-rose-800 font-medium cursor-pointer"
                @click="resetSale"
              >
                Limpar Pedido (F2)
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="uppercase bg-slate-50 text-slate-400 font-semibold border-b border-slate-100 text-[11px]">
                  <tr>
                    <th class="py-2.5 px-4">
                      Item
                    </th>
                    <th class="py-2.5 px-4 text-center w-28">
                      Quantidade
                    </th>
                    <th class="py-2.5 px-4 text-right">
                      Unitário
                    </th>
                    <th class="py-2.5 px-4 text-right">
                      Subtotal
                    </th>
                    <th class="py-2.5 px-4 text-center w-12">
                      Ação
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr
                    v-for="(item, idx) in items"
                    :key="item.product_id"
                    class="hover:bg-slate-50/50 transition-colors"
                  >
                    <td class="py-3 px-4 font-semibold text-slate-900">
                      {{ item.name }}
                    </td>
                    <td class="py-3 px-4">
                      <div class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          aria-label="Diminuir quantidade"
                          class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center cursor-pointer transition-colors"
                          @click="updateQuantity(idx, -1)"
                        >
                          -
                        </button>
                        <span class="w-8 text-center font-bold tabular-nums text-slate-900">{{ item.quantity }}</span>
                        <button
                          type="button"
                          aria-label="Aumentar quantidade"
                          class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center cursor-pointer transition-colors"
                          @click="updateQuantity(idx, 1)"
                        >
                          +
                        </button>
                      </div>
                    </td>
                    <td class="py-3 px-4 text-right tabular-nums">
                      {{ formatMoney(item.price) }}
                    </td>
                    <td class="py-3 px-4 text-right font-bold text-slate-900 tabular-nums">
                      {{ formatMoney(item.price * item.quantity) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                      <button
                        type="button"
                        aria-label="Remover item"
                        class="text-rose-500 hover:text-rose-700 font-bold text-base cursor-pointer p-1"
                        title="Remover item"
                        @click="removeItem(idx)"
                      >
                        &times;
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!items.length">
                    <td
                      colspan="5"
                      class="py-12 text-center text-slate-400"
                    >
                      Nenhum produto adicionado. Use o campo de busca (F3 ou /) para adicionar itens.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Column: Financial Summary & Quick Checkout -->
        <div class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 sticky top-6 shadow-sm">
            <span class="text-xs font-bold text-slate-900 uppercase block border-b border-slate-100 pb-2">
              Resumo Financeiro
            </span>

            <div class="space-y-3 text-xs">
              <div class="flex justify-between text-slate-600 font-medium">
                <span>Subtotal Bruto</span>
                <span class="font-bold text-slate-900 tabular-nums">{{ formatMoney(subtotal) }}</span>
              </div>

              <div>
                <label
                  for="discount-input"
                  class="block text-[11px] font-medium text-slate-600 mb-1"
                >
                  Desconto (R$) <span class="text-slate-400">(F8)</span>
                </label>
                <input
                  id="discount-input"
                  ref="discountInputRef"
                  v-model.number="form.discount"
                  type="number"
                  step="0.01"
                  min="0"
                  :max="subtotal"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 font-bold tabular-nums focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                  placeholder="0,00"
                />
              </div>

              <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-700">Total Líquido</span>
                <span class="text-3xl font-extrabold text-slate-900 tabular-nums">{{ formatMoney(totalAmount) }}</span>
              </div>
            </div>

            <!-- Installments Options -->
            <div class="pt-3 border-t border-slate-100 space-y-2">
              <label
                for="installments-select"
                class="block text-[11px] font-medium text-slate-600"
              >Parcelamento</label>
              <select
                id="installments-select"
                v-model.number="form.installments"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 font-semibold focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 cursor-pointer"
              >
                <option
                  v-for="n in 12"
                  :key="n"
                  :value="n"
                >
                  {{ n }}x {{ n === 1 ? 'à vista' : 'mensais' }}
                </option>
              </select>

              <div
                v-if="form.installments > 1"
                class="rounded-lg bg-slate-50 p-2.5 space-y-1 text-[11px] border border-slate-100 max-h-36 overflow-y-auto"
              >
                <div class="font-semibold text-slate-700 mb-1">
                  Parcelas Previstas:
                </div>
                <div
                  v-for="inst in calculatedInstallments"
                  :key="inst.number"
                  class="flex justify-between text-slate-600 tabular-nums"
                >
                  <span>{{ inst.number }}ª ({{ inst.date }})</span>
                  <span class="font-bold text-slate-900">{{ formatMoney(inst.amount) }}</span>
                </div>
              </div>
            </div>

            <div>
              <label
                for="order-notes"
                class="block text-[11px] font-medium text-slate-600 mb-1"
              >Observações Internas</label>
              <textarea
                id="order-notes"
                v-model="form.notes"
                rows="2"
                placeholder="Anotações de balcão..."
                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 placeholder:text-slate-400"
              />
            </div>

            <!-- Primary Checkout Button -->
            <AppButton
              variant="primary"
              size="lg"
              class="w-full py-3.5 text-sm font-bold shadow-md flex items-center justify-center gap-2"
              :disabled="!items.length || form.processing"
              @click="openCheckout"
            >
              <span>Ir para Pagamento (F12)</span>
              <span class="text-xs bg-slate-800 px-1.5 py-0.5 rounded font-mono font-normal">F12</span>
            </AppButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Multi-Payment Checkout Modal (Acessível / Focus Trap / Validação em Centavos) -->
    <div
      v-if="isCheckoutOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="checkout-modal-title"
    >
      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3
              id="checkout-modal-title"
              class="text-lg font-bold text-slate-900"
            >
              Finalizar Venda - Checkout
            </h3>
            <p class="text-xs text-slate-500">
              Adicione uma ou mais formas de pagamento para cobrir o total líquido.
            </p>
          </div>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-2xl leading-none p-1 cursor-pointer"
            aria-label="Fechar checkout"
            @click="isCheckoutOpen = false"
          >
            &times;
          </button>
        </div>

        <!-- Total vs Paid Balance Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
          <div>
            <span class="text-slate-500 font-medium">Total da Venda:</span>
            <p class="text-lg font-extrabold text-slate-900 tabular-nums">
              {{ formatMoney(totalAmount) }}
            </p>
          </div>
          <div>
            <span class="text-slate-500 font-medium">Total Aplicado:</span>
            <p class="text-lg font-extrabold text-slate-900 tabular-nums">
              {{ formatMoney(paymentsSum) }}
            </p>
          </div>
          <div>
            <span class="text-slate-500 font-medium">Situação:</span>
            <p
              v-if="remainingPaymentCents === 0"
              class="text-sm font-bold text-emerald-600 flex items-center gap-1 mt-1"
            >
              ✓ Total 100% Coberto
            </p>
            <p
              v-else-if="remainingPaymentCents > 0"
              class="text-sm font-bold text-amber-600 tabular-nums mt-1"
            >
              Faltam {{ formatMoney(remainingPaymentAmount) }}
            </p>
            <p
              v-else
              class="text-sm font-bold text-rose-600 tabular-nums mt-1"
            >
              Excede {{ formatMoney(Math.abs(remainingPaymentAmount)) }}
            </p>
          </div>
        </div>

        <!-- Payment Rows List -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-900 uppercase">Formas de Pagamento</span>
            <button
              type="button"
              class="text-xs font-semibold text-slate-900 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
              @click="addPaymentRow"
            >
              + Adicionar Método
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(p, pIdx) in payments"
              :key="pIdx"
              class="p-4 rounded-xl border border-slate-200 bg-white space-y-3 relative shadow-xs"
            >
              <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <!-- Payment Method Select -->
                <div class="sm:col-span-5">
                  <label
                    :for="`pm-select-${pIdx}`"
                    class="block text-[11px] font-semibold text-slate-600 mb-1"
                  >
                    Método de Pagamento
                  </label>
                  <select
                    :id="`pm-select-${pIdx}`"
                    v-model.number="p.payment_method_id"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 cursor-pointer"
                    @change="onMethodChange(p)"
                  >
                    <option
                      v-for="pm in paymentMethods"
                      :key="pm.id"
                      :value="pm.id"
                    >
                      {{ pm.name }}
                    </option>
                  </select>
                </div>

                <!-- Applied Amount -->
                <div class="sm:col-span-4">
                  <div class="flex justify-between items-center mb-1">
                    <label
                      :for="`pm-amount-${pIdx}`"
                      class="block text-[11px] font-semibold text-slate-600"
                    >
                      Valor Aplicado (R$)
                    </label>
                    <button
                      v-if="remainingPaymentCents !== 0"
                      type="button"
                      class="text-[10px] text-slate-600 hover:text-slate-900 underline font-medium cursor-pointer"
                      @click="fillRemaining(p)"
                    >
                      Cobrir Restante
                    </button>
                  </div>
                  <input
                    :id="`pm-amount-${pIdx}`"
                    :ref="pIdx === 0 ? 'firstPaymentInputRef' : undefined"
                    v-model.number="p.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-900 tabular-nums focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    @input="calculateChange(p)"
                  />
                </div>

                <!-- Remove Button -->
                <div class="sm:col-span-3 flex items-center justify-end">
                  <button
                    v-if="payments.length > 1"
                    type="button"
                    class="px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 text-xs font-semibold transition-colors cursor-pointer"
                    aria-label="Remover forma de pagamento"
                    @click="removePaymentRow(pIdx)"
                  >
                    Remover
                  </button>
                </div>
              </div>

              <!-- Special Cash Fields: Received & Change Calculation -->
              <div
                v-if="isCashMethod(p.payment_method_id)"
                class="p-3 bg-emerald-50/60 rounded-lg border border-emerald-200/80 grid grid-cols-1 sm:grid-cols-2 gap-3 items-center text-xs"
              >
                <div>
                  <label
                    :for="`pm-received-${pIdx}`"
                    class="block text-[11px] font-semibold text-emerald-900 mb-1"
                  >
                    Dinheiro Recebido do Cliente (R$)
                  </label>
                  <input
                    :id="`pm-received-${pIdx}`"
                    v-model.number="p.received_amount"
                    type="number"
                    step="0.01"
                    :min="p.amount"
                    placeholder="Valor entregue..."
                    class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 tabular-nums focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                    @input="calculateChange(p)"
                  />
                </div>

                <div class="flex flex-col justify-center sm:text-right">
                  <span class="text-[11px] font-medium text-emerald-800">Troco a Devolver:</span>
                  <span class="text-xl font-extrabold text-emerald-700 tabular-nums">
                    {{ formatMoney(p.change_given || 0) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Backend Errors on Form -->
        <div
          v-if="Object.keys(form.errors).length"
          class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 space-y-1"
        >
          <div
            v-for="(err, k) in form.errors"
            :key="k"
          >
            • {{ err }}
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
            @click="isCheckoutOpen = false"
          >
            Voltar (Esc)
          </button>

          <AppButton
            variant="primary"
            size="md"
            class="px-6 py-2.5 font-bold shadow-md"
            :loading="form.processing"
            :disabled="!isPaymentValid || form.processing"
            @click="submitCheckout"
          >
            Confirmar e Emitir Venda (Enter)
          </AppButton>
        </div>
      </div>
    </div>
  </AppLayout>
</template>