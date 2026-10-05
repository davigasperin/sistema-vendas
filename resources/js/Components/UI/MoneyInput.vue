<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: number | string | null | undefined;
    label?: string;
    id?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    hint?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: '0,00',
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const inputId = computed(() => props.id || `money-${Math.random().toString(36).substring(2, 9)}`);

function handleInput(e: Event) {
    const target = e.target as HTMLInputElement;
    const raw = target.value.replace(/\D/g, '');
    const num = raw ? parseFloat(raw) / 100 : 0;
    emit('update:modelValue', num);
}

const displayValue = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return '';
    }
    const num = typeof props.modelValue === 'string' ? parseFloat(props.modelValue) : props.modelValue;
    if (isNaN(num)) return '';
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
});
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            :for="inputId"
            class="block text-xs font-medium text-slate-700 mb-1.5"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <span class="text-xs font-semibold text-slate-400">R$</span>
            </div>
            <input
                :id="inputId"
                type="text"
                inputmode="numeric"
                :value="displayValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                @input="handleInput"
                class="w-full rounded-lg border bg-white pl-10 pr-3.5 py-2 text-sm tabular-nums text-slate-900 placeholder:text-slate-400 transition-colors duration-150 focus:outline-hidden focus:ring-2 focus:ring-offset-0 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed"
                :class="[
                    error
                        ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-slate-200 hover:border-slate-300 focus:border-slate-900 focus:ring-slate-900/10',
                ]"
            />
        </div>
        <p v-if="error" class="mt-1 text-xs text-rose-600 font-medium">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
