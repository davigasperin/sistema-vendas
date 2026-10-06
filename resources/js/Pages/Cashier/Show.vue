<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import type { CashShift } from '@/types';

const props = defineProps<{
    shift: CashShift;
    canAudit: boolean;
}>();

function formatMoney(value?: number | null): string {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(value?: string | null): string {
    if (!value) return '-';
    return new Date(value).toLocaleString('pt-BR');
}

function differenceClass(): string {
    if (props.shift.difference === null || props.shift.difference === undefined || props.shift.difference === 0) {
        return 'text-slate-900';
    }

    return props.shift.difference > 0 ? 'text-emerald-600' : 'text-rose-600';
}
</script>

<template>
  <AppLayout title="Detalhes do Caixa">
    <Head :title="`Caixa #${shift.id}`" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-semibold text-slate-900">
            Caixa #{{ shift.id }}
          </h1>
          <Badge
            :variant="shift.status === 'open' ? 'success' : 'neutral'"
            size="sm"
          >
            {{ shift.status === 'open' ? 'Aberto' : 'Fechado' }}
          </Badge>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          Operador: {{ shift.user?.name || '-' }}
        </p>
      </div>
      <Link href="/cashier">
        <AppButton
          variant="secondary"
          size="md"
        >
          Voltar ao Caixa
        </AppButton>
      </Link>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Abertura
        </p>
        <p class="mt-1 text-sm font-semibold text-slate-900">
          {{ formatDate(shift.opened_at) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Fechamento
        </p>
        <p class="mt-1 text-sm font-semibold text-slate-900">
          {{ formatDate(shift.closed_at) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Fundo inicial
        </p>
        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
          {{ formatMoney(shift.initial_amount) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Valor contado
        </p>
        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
          {{ formatMoney(shift.final_amount_reported) }}
        </p>
      </div>
      <div
        v-if="canAudit"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Valor esperado
        </p>
        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
          {{ formatMoney(shift.final_amount_expected) }}
        </p>
      </div>
      <div
        v-if="shift.status === 'closed'"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-[11px] font-medium uppercase text-slate-500">
          Diferença
        </p>
        <p
          class="mt-1 text-lg font-semibold tabular-nums"
          :class="differenceClass()"
        >
          {{ formatMoney(shift.difference) }}
        </p>
      </div>
    </div>

    <div
      v-if="shift.notes"
      class="rounded-xl border border-slate-200 bg-white p-4 mb-6"
    >
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">
        Observações
      </h2>
      <p class="mt-2 whitespace-pre-line text-sm text-slate-600">
        {{ shift.notes }}
      </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">
            Movimentações
          </h2>
        </div>
        <div
          v-if="shift.movements?.length"
          class="overflow-x-auto"
        >
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase">
              <tr>
                <th class="px-4 py-3">
                  Data
                </th>
                <th class="px-4 py-3">
                  Tipo
                </th>
                <th class="px-4 py-3">
                  Justificativa
                </th>
                <th class="px-4 py-3 text-right">
                  Valor
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="movement in shift.movements"
                :key="movement.id"
              >
                <td class="px-4 py-3 tabular-nums">
                  {{ formatDate(movement.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <Badge
                    :variant="movement.type === 'bleed' ? 'warning' : 'success'"
                    size="sm"
                  >
                    {{ movement.type === 'receipt' ? 'Recebimento de parcela' : movement.type === 'supply' ? 'Suprimento' : 'Sangria' }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  {{ movement.reason }}
                </td>
                <td class="px-4 py-3 text-right font-semibold tabular-nums">
                  {{ formatMoney(movement.amount) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState
          v-else
          title="Sem movimentações"
          description="Nenhuma sangria ou suprimento registrado neste turno."
        />
      </section>

      <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">
            Vendas vinculadas
          </h2>
        </div>
        <div
          v-if="shift.sales?.length"
          class="overflow-x-auto"
        >
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase">
              <tr>
                <th class="px-4 py-3">
                  Venda
                </th>
                <th class="px-4 py-3">
                  Data
                </th>
                <th class="px-4 py-3">
                  Cliente
                </th>
                <th class="px-4 py-3 text-right">
                  Total
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="sale in shift.sales"
                :key="sale.id"
              >
                <td class="px-4 py-3">
                  <Link
                    :href="`/sales/${sale.id}`"
                    class="font-medium text-slate-900 hover:underline"
                  >
                    #{{ sale.id }}
                  </Link>
                </td>
                <td class="px-4 py-3 tabular-nums">
                  {{ formatDate(sale.created_at) }}
                </td>
                <td class="px-4 py-3">
                  {{ sale.customer || 'Consumidor final' }}
                </td>
                <td class="px-4 py-3 text-right font-semibold tabular-nums">
                  {{ formatMoney(sale.total_amount) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState
          v-else
          title="Sem vendas vinculadas"
          description="Nenhuma venda foi registrada neste turno."
        />
      </section>
    </div>
  </AppLayout>
</template>
