<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <GuestLayout title="Recuperar Senha">
        <Head title="Recuperação de Senha" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-white">Recuperar Senha</h1>
            <p class="text-xs text-slate-400 mt-1">Informe seu e-mail para receber as instruções de redefinição.</p>
        </div>

        <div v-if="status" class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs font-medium text-emerald-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-medium text-slate-300 mb-1.5">
                    E-mail Cadastrado <span class="text-rose-400">*</span>
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    placeholder="seu.email@empresa.com"
                    class="w-full rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 transition-colors focus:border-white/30 focus:ring-2 focus:ring-white/20"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-400 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="pt-2 flex items-center justify-between">
                <Link
                    href="/login"
                    class="text-xs font-medium text-slate-400 hover:text-white transition-colors"
                >
                    &larr; Voltar para o Login
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="py-2.5 px-4 rounded-lg bg-white text-slate-900 hover:bg-slate-100 font-semibold text-xs disabled:opacity-50 transition-colors cursor-pointer"
                >
                    {{ form.processing ? 'Enviando...' : 'Enviar Link' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
