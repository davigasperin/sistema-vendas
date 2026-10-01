<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <GuestLayout title="Redefinir Senha">
        <Head title="Redefinição de Senha" />

        <div class="mb-6">
            <h1 class="text-xl font-bold tracking-tight text-white">Criar nova senha</h1>
            <p class="text-xs text-slate-400 mt-1">Defina sua nova senha de acesso ao sistema.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">E-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    readonly
                    class="w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3.5 py-2.5 text-xs text-slate-400"
                />
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5">Nova Senha *</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    placeholder="••••••••"
                    class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400 font-medium">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1.5">Confirmar Nova Senha *</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    placeholder="••••••••"
                    class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                />
            </div>

            <div class="pt-2 flex items-center justify-between">
                <Link href="/login" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                    &larr; Voltar ao Login
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/20 disabled:opacity-50 transition-colors cursor-pointer"
                >
                    {{ form.processing ? 'Atualizando...' : 'Salvar Nova Senha' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
