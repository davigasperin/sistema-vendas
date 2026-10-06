<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import type {
    MonthClose,
    MonthSnapshot,
    PaginatedData,
    PageProps,
    PaymentMethod,
    ReceivablesInstallment,
} from '@/types';

const props = defineProps<{
    installments: PaginatedData<ReceivablesInstallment>;
    paymentMethods: PaymentMethod[];
    customers: { id: number; name: string }[];
    summary: { a_receber: number; vencido: number; recebido_mes: number };
    monthSnapshot: MonthSnapshot | null;
    currentMonthClose: MonthClose | null;
    selectedYear: number;
    selectedMonth: number;
    filters?: {
        status?: string;
        customer_id?: string;
        date_from?: string;
        date_to?: string;
    };
    abilities: {
        canPay: boolean;
        canCloseMonth: boolean;
    };
}>();

const page = usePage<PageProps>();

const activeTab = ref<'receivables' | 'close'>('receivables');
const status = ref(props.filters?.status || '');
const customerId = ref(props.filters?.customer_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const selectedPeriod = ref(`${props.selectedYear}-${String(props.selectedMonth).padStart(2, '0')}`);

const selected = ref<number[]>([]);
const payTarget = ref<ReceivablesInstallment | null>(null);
const showPayModal = ref(false);
const showBatchModal = ref(false);
const showCloseConfirm = ref(false);
const batchLoading = ref(false);
const closeLoading = ref(false);

function localIsoDate(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

const today = localIsoDate();

const payForm = useForm({
    payment_method_id: '',
    paid_date: today,
});

const closeForm = useForm({});

const monthLabel = computed(() => {
    const date = new Date(props.selectedYear, props.selectedMonth - 1);
    return date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const batchError = computed(() => page.props.errors?.['installment'] ?? page.props.errors?.['payment_method_id'] ?? null);

function applyFilters() {
    router.get(
        '/receivables',
        {
            status: status.value,
            customer_id: customerId.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    status.value = '';
    customerId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/receivables');
}

function toggleSelect(id: number) {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((v) => v !== id)
        : [...selected.value, id];
}

function toggleSelectAll() {
    const unpaidIds = payableRows.value.map((row) => row.id);
    const allSelected = unpaidIds.length > 0 && unpaidIds.every((id) => selected.value.includes(id));
    selected.value = allSelected ? [] : unpaidIds;
}

const payableRows = computed(() =>
    props.installments.data.filter((row) => !row.is_paid)
);

const allSelected = computed(() =>
    payableRows.value.length > 0 && payableRows.value.every((row) => selected.value.includes(row.id))
);

function openPayModal(row: ReceivablesInstallment) {
    payTarget.value = row;
    payForm.reset();
    payForm.payment_method_id = '';
    payForm.paid_date = today;
    showPayModal.value = true;
}

function openBatchModal() {
    payForm.reset();
    payForm.payment_method_id = '';
    payForm.paid_date = today;
    showBatchModal.value = true;
}

function submitPay() {
    if (!payTarget.value) return;

    payForm.transform((data) => ({
        payment_method_id: Number(data.payment_method_id),
        paid_date: data.paid_date,
    })).post(`/installments/${payTarget.value.id}/pay`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
            payTarget.value = null;
        },
    });
}

function submitBatch() {
    if (!payForm.payment_method_id) {
        payForm.setError('payment_method_id', 'Informe o método de pagamento.');
        return;
    }

    batchLoading.value = true;
    router.post('/installments/pay-batch', {
        installment_ids: selected.value,
        payment_method_id: Number(payForm.payment_method_id),
        paid_date: payForm.paid_date,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showBatchModal.value = false;
            selected.value = [];
        },
        onFinish: () => {
            batchLoading.value = false;
        },
    });
}

function submitCloseMonth() {
    const [year, month] = selectedPeriod.value.split('-');
    closeForm.post(`/month-close/${year}/${Number(month)}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showCloseConfirm.value = false;
        },
        onFinish: () => {
            closeLoading.value = false;
        },
    });
    closeLoading.value = true;
}

function statusBadge(row: ReceivablesInstallment) {
    if (row.status === 'paid') return { variant: 'success' as const, label: 'Paga' };
    if (row.status === 'overdue') return { variant: 'danger' as const, label: 'Vencida' };
    return { variant: 'warning' as const, label: 'Pendente' };
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr.slice(0, 10) + 'T00:00:00').toLocaleDateString('pt-BR');
}

const monthCloseError = computed(() => page.props.errors?.['month_close'] ?? null);
</script>

<template>
  <AppLayout title="Contas a Receber">
    <Head title="Contas a Receber" />

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">
          Contas a Receber
        </h1>
        <p class="text-sm text-slate-500 mt-0.5">
          Baixa de parcelas e fechamento mensal.
        </p>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Período</label>
        <input
          v-model="selectedPeriod"
          type="month"
          class="rounded-lg border-slate-200 px-3 py-2 text-sm"
          @change="
            router.get(
              '/receivables',
              { year: selectedPeriod.split('-')[0], month: Number(selectedPeriod.split('-')[1]) },
              { preserveState: true, replace: true }
            )
          "
        />
      </div>
    </div>

    <div class="flex gap-1 mb-6 border-b border-slate-200">
      <button
        type="button"
        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="
          activeTab === 'receivables'
            ? 'border-slate-900 text-slate-900'
            : 'border-transparent text-slate-500 hover:text-slate-700'
        "
        @click="activeTab = 'receivables'"
      >
        A receber
      </button>
      <button
        type="button"
        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="
          activeTab === 'close'
            ? 'border-slate-900 text-slate-900'
            : 'border-transparent text-slate-500 hover:text-slate-700'
        "
        @click="activeTab = 'close'"
      >
        Fechamento do mês
      </button>
    </div>

    <template v-if="activeTab === 'receivables'">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <StatCard
          title="A receber"
          :value="formatMoney(summary.a_receber)"
          variant="primary"
        />
        <StatCard
          title="Vencidas"
          :value="formatMoney(summary.vencido)"
          variant="danger"
        />
        <StatCard
          title="Recebido no mês"
          :value="formatMoney(summary.recebido_mes)"
          variant="success"
        />
      </div>

      <div
        v-if="batchError"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 mb-4 text-sm text-rose-700"
      >
        {{ batchError }}
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Status</label>
            <select
              v-model="status"
              class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
              <option value="">
                Todos
              </option>
              <option value="pending">
                Pendentes
              </option>
              <option value="overdue">
                Vencidas
              </option>
              <option value="paid">
                Pagas
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Cliente</label>
            <select
              v-model="customerId"
              class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
              <option value="">
                Todos os clientes
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
            <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Vencimento de</label>
            <input
              v-model="dateFrom"
              type="date"
              class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Vencimento até</label>
            <input
              v-model="dateTo"
              type="date"
              class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-100">
          <AppButton
            variant="secondary"
            size="sm"
            @click="resetFilters"
          >
            Limpar
          </AppButton>
          <AppButton
            variant="primary"
            size="sm"
            @click="applyFilters"
          >
            Filtrar Resultados
          </AppButton>
        </div>
      </div>

      <div
        v-if="abilities.canPay && payableRows.length > 0"
        class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-3 mb-4"
      >
        <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
          <input
            type="checkbox"
            :checked="allSelected"
            class="rounded border-slate-300"
            @change="toggleSelectAll"
          />
          Selecionar todas pendentes
        </label>
        <div class="flex items-center gap-3">
          <span class="text-xs text-slate-500">{{ selected.length }} selecionada(s)</span>
          <AppButton
            variant="success"
            size="sm"
            :disabled="selected.length === 0"
            @click="openBatchModal"
          >
            Baixar selecionadas
          </AppButton>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
              <tr class="text-left text-xs font-medium text-slate-500 uppercase">
                <th
                  v-if="abilities.canPay"
                  class="px-4 py-3 w-10"
                />
                <th class="px-4 py-3">
                  Parcela
                </th>
                <th class="px-4 py-3">
                  Venda
                </th>
                <th class="px-4 py-3">
                  Cliente
                </th>
                <th class="px-4 py-3">
                  Valor
                </th>
                <th class="px-4 py-3">
                  Vencimento
                </th>
                <th class="px-4 py-3">
                  Status
                </th>
                <th class="px-4 py-3">
                  Pago em
                </th>
                <th class="px-4 py-3">
                  Método
                </th>
                <th class="px-4 py-3 text-right">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="row in installments.data"
                :key="row.id"
                class="hover:bg-slate-50/60"
              >
                <td
                  v-if="abilities.canPay"
                  class="px-4 py-3"
                >
                  <input
                    v-if="!row.is_paid"
                    type="checkbox"
                    :checked="selected.includes(row.id)"
                    class="rounded border-slate-300"
                    @change="toggleSelect(row.id)"
                  />
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">
                  {{ row.installment_number }}ª
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">
                  #{{ row.sale_id }}
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">
                  {{ row.sale?.customer?.name ?? '-' }}
                </td>
                <td class="px-4 py-3 text-sm font-medium text-slate-900">
                  {{ formatMoney(Number(row.amount)) }}
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">
                  {{ formatDate(row.due_date) }}
                </td>
                <td class="px-4 py-3">
                  <Badge
                    :variant="statusBadge(row).variant"
                    size="sm"
                  >
                    {{ statusBadge(row).label }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">
                  {{ formatDate(row.paid_date) }}
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">
                  {{ row.payment_method?.name ?? '-' }}
                </td>
                <td class="px-4 py-3 text-right">
                  <AppButton
                    v-if="!row.is_paid && abilities.canPay"
                    variant="primary"
                    size="sm"
                    @click="openPayModal(row)"
                  >
                    Baixar
                  </AppButton>
                  <span
                    v-else
                    class="text-xs text-slate-400"
                  >-</span>
                </td>
              </tr>
              <tr v-if="installments.data.length === 0">
                <td
                  :colspan="abilities.canPay ? 10 : 9"
                  class="px-4 py-10 text-center text-sm text-slate-400"
                >
                  Nenhuma parcela encontrada.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4">
          <Pagination :links="installments.links || []" />
        </div>
      </div>
    </template>


    <template v-else>
      <div
        v-if="monthCloseError"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 mb-4 text-sm text-rose-700"
      >
        {{ monthCloseError }}
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 capitalize">
              {{ monthLabel }}
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">
              {{
                currentMonthClose
                  ? 'Mês fechado. Totais congelados e baixas bloqueadas.'
                  : 'Ao fechar, o mês é congelado: baixas e movimentações do período ficam bloqueadas.'
              }}
            </p>
          </div>
          <Badge :variant="currentMonthClose ? 'success' : 'warning'">
            {{ currentMonthClose ? 'Fechado' : 'Aberto' }}
          </Badge>
        </div>

        <template v-if="monthSnapshot">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <StatCard
              title="Vendas concluídas"
              :value="formatMoney(monthSnapshot.sales_completed_total)"
            />
            <StatCard
              title="Parcelas recebidas no mês"
              :value="formatMoney(monthSnapshot.installments_received_total)"
            />
            <StatCard
              title="Vendas à vista no mês"
              :value="formatMoney(monthSnapshot.spot_sales_total)"
            />
            <StatCard
              title="Despesas pagas no mês"
              :value="formatMoney(monthSnapshot.expenses_paid_total)"
              variant="danger"
            />
            <StatCard
              title="Saldo do período"
              :value="formatMoney(monthSnapshot.cash_balance)"
              variant="success"
            />
          </div>

          <div class="flex justify-end mt-6 pt-4 border-t border-slate-100">
            <AppButton
              v-if="!currentMonthClose && abilities.canCloseMonth"
              variant="danger"
              size="md"
              @click="showCloseConfirm = true"
            >
              Fechar mês
            </AppButton>
            <p
              v-else-if="!currentMonthClose && !abilities.canCloseMonth"
              class="text-xs text-slate-400 self-center"
            >
              Somente administrador ou financeiro pode fechar o mês.
            </p>
          </div>
        </template>
        <p
          v-else
          class="text-sm text-slate-400"
        >
          Sem permissão para visualizar o fechamento.
        </p>
      </div>
    </template>

    <Modal
      :show="showPayModal"
      title="Baixar parcela"
      max-width="md"
      @close="showPayModal = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="submitPay"
      >
        <div
          v-if="payTarget"
          class="rounded-lg bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600"
        >
          Venda #{{ payTarget.sale_id }} · {{ payTarget.installment_number }}ª parcela ·
          <span class="font-semibold text-slate-900">{{ formatMoney(Number(payTarget.amount)) }}</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Forma de pagamento *</label>
          <select
            v-model="payForm.payment_method_id"
            class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            :class="payForm.errors.payment_method_id ? 'border-rose-300' : ''"
          >
            <option
              value=""
              disabled
            >
              Selecione
            </option>
            <option
              v-for="pm in paymentMethods"
              :key="pm.id"
              :value="String(pm.id)"
            >
              {{ pm.name }}
            </option>
          </select>
          <p
            v-if="payForm.errors.payment_method_id"
            class="mt-1 text-xs text-rose-600"
          >
            {{ payForm.errors.payment_method_id }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Data do recebimento</label>
          <input
            v-model="payForm.paid_date"
            type="date"
            class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
          />
          <p
            v-if="payForm.errors.paid_date"
            class="mt-1 text-xs text-rose-600"
          >
            {{ payForm.errors.paid_date }}
          </p>
        </div>

        <p
          v-if="batchError"
          class="text-xs text-rose-600"
        >
          {{ batchError }}
        </p>

        <div class="flex justify-end gap-2.5 pt-2">
          <AppButton
            variant="secondary"
            size="sm"
            :disabled="payForm.processing"
            @click="showPayModal = false"
          >
            Cancelar
          </AppButton>
          <AppButton
            type="submit"
            variant="primary"
            size="sm"
            :loading="payForm.processing"
          >
            Confirmar baixa
          </AppButton>
        </div>
      </form>
    </Modal>

    <Modal
      :show="showBatchModal"
      title="Baixar parcelas selecionadas"
      max-width="md"
      @close="showBatchModal = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="submitBatch"
      >
        <div class="rounded-lg bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600">
          {{ selected.length }} parcela(s) serão baixadas com os dados abaixo.
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Forma de pagamento *</label>
          <select
            v-model="payForm.payment_method_id"
            class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            :class="payForm.errors.payment_method_id ? 'border-rose-300' : ''"
          >
            <option
              value=""
              disabled
            >
              Selecione
            </option>
            <option
              v-for="pm in paymentMethods"
              :key="pm.id"
              :value="String(pm.id)"
            >
              {{ pm.name }}
            </option>
          </select>
          <p
            v-if="payForm.errors.payment_method_id"
            class="mt-1 text-xs text-rose-600"
          >
            {{ payForm.errors.payment_method_id }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Data do recebimento</label>
          <input
            v-model="payForm.paid_date"
            type="date"
            class="w-full rounded-lg border-slate-200 px-3.5 py-2 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
          />
        </div>

        <div class="flex justify-end gap-2.5 pt-2">
          <AppButton
            variant="secondary"
            size="sm"
            :disabled="batchLoading"
            @click="showBatchModal = false"
          >
            Cancelar
          </AppButton>
          <AppButton
            type="submit"
            variant="success"
            size="sm"
            :loading="batchLoading"
          >
            Confirmar baixas
          </AppButton>
        </div>
      </form>
    </Modal>

    <ConfirmDialog
      :show="showCloseConfirm"
      title="Fechar mês"
      :message="`Fechar ${monthLabel}? O período será congelado e novas baixas dentro dele serão bloqueadas.`"
      confirm-label="Fechar mês"
      variant="danger"
      :loading="closeLoading"
      @confirm="submitCloseMonth"
      @cancel="showCloseConfirm = false"
    />
  </AppLayout>
</template>
