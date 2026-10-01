<script setup lang="ts">
import { computed } from 'vue';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    modelValue: string | number | null | undefined;
    options: Option[];
    label?: string;
    id?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const selectId = computed(() => props.id || `select-${Math.random().toString(36).substring(2, 9)}`);
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            :for="selectId"
            class="block text-xs font-semibold text-slate-700 tracking-wide mb-1.5"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        <select
            :id="selectId"
            :value="modelValue ?? ''"
            :required="required"
            :disabled="disabled"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
            class="w-full rounded-xl border bg-white px-3.5 py-2 text-sm text-slate-900 transition-all duration-150 focus:outline-hidden focus:ring-2 focus:ring-offset-0 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed cursor-pointer"
            :class="[
                error
                    ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20'
                    : 'border-slate-200 hover:border-slate-300 focus:border-slate-900 focus:ring-slate-900/10',
            ]"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option
                v-for="opt in options"
                :key="opt.value"
                :value="opt.value"
            >
                {{ opt.label }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-xs text-rose-600 font-medium">{{ error }}</p>
    </div>
</template>
