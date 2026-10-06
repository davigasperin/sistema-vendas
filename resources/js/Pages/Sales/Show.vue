<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import type { Sale } from '@/types';

const props = defineProps<{
    sale: Sale;
}>();

const showCancelDialog = ref(false);
const isCancelling = ref(false);
const showThermalModal = ref(false);
const thermalWidth = ref<'80mm' | '58mm'>('80mm');

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function formatDateTime(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('pt-BR');
}

function handleCancelSale() {
    isCancelling.value = true;
    router.delete(`/sales/${props.sale.id}`, {
        onFinish: () => {
            isCancelling.value = false;
            showCancelDialog.value = false;
        },
    });
}

function openThermalPrint(width: '80mm' | '58mm' = '80mm') {
    window.open(`/sales/${props.sale.id}/receipt?width=${width}&autoprint=1`, '_blank', 'width=450,height=600');
}
</script>

<template>
  <AppLayout :title="`Venda #${sale.id}`">
    <Head :title="`Venda #${sale.id}`" />

    <div class="max-w-5xl mx-auto space-y-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-semibold text-slate-900">
            Venda #{{ sale.id }}
          </h1>
          <Badge :variant="sale.status === 'completed' ? 'success' : 'danger'">
            {{ sale.status === 'completed' ? 'Concluída' : 'Cancelada' }}
          </Badge>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            type="button"
            class="px-3.5 py-2 rounded-lg bg-slate-900 text-xs font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-1.5 shadow-sm"
            @click="showThermalModal = true"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
              />
            </svg>
            Imprimir Cupom
          </button>

          <a
            :href="`/sales/${sale.id}/pdf`"
            target="_blank"
            rel="noopener noreferrer"
            class="px-3.5 py-2 rounded-lg bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors flex items-center gap-1.5"
          >
            <svg
              class="w-4 h-4 text-slate-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            PDF A4
          </a>

          <button
            v-if="sale.status === 'completed'"
            type="button"
            class="px-3.5 py-2 rounded-lg bg-rose-50 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors"
            @click="showCancelDialog = true"
          >
            Cancelar Venda
          </button>

          <Link
            href="/sales"
            class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors ml-1"
          >
            &larr; Voltar
          </Link>
        </div>
      </div>

      <!-- Details Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <span class="text-xs font-medium text-slate-400 uppercase">Cliente</span>
          <p class="text-base font-semibold text-slate-900 mt-1">
            {{ sale.customer?.name || 'Cliente Avulso' }}
          </p>
          <p class="text-xs text-slate-500">
            {{ sale.customer?.email || sale.customer?.phone || '-' }}
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <span class="text-xs font-medium text-slate-400 uppercase">Vendedor & Caixa</span>
          <p class="text-base font-semibold text-slate-900 mt-1">
            {{ sale.user?.name || '-' }}
          </p>
          <p class="text-xs text-slate-500">
            {{ sale.cash_shift_id ? `Caixa #${sale.cash_shift_id}` : 'Sem caixa (Retaguarda)' }}
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <span class="text-xs font-medium text-slate-400 uppercase">Condição</span>
          <p class="text-base font-semibold text-slate-900 mt-1">
            {{ sale.payment_method?.name || '-' }}
          </p>
          <p class="text-xs text-slate-500">
            {{ sale.installments }}x parcela(s)
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <span class="text-xs font-medium text-slate-400 uppercase">Total Líquido</span>
          <p class="text-2xl font-semibold text-slate-900 mt-1">
            {{ formatMoney(sale.total_amount) }}
          </p>
          <p
            v-if="sale.discount > 0"
            class="text-xs text-emerald-600"
          >
            Desconto: {{ formatMoney(sale.discount) }}
          </p>
        </div>
      </div>

      <!-- Payments Section -->
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
          Pagamentos Registrados
        </h3>
        <div
          v-if="sale.payments && sale.payments.length"
          class="overflow-x-auto"
        >
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50 text-slate-500">
              <tr>
                <th class="py-2.5 px-4">
                  Forma de Pagamento
                </th>
                <th class="py-2.5 px-4 text-right">
                  Valor Aplicado
                </th>
                <th class="py-2.5 px-4 text-right">
                  Troco Fornecido
                </th>
                <th class="py-2.5 px-4">
                  Observações
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="payment in sale.payments"
                :key="payment.id"
              >
                <td class="py-3 px-4 font-semibold text-slate-900">
                  {{ payment.payment_method?.name || '-' }}
                </td>
                <td class="py-3 px-4 text-right font-semibold text-slate-900 tabular-nums">
                  {{ formatMoney(payment.amount) }}
                </td>
                <td class="py-3 px-4 text-right tabular-nums">
                  <span
                    v-if="payment.change_given > 0"
                    class="text-emerald-600 font-semibold"
                  >
                    {{ formatMoney(payment.change_given) }}
                  </span>
                  <span
                    v-else
                    class="text-slate-400"
                  >-</span>
                </td>
                <td class="py-3 px-4 text-xs text-slate-500">
                  {{ payment.notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-else
          class="text-sm text-slate-500"
        >
          Pagamento único via <strong>{{ sale.payment_method?.name }}</strong> no valor de <strong>{{ formatMoney(sale.total_amount) }}</strong>.
        </div>
      </div>

      <!-- Items -->
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
          Itens do Pedido
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50 text-slate-500">
              <tr>
                <th class="py-3 px-4">
                  Produto
                </th>
                <th class="py-3 px-4 text-center">
                  Quantidade
                </th>
                <th class="py-3 px-4 text-right">
                  Valor Unitário
                </th>
                <th class="py-3 px-4 text-right">
                  Subtotal
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in sale.items || []"
                :key="item.id"
              >
                <td class="py-3 px-4 font-semibold text-slate-900">
                  {{ item.product?.name || 'Item' }}
                </td>
                <td class="py-3 px-4 text-center">
                  {{ item.quantity }}
                </td>
                <td class="py-3 px-4 text-right">
                  {{ formatMoney(item.unit_price) }}
                </td>
                <td class="py-3 px-4 text-right font-semibold text-slate-900">
                  {{ formatMoney(item.subtotal) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Installments -->
      <div
        v-if="sale.sale_installments?.length"
        class="rounded-xl border border-slate-200 bg-white p-6"
      >
        <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
          Detalhamento de Parcelas
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="inst in sale.sale_installments"
            :key="inst.id"
            class="p-3.5 rounded-lg border border-slate-100 bg-slate-50/70 flex items-center justify-between"
          >
            <div>
              <span class="text-xs font-semibold text-slate-800">{{ inst.installment_number }}ª Parcela</span>
              <p class="text-xs text-slate-400">
                Vencimento: {{ formatDate(inst.due_date) }}
              </p>
            </div>
            <div class="text-right">
              <span class="text-sm font-semibold text-slate-900 block">{{ formatMoney(inst.amount) }}</span>
              <Badge
                :variant="inst.is_paid ? 'success' : 'warning'"
                size="sm"
              >
                {{ inst.is_paid ? 'Paga' : 'Pendente' }}
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Imprimir Cupom Térmico -->
    <div
      v-if="showThermalModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="thermal-modal-title"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3
            id="thermal-modal-title"
            class="text-base font-semibold text-slate-900"
          >
            Imprimir Cupom Térmico
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-lg leading-none"
            aria-label="Fechar modal"
            @click="showThermalModal = false"
          >
            &times;
          </button>
        </div>

        <div class="space-y-4 text-xs text-slate-600">
          <p>Selecione a largura da bobina da sua impressora térmica:</p>

          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              :class="[
                'p-4 rounded-xl border text-center transition-all cursor-pointer',
                thermalWidth === '80mm'
                  ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10 font-semibold text-slate-900'
                  : 'border-slate-200 hover:border-slate-300 text-slate-700'
              ]"
              @click="thermalWidth = '80mm'"
            >
              <span class="block text-sm">80mm (Padrão)</span>
              <span class="text-[11px] text-slate-500">Impressoras de balcão comuns</span>
            </button>

            <button
              type="button"
              :class="[
                'p-4 rounded-xl border text-center transition-all cursor-pointer',
                thermalWidth === '58mm'
                  ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10 font-semibold text-slate-900'
                  : 'border-slate-200 hover:border-slate-300 text-slate-700'
              ]"
              @click="thermalWidth = '58mm'"
            >
              <span class="block text-sm">58mm (Compacto)</span>
              <span class="text-[11px] text-slate-500">Mini impressoras e portáteis</span>
            </button>
          </div>

          <!-- Mini Preview -->
          <div class="p-3 bg-slate-100 rounded-lg border border-slate-200 font-mono text-[10px] space-y-1">
            <div class="text-center font-bold">
              SISTEMA COMERCIAL - VENDA #{{ sale.id }}
            </div>
            <div class="flex justify-between">
              <span>DATA: {{ formatDateTime(sale.created_at) }}</span>
              <span>{{ sale.user?.name }}</span>
            </div>
            <div class="border-t border-dashed border-slate-400 pt-1 flex justify-between font-bold">
              <span>TOTAL:</span>
              <span>{{ formatMoney(sale.total_amount) }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200"
            @click="showThermalModal = false"
          >
            Fechar
          </button>
          <button
            type="button"
            class="px-5 py-2 rounded-lg bg-slate-900 text-xs font-semibold text-white hover:bg-slate-800 shadow-sm"
            @click="openThermalPrint(thermalWidth)"
          >
            Abrir Impressão ({{ thermalWidth }})
          </button>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="showCancelDialog"
      title="Cancelar Venda"
      message="Deseja realmente cancelar esta venda? O estoque dos itens será estornado automaticamente."
      confirm-label="Cancelar Venda"
      cancel-label="Voltar"
      variant="danger"
      :loading="isCancelling"
      @confirm="handleCancelSale"
      @cancel="showCancelDialog = false"
    />
  </AppLayout>
</template>
