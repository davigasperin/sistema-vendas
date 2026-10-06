<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Modal from '@/Components/UI/Modal.vue';
import MoneyInput from '@/Components/UI/MoneyInput.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import SelectInput from '@/Components/UI/SelectInput.vue';
import type { CashShift, PaginationLink } from '@/types';

defineProps<{
    currentShift: CashShift | null;
    shifts: {
        data: CashShift[];
        meta?: {
            links?: PaginationLink[];
        };
    };
    canAudit: boolean;
    canOperate: boolean;
}>();

const openModalOpen = ref(false);
const movementModalOpen = ref(false);
const closeModalOpen = ref(false);

const openForm = useForm({
    initial_amount: 0,
    notes: '',
});

const movementForm = useForm({
    type: 'supply' as 'supply' | 'bleed',
    amount: 0,
    reason: '',
});

const closeForm = useForm({
    reported_amount: 0,
    notes: '',
});

function submitOpen() {
    openForm.post('/cashier/open', {
        preserveScroll: true,
        onSuccess: () => {
            openModalOpen.value = false;
            openForm.reset();
        },
    });
}

function submitMovement() {
    movementForm.post('/cashier/movement', {
        preserveScroll: true,
        onSuccess: () => {
            movementModalOpen.value = false;
            movementForm.reset();
        },
    });
}

