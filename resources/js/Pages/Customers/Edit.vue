<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Customer } from '@/types';

const props = defineProps<{
    customer: Customer;
}>();

const form = useForm({
    name: props.customer.name,
    email: props.customer.email || '',
    phone: props.customer.phone || '',
    address: props.customer.address || '',
    birth_date: props.customer.birth_date ? props.customer.birth_date.substring(0, 10) : '',
});

function submit() {
    form.put(`/customers/${props.customer.id}`);
}
</script>

<template>
    <AppLayout title="Editar Cliente">
        <Head :title="`Editar: ${customer.name}`" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar Cliente</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Atualize os dados de contato do cliente.</p>
                </div>
                <Link
                    href="/customers"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nome Completo *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">E-mail</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Telefone</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Endereço</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Data de Nascimento</label>
                        <input
                            v-model="form.birth_date"
                            type="date"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <Link
                        href="/customers"
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
