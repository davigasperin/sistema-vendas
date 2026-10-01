<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { ExpenseCategory } from '@/types';

defineProps<{
    categories: ExpenseCategory[];
}>();

const form = useForm({
    description: '',
    amount: '',
    due_date: new Date().toISOString().split('T')[0],
    category_id: '' as number | '',
    type: 'expense',
    status: 'pending',
    notes: '',
});

function submit() {
    form.post('/expenses');
}
</script>

<template>
    <AppLayout title="Nova Despesa">
        <Head title="Cadastrar Despesa / Receita" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Nova Despesa / Receita</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Registre contas a pagar ou entradas financeiras manuais.</p>
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
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Descrição do Lançamento *</label>
                    <input
                        v-model="form.description"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ex: Aluguel do Escritório, Conta de Energia..."
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tipo de Lançamento *</label>
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
                            <option value="">Selecione uma categoria...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.category_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Valor (R$) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0,00"
                        />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Data de Vencimento *</label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.due_date" class="mt-1 text-xs text-rose-600">{{ form.errors.due_date }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Observações</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Detalhes adicionais, forma de quitação, número da nota fiscal..."
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
                        {{ form.processing ? 'Salvando...' : 'Salvar Lançamento' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