function submitClose() {
    closeForm.post('/cashier/close', {
        preserveScroll: true,
        onSuccess: () => {
            closeModalOpen.value = false;
            closeForm.reset();
        },
    });
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
  <AppLayout title="Frente de Caixa">
    <Head title="Controle de Turnos e Caixa" />

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">
          Controle de Caixa
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          Abertura de turnos, movimentações (sangria/suprimento) e fechamento cego.
        </p>
      </div>
      <div class="flex items-center gap-2.5">
        <AppButton
          v-if="canOperate && !currentShift"
          variant="primary"
          size="md"
          @click="openModalOpen = true"
        >
          <svg
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Abrir Meu Caixa
        </AppButton>
      </div>
    </div>


    <div
      v-if="currentShift"
      class="rounded-xl border border-emerald-200 bg-white p-6 mb-6 shadow-xs"
    >
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse" />
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-semibold text-slate-900">
                Meu Turno em Aberto #{{ currentShift.id }}
              </h2>
              <Badge
                variant="success"
                size="sm"
              >
                Em Operação
              </Badge>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Aberto em {{ formatDate(currentShift.opened_at) }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <AppButton
            v-if="canOperate"
            variant="secondary"
            size="sm"
            @click="movementModalOpen = true"
          >
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
              />
            </svg>
            Sangria / Suprimento
          </AppButton>
          <AppButton
            v-if="canOperate"
            variant="danger"
            size="sm"
            @click="closeModalOpen = true"
          >
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Fechar Caixa
          </AppButton>
          <Link :href="`/cashier/${currentShift.id}`">
            <AppButton
              variant="secondary"
              size="sm"
            >
              Detalhes
            </AppButton>
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
          <span class="text-[11px] font-medium text-slate-500 uppercase">Fundo Inicial</span>
          <p class="text-lg font-semibold text-slate-900 mt-1 tabular-nums">
            {{ formatMoney(currentShift.initial_amount) }}
          </p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
          <span class="text-[11px] font-medium text-slate-500 uppercase">Movimentações no Turno</span>
          <p class="text-lg font-semibold text-slate-900 mt-1 tabular-nums">
            {{ currentShift.movements?.length || 0 }} registro(s)
          </p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
          <span class="text-[11px] font-medium text-slate-500 uppercase">Conferência Cega</span>
          <p class="text-xs font-normal text-slate-500 mt-1">
            Saldo esperado ocultado até o fechamento para blind audit.
          </p>
        </div>
      </div>
    </div>


    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
        <h3 class="text-xs font-semibold text-slate-800 uppercase tracking-wider">
          {{ canAudit ? 'Todos os Turnos de Caixa (Auditoria)' : 'Meus Turnos Anteriores' }}
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="uppercase bg-slate-50/80 text-slate-500 font-medium border-b border-slate-200">
            <tr>
              <th class="py-3 px-4">
                ID
              </th>
              <th class="py-3 px-4">
                Operador
              </th>
              <th class="py-3 px-4">
                Abertura
              </th>
              <th class="py-3 px-4">
                Fechamento
              </th>
              <th class="py-3 px-4 text-right">
                Fundo Inicial
              </th>
              <th class="py-3 px-4 text-right">
                Contado
              </th>
              <th
                v-if="canAudit"
                class="py-3 px-4 text-right"
              >
                Esperado
              </th>
              <th class="py-3 px-4 text-right">
                Diferença
              </th>
              <th class="py-3 px-4 text-center">
                Status
              </th>
              <th class="py-3 px-4 text-right">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="shift in shifts.data"
              :key="shift.id"
              class="hover:bg-slate-50/60 transition-colors"
            >
              <td class="py-3 px-4 font-mono text-slate-500">
                #{{ shift.id }}
              </td>
              <td class="py-3 px-4 font-medium text-slate-900">
                {{ shift.user?.name || '-' }}
              </td>
              <td class="py-3 px-4 tabular-nums">
                {{ formatDate(shift.opened_at) }}
              </td>
              <td class="py-3 px-4 tabular-nums text-slate-500">
                {{ formatDate(shift.closed_at) }}
              </td>
              <td class="py-3 px-4 text-right tabular-nums">
                {{ formatMoney(shift.initial_amount) }}
              </td>
              <td class="py-3 px-4 text-right tabular-nums">
                {{ shift.final_amount_reported !== null && shift.final_amount_reported !== undefined ? formatMoney(shift.final_amount_reported) : '-' }}
              </td>
              <td
                v-if="canAudit"
                class="py-3 px-4 text-right tabular-nums"
              >
                {{ shift.final_amount_expected !== null && shift.final_amount_expected !== undefined ? formatMoney(shift.final_amount_expected) : '-' }}
              </td>
              <td
                class="py-3 px-4 text-right tabular-nums font-semibold"
                :class="[
                  shift.difference === null || shift.difference === undefined
                    ? 'text-slate-400'
                    : shift.difference === 0
                      ? 'text-slate-700'
                      : shift.difference > 0
                        ? 'text-emerald-600'
                        : 'text-rose-600'
                ]"
              >
                {{ shift.difference !== null && shift.difference !== undefined ? formatMoney(shift.difference) : '-' }}
              </td>
              <td class="py-3 px-4 text-center">
                <Badge
                  :variant="shift.status === 'open' ? 'success' : 'neutral'"
                  size="sm"
                >
                  {{ shift.status === 'open' ? 'Aberto' : 'Fechado' }}
                </Badge>
              </td>
              <td class="py-3 px-4 text-right">
                <Link
                  :href="`/cashier/${shift.id}`"
                  class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                >
                  Ver Detalhes
                </Link>
              </td>
            </tr>
            <tr v-if="!shifts.data.length">
              <td :colspan="canAudit ? 10 : 9">
                <EmptyState
                  title="Nenhum turno registrado"
                  description="Turnos de caixa abertos e fechados aparecerão listados aqui."
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-100 px-4">
        <Pagination :links="shifts.meta?.links || []" />
      </div>
    </div>


    <Modal
      :show="openModalOpen"
      title="Abertura de Caixa"
      @close="openModalOpen = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="submitOpen"
      >
        <MoneyInput
          v-model="openForm.initial_amount"
          label="Fundo de Troco Inicial"
          :error="openForm.errors.initial_amount"
          required
        />
        <TextInput
          v-model="openForm.notes"
          label="Observações de Abertura"
          placeholder="Opcional..."
          :error="openForm.errors.notes"
        />
        <div class="flex justify-end gap-2 pt-2">
          <AppButton
            variant="secondary"
            type="button"
            @click="openModalOpen = false"
          >
            Cancelar
          </AppButton>
          <AppButton
            variant="primary"
            type="submit"
            :loading="openForm.processing"
          >
            Confirmar Abertura
          </AppButton>
        </div>
      </form>
    </Modal>


    <Modal
      :show="movementModalOpen"
      title="Registrar Movimentação de Caixa"
      @close="movementModalOpen = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="submitMovement"
      >
        <SelectInput
          v-model="movementForm.type"
          label="Tipo de Movimentação"
          :options="[
            { value: 'supply', label: 'Suprimento (Entrada de Troco)' },
            { value: 'bleed', label: 'Sangria (Retirada de Segurança)' }
          ]"
          :error="movementForm.errors.type"
          required
        />
        <MoneyInput
          v-model="movementForm.amount"
          label="Valor da Movimentação"
          :error="movementForm.errors.amount"
          required
        />
        <TextInput
          v-model="movementForm.reason"
          label="Justificativa"
          placeholder="Ex: Troco complementar em notas de 5"
          :error="movementForm.errors.reason"
          required
        />
        <div class="flex justify-end gap-2 pt-2">
          <AppButton
            variant="secondary"
            type="button"
            @click="movementModalOpen = false"
          >
            Cancelar
          </AppButton>
          <AppButton
            variant="primary"
            type="submit"
            :loading="movementForm.processing"
          >
            Salvar Movimentação
          </AppButton>
        </div>
      </form>
    </Modal>


    <Modal
      :show="closeModalOpen"
      title="Fechamento de Caixa (Conferência Cega)"
      @close="closeModalOpen = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="submitClose"
      >
        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
          Contagem física real da gaveta. Conte o dinheiro físico disponível e informe o total exato.
        </div>
        <MoneyInput
          v-model="closeForm.reported_amount"
          label="Total em Dinheiro Contado na Gaveta"
          :error="closeForm.errors.reported_amount"
          required
        />
        <TextInput
          v-model="closeForm.notes"
          label="Observações do Fechamento"
          placeholder="Opcional..."
          :error="closeForm.errors.notes"
        />
        <div class="flex justify-end gap-2 pt-2">
          <AppButton
            variant="secondary"
            type="button"
            @click="closeModalOpen = false"
          >
            Cancelar
          </AppButton>
          <AppButton
            variant="danger"
            type="submit"
            :loading="closeForm.processing"
          >
            Fechar Turno
          </AppButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>
