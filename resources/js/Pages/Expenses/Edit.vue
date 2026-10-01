<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Expense, ExpenseCategory } from '@/types';

const props = defineProps<{
    expense: Expense;
    categories: ExpenseCategory[];
}>();

const form = useForm({
    description: props.expense.description,
    amount: props.expense.amount,
    due_date: props.expense.due_date ? props.expense.due_date.substring(0, 10) : '',
    paid_date: props.expense.paid_date ? props.expense.paid_date.substring(0, 10) : '',
    category_id: props.expense.category_id,
    type: props.expense.type,
    status: props.expense.status,
    notes: props.expense.notes || '',
});

function submit() {
    form.put(`/expenses/${props.expense.id}`);
}
</script>

<template>
    <AppLayout title="Editar Despesa">
        <Head :title="`Editar: ${expense.description}`" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar Lançamento</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Atualize as informações financeiras da conta.</p>
                </div>
                <Link
                    href="/expenses"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Descrição *</label>
                    <input
                        v-model="form.description"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tipo</label>
                        <select
                            v-model="form.type"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="expense">Despesa (Saída)</option>
                            <option value="income">Receita (Entrada Manual)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Categoria *</label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Valor (R$) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Vencimento *</label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Data de Pagamento</label>
                        <input
                            v-model="form.paid_date"
                            type="date"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Observações</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <Link
                        href="/expenses"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
