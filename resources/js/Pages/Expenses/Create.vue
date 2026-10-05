<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import SelectInput from '@/Components/UI/SelectInput.vue';
import MoneyInput from '@/Components/UI/MoneyInput.vue';
import Modal from '@/Components/UI/Modal.vue';
import type { ExpenseCategory } from '@/types';

const props = defineProps<{
    categories: ExpenseCategory[];
}>();

const localCategories = ref<ExpenseCategory[]>([...props.categories]);

const showCategoryModal = ref(false);
const newCategoryName = ref('');
const isCreatingCategory = ref(false);
const categoryError = ref('');

const form = useForm({
    description: '',
    amount: 0,
    due_date: new Date().toISOString().split('T')[0],
    category_id: '' as number | '',
    type: 'expense',
    status: 'pending',
    notes: '',
});

const categoryOptions = computed(() => {
    return localCategories.value
        .filter((c) => !c.type || c.type === form.type)
        .map((c) => ({
            value: c.id,
            label: c.name,
        }));
});

const typeOptions = [
    { value: 'expense', label: 'Despesa (Saída de Caixa)' },
    { value: 'income', label: 'Receita (Entrada Manual)' },
];

async function handleCreateCategory() {
    if (!newCategoryName.value.trim()) {
        categoryError.value = 'Informe o nome da categoria.';
        return;
    }

    isCreatingCategory.value = true;
    categoryError.value = '';

    try {
        const response = await axios.post<ExpenseCategory>('/expense-categories', {
            name: newCategoryName.value.trim(),
            type: form.type,
        });

        localCategories.value.push(response.data);
        form.category_id = response.data.id;
        newCategoryName.value = '';
        showCategoryModal.value = false;
    } catch (err: any) {
        categoryError.value = err.response?.data?.message || 'Erro ao criar categoria.';
    } finally {
        isCreatingCategory.value = false;
    }
}

function submit() {
    form.post('/expenses');
}
</script>

<template>
    <AppLayout title="Novo Lançamento">
        <Head title="Novo Lançamento Financeiro" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Novo Lançamento</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Registre contas a pagar ou entradas financeiras avulsas.</p>
                </div>
                <Link
                    href="/expenses"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 space-y-5">
                <div>
                    <TextInput
                        v-model="form.description"
                        label="Descrição do Lançamento"
                        placeholder="Ex: Aluguel do Escritório, Energia Elétrica, Taxa de Hospedagem..."
                        required
                        :error="form.errors.description"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <SelectInput
                            v-model="form.type"
                            :options="typeOptions"
                            label="Tipo de Lançamento"
                            required
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-medium text-slate-700">
                                Categoria <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                @click="showCategoryModal = true"
                                class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                + Nova Categoria
                            </button>
                        </div>
                        <SelectInput
                            v-model="form.category_id"
                            :options="categoryOptions"
                            placeholder="Selecione uma categoria..."
                            required
                            :error="form.errors.category_id"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <MoneyInput
                            v-model="form.amount"
                            label="Valor do Lançamento"
                            required
                            :error="form.errors.amount"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.due_date"
                            type="date"
                            label="Data de Vencimento"
                            required
                            :error="form.errors.due_date"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Observações Internas</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors"
                        placeholder="Informações adicionais de conciliação..."
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
                        Salvar Lançamento
                    </AppButton>
                </div>
            </form>

            <!-- Modal Criar Categoria Inline -->
            <Modal
                :show="showCategoryModal"
                title="Nova Categoria Financeira"
                max-width="sm"
                @close="showCategoryModal = false"
            >
                <div class="space-y-4">
                    <div>
                        <TextInput
                            v-model="newCategoryName"
                            label="Nome da Categoria"
                            placeholder="Ex: Marketing, Logística, Software..."
                            required
                            :error="categoryError"
                        />
                    </div>
                    <p class="text-xs text-slate-500">
                        A categoria será criada automaticamente para o tipo <strong class="text-slate-700">{{ form.type === 'expense' ? 'Despesa' : 'Receita' }}</strong>.
                    </p>
                    <div class="flex justify-end gap-2 pt-2">
                        <AppButton
                            variant="secondary"
                            size="sm"
                            :disabled="isCreatingCategory"
                            @click="showCategoryModal = false"
                        >
                            Cancelar
                        </AppButton>
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="isCreatingCategory"
                            @click="handleCreateCategory"
                        >
                            Salvar Categoria
                        </AppButton>
                    </div>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
