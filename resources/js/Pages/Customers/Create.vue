<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    birth_date: '',
});

function submit() {
    form.post('/customers');
}
</script>

<template>
    <AppLayout title="Novo Cliente">
        <Head title="Cadastrar Cliente" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Novo Cliente</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Cadastre um cliente para emissão de pedidos e relatórios.</p>
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
                        placeholder="Ex: João da Silva"
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
                            placeholder="joao@email.com"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Telefone / WhatsApp</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="(11) 98888-7777"
                        />
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Endereço Residencial/Comercial</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Rua, Número, Bairro, Cidade"
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
                        {{ form.processing ? 'Salvando...' : 'Cadastrar Cliente' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
