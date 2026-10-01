<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import MoneyInput from '@/Components/UI/MoneyInput.vue';
import type { Product } from '@/types';

const props = defineProps<{
    product: Product;
}>();

const form = useForm({
    name: props.product.name,
    description: props.product.description || '',
    price: Number(props.product.price),
    stock: props.product.stock,
    low_stock_threshold: props.product.low_stock_threshold,
    active: Boolean(props.product.active),
});

function submit() {
    form.put(`/products/${props.product.id}`);
}
</script>

<template>
    <AppLayout title="Editar Produto">
        <Head :title="`Editar: ${product.name}`" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/80">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Editar Produto</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Atualize as informações do item no catálogo.</p>
                </div>
                <Link
                    href="/products"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-5">
                <div>
                    <TextInput
                        v-model="form.name"
                        label="Nome do Produto"
                        required
                        :error="form.errors.name"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5">Descrição</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10 transition-all"
                    />
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
                            label="Estoque Atual"
                            required
                            :error="form.errors.stock"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model.number="form.low_stock_threshold"
                            type="number"
                            label="Alerta Estoque Baixo"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input
                        id="active"
                        v-model="form.active"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                    />
                    <label for="active" class="text-xs font-medium text-slate-700 cursor-pointer">Disponível para venda</label>
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
                        Salvar Alterações
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
