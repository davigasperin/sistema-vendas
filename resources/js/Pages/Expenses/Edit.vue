<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import SelectInput from '@/Components/UI/SelectInput.vue';
import MoneyInput from '@/Components/UI/MoneyInput.vue';
import type { Expense, ExpenseCategory } from '@/types';

const props = defineProps<{
    expense: Expense;
    categories: ExpenseCategory[];
}>();

const form = useForm({
    description: props.expense.description,
    amount: Number(props.expense.amount),
    due_date: props.expense.due_date ? props.expense.due_date.substring(0, 10) : '',
    paid_date: props.expense.paid_date ? props.expense.paid_date.substring(0, 10) : '',
    category_id: props.expense.category_id,
    type: props.expense.type,
    status: props.expense.status,
    notes: props.expense.notes || '',
});

const categoryOptions = computed(() => {
    return props.categories.map((c) => ({
        value: c.id,
        label: c.name,
    }));
});

const typeOptions = [
    { value: 'expense', label: 'Despesa (Saída de Caixa)' },
    { value: 'income', label: 'Receita (Entrada Manual)' },
];

function submit() {
    form.put(`/expenses/${props.expense.id}`);
}
</script>

<template>
    <AppLayout title="Editar Lançamento">
        <Head :title="`Editar: ${expense.description}`" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/80">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Editar Lançamento</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Atualize as informações financeiras da conta.</p>
                </div>
                <Link
                    href="/expenses"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-5">
                <div>
                    <TextInput
                        v-model="form.description"
                        label="Descrição"
                        required
                        :error="form.errors.description"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <SelectInput
                            v-model="form.type"
                            :options="typeOptions"
                            label="Tipo"
                            required
                        />
                    </div>

                    <div>
                        <SelectInput
                            v-model="form.category_id"
                            :options="categoryOptions"
                            label="Categoria"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <MoneyInput
                            v-model="form.amount"
                            label="Valor"
                            required
                            :error="form.errors.amount"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.due_date"
                            type="date"
                            label="Vencimento"
                            required
                            :error="form.errors.due_date"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.paid_date"
                            type="date"
                            label="Data do Pagamento"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5">Observações</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all"
                    />
                </div>

                <div class="flex justify-end gap-2.5 pt-6 border-t border-slate-100">
                    <Link href="/expenses">
                        <AppButton variant="secondary" size="md">Cancelar</AppButton>
                    </Link>
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                    >
                        Salvar Alterações
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
