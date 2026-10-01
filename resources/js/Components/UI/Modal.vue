<script setup lang="ts">
defineProps<{
    show: boolean;
    title?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div
                    class="w-full rounded-2xl bg-white p-6 shadow-xl transition-all"
                    :class="[
                        maxWidth === 'sm' ? 'max-w-sm' : '',
                        maxWidth === 'md' ? 'max-w-md' : '',
                        maxWidth === 'lg' ? 'max-w-lg' : '',
                        maxWidth === 'xl' ? 'max-w-xl' : '',
                        maxWidth === '2xl' ? 'max-w-2xl' : 'max-w-lg',
                    ]"
                >
                    <div v-if="title" class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-500"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
