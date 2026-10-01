<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string | number | null | undefined;
    label?: string;
    id?: string;
    type?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    hint?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputId = computed(() => props.id || `input-${Math.random().toString(36).substring(2, 9)}`);
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            :for="inputId"
            class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        <div class="relative">
            <input
                :id="inputId"
                :type="type"
                :value="modelValue ?? ''"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                class="w-full rounded-xl border bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-all duration-150 focus:outline-hidden focus:ring-2 focus:ring-offset-0 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed"
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
