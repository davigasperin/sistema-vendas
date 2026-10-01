<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import TextInput from '@/Components/UI/TextInput.vue';
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
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Editar Cliente</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Atualize os dados cadastrais e de contato.</p>
                </div>
                <Link
                    href="/customers"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                    &larr; Voltar
                </Link>
            </div>

            <form @submit.prevent="submit" class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 space-y-5">
                <div>
                    <TextInput
                        v-model="form.name"
                        label="Nome Completo / Razão Social"
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
                            :error="form.errors.email"
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.phone"
                            type="text"
                            label="Telefone"
                            :error="form.errors.phone"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <TextInput
                            v-model="form.address"
                            label="Endereço"
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
                        Salvar Alterações
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
