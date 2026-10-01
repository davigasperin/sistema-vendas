<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
}
</script>

<template>
    <GuestLayout title="Entrar">
        <Head title="Acesso ao Sistema" />

        <div class="mb-6">
            <h1 class="text-xl font-bold tracking-tight text-white">Acesse sua conta</h1>
            <p class="text-xs text-slate-400 mt-1">Informe suas credenciais para acessar o painel de vendas.</p>
        </div>

        <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-medium text-emerald-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    E-mail Corporativo <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="seu.email@empresa.com"
                        class="w-full rounded-xl border bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 transition-all duration-150 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20"
                        :class="[
                            form.errors.email
                                ? 'border-rose-500/60 focus:border-rose-500'
                                : 'border-slate-800 focus:border-blue-500 hover:border-slate-700',
                        ]"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-300">
                        Senha de Acesso <span class="text-rose-400">*</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-[11px] font-medium text-blue-400 hover:text-blue-300 transition-colors"
                    >
                        Esqueceu a senha?
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full rounded-xl border bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 transition-all duration-150 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20"
                        :class="[
                            form.errors.password
                                ? 'border-rose-500/60 focus:border-rose-500'
                                : 'border-slate-800 focus:border-blue-500 hover:border-slate-700',
                        ]"
                    />
                </div>
                <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400 font-medium">
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-slate-800 bg-slate-950/60 text-blue-600 focus:ring-blue-500/20 focus:ring-offset-0 cursor-pointer"
                    />
                    <span class="text-xs text-slate-400 select-none">Manter conectado</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs tracking-wide shadow-md shadow-blue-600/20 transition-all duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg
                        v-if="form.processing"
                        class="animate-spin -ml-0.5 h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>{{ form.processing ? 'Autenticando...' : 'Acessar Painel' }}</span>
                </button>
            </div>
        </form>

        <!-- Demo credentials help note for evaluation/portfolio -->
        <div class="mt-6 pt-4 border-t border-slate-800/60 text-[11px] text-slate-400 bg-slate-950/40 p-3 rounded-xl">
            <span class="font-semibold text-slate-300 block mb-1">Acesso de Demonstração:</span>
            <div class="flex justify-between">
                <span>Admin: <strong class="text-slate-200">admin@sistema.com</strong></span>
                <span>Senha: <strong class="text-slate-200">password</strong></span>
            </div>
        </div>
    </GuestLayout>
</template>
