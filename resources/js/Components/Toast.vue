<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-x-10 opacity-0"
        enter-to-class="transform translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-x-0 opacity-100"
        leave-to-class="transform translate-x-10 opacity-0"
    >
        <div v-if="show" class="fixed top-20 right-6 z-[9999] p-4 bg-white shadow-lg rounded-lg border-l-4"
             :class="type === 'success' ? 'border-green-500' : 'border-red-500'">
            <p class="text-sm font-semibold text-slate-800">{{ message }}</p>
        </div>
    </Transition>
</template>

<script setup>
import { ref } from 'vue';

const show = ref(false);
const message = ref('');
const type = ref('success');

const triggerToast = (msg, t = 'success') => {
    message.value = msg;
    type.value = t;
    show.value = true;
    
    // Auto hide setelah 3 detik
    setTimeout(() => {
        show.value = false;
    }, 3000);
};

defineExpose({ triggerToast });
</script>