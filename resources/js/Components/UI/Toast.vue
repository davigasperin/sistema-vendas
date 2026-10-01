<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const message = ref<string | null>(null);
const type = ref<'success' | 'error' | 'info'>('success');
const visible = ref(false);

let timeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            message.value = flash.success;
            type.value = 'success';
            showToast();
        } else if (flash?.error) {
            message.value = flash.error;
            type.value = 'error';
            showToast();
        } else if (flash?.info) {
            message.value = flash.info;
            type.value = 'info';
            showToast();
        }
    },
    { deep: true, immediate: true }
);

function showToast() {
    visible.value = true;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        visible.value = false;
    }, 4000);
}
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible && message"
            class="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm font-medium shadow-lg"
            :class="[
                type === 'success' ? 'text-emerald-700' : '',
                type === 'error' ? 'text-rose-700' : '',
                type === 'info' ? 'text-slate-700' : '',
            ]"
        >
            <span>{{ message }}</span>
            <button @click="visible = false" class="text-slate-400 hover:text-slate-600">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </Transition>
</template>
