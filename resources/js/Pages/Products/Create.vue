<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import MoneyInput from '@/Components/UI/MoneyInput.vue';

const form = useForm({
    name: '',
    description: '',
    price: 0,
    stock: 0,
    low_stock_threshold: 5,
    active: true,
});

function submit() {
    form.post('/products');
}
</script>

<template>
    <AppLayout title="Novo Produto">
        <Head title="Cadastrar Produto" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Novo Produto</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Cadastre um item no catálogo com estoque e precificação oficial.</p>
                </div>
                <Link
                    href="/products"
                    class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 space-y-5">
                <div>
                    <TextInput
                        v-model="form.name"
                        label="Nome do Produto"
                        placeholder="Ex: Teclado Mecânico Switch Blue"
                        required
                        :error="form.errors.name"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Descrição Comercial</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-colors"
                        placeholder="Especificações técnicas, dimensões, garantia..."
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <MoneyInput
                            v-model="form.price"
                            label="Preço de Venda"
                            required
                            :error="form.errors.price"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model.number="form.stock"
                            type="number"
                            label="Estoque Inicial"
                            required
                            :error="form.errors.stock"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model.number="form.low_stock_threshold"
                            type="number"
                            label="Alerta de Estoque Baixo"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input
                        id="active"
                        v-model="form.active"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-slate-300 text-slate-800 focus:ring-slate-900/10 cursor-pointer"
                    />
                    <label for="active" class="text-xs font-medium text-slate-700 cursor-pointer">Disponibilizar item para vendas imediatamente</label>
                </div>

                <div class="flex justify-end gap-2.5 pt-6 border-t border-slate-100">
                    <Link href="/products">
                        <AppButton variant="secondary" size="md">Cancelar</AppButton>
                    </Link>
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                    >
                        Cadastrar Produto
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
