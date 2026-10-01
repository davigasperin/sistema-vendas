<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';

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
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/80">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Novo Cliente</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Cadastre o cliente para emissão de pedidos e relatórios.</p>
                </div>
                <Link
                    href="/customers"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-5">
                <div>
                    <TextInput
                        v-model="form.name"
                        label="Nome Completo / Razão Social"
                        placeholder="Ex: Carlos Eduardo Silva"
                        required
                        :error="form.errors.name"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <TextInput
                            v-model="form.email"
                            type="email"
                            label="E-mail"
                            placeholder="carlos@empresa.com"
                            :error="form.errors.email"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.phone"
                            type="text"
                            label="Telefone / WhatsApp"
                            placeholder="(11) 98888-7777"
                            :error="form.errors.phone"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <TextInput
                            v-model="form.address"
                            label="Endereço Completo"
                            placeholder="Rua, Número, Bairro, Cidade"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.birth_date"
                            type="date"
                            label="Data de Nascimento"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-6 border-t border-slate-100">
                    <Link href="/customers">
                        <AppButton variant="secondary" size="md">Cancelar</AppButton>
                    </Link>
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                    >
                        Cadastrar Cliente
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
