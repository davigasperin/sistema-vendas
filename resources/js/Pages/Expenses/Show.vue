<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import type { Expense } from '@/types';

const props = defineProps<{
    expense: Expense;
}>();

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

function formatMoney(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function markPaid() {
    router.patch(`/expenses/${props.expense.id}/mark-paid`, {}, { preserveScroll: true });
}

function handleDeleteExpense() {
    isDeleting.value = true;
    router.delete(`/expenses/${props.expense.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :title="expense.description">
        <Head :title="`Despesa: ${expense.description}`" />

        <div class="max-w-3xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ expense.description }}</h1>
                    <Badge :variant="expense.status === 'paid' ? 'success' : expense.status === 'overdue' ? 'danger' : 'warning'">
                        {{ expense.status_label }}
                    </Badge>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/expenses/${expense.id}/edit`"
                        class="px-4 py-2 rounded-lg bg-slate-800 text-xs font-semibold text-white hover:bg-slate-900 transition-colors"
                    >
                        Editar
                    </Link>
                    <button
                        v-if="expense.status !== 'paid'"
                        type="button"
                        @click="markPaid"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors"
                    >
                        Marcar como Pago
                    </button>
                    <button
                        type="button"
                        @click="showDeleteDialog = true"
                        class="px-4 py-2 rounded-lg bg-rose-50 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors"
                    >
                        Excluir
                    </button>
                    <Link
                        href="/expenses"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ml-2"
                    >
                        &larr; Voltar
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-400 uppercase">Valor</span>
                    <p class="text-2xl font-semibold mt-1" :class="expense.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                        {{ formatMoney(expense.amount) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-400 uppercase">Tipo</span>
                    <p class="text-base font-semibold text-slate-900 mt-1">{{ expense.type_label }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <span class="text-xs font-medium text-slate-400 uppercase">Categoria</span>
                    <p class="text-base font-semibold text-slate-900 mt-1">{{ expense.category?.name || '-' }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Detalhes</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Data de Vencimento</span>
                        <span class="font-semibold text-slate-900">{{ formatDate(expense.due_date) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Data de Pagamento</span>
                        <span class="font-semibold text-slate-900">{{ formatDate(expense.paid_date) }}</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs text-slate-400 block">Observações</span>
                        <p class="text-slate-600 mt-1">{{ expense.notes || 'Nenhuma observação.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="showDeleteDialog"
            title="Excluir Lançamento"
            message="Deseja realmente excluir este lançamento? Esta ação não pode ser desfeita."
            confirm-label="Excluir"
            cancel-label="Cancelar"
            variant="danger"
            :loading="isDeleting"
            @confirm="handleDeleteExpense"
            @cancel="showDeleteDialog = false"
        />
    </AppLayout>
</template>
