<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    description: '',
    price: '',
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
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Novo Produto</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Cadastre um novo item para venda e controle de estoque.</p>
                </div>
                <Link
                    href="/products"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nome do Produto *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ex: Mouse Sem Fio Ergonômico"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Descrição</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Detalhes, especificações técnicas..."
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Preço Unitário (R$) *</label>
                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0,00"
                        />
                        <p v-if="form.errors.price" class="mt-1 text-xs text-rose-600">{{ form.errors.price }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Estoque Inicial *</label>
                        <input
                            v-model.number="form.stock"
                            type="number"
                            min="0"
                            required
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.stock" class="mt-1 text-xs text-rose-600">{{ form.errors.stock }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alerta Estoque Baixo</label>
                        <input
                            v-model.number="form.low_stock_threshold"
                            type="number"
                            min="0"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input
                        id="active"
                        v-model="form.active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label for="active" class="text-xs font-medium text-slate-700">Produto ativo para vendas imediatamente</label>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <Link
                        href="/products"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Cadastrando...' : 'Cadastrar Produto' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
