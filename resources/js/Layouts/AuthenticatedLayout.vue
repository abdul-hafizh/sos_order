<script setup>
import { ref, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Toast from '@/Components/Toast.vue';

const page = usePage();
const toastRef = ref(null);

const triggerToastRef = (msg, type) => {
    nextTick(() => {
        if (toastRef.value) {
            toastRef.value.triggerToast(msg, type);
        } else {
            console.error("Toast component not found!");
        }
    });
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) triggerToastRef(flash.success, 'success');
    if (flash?.error) triggerToastRef(flash.error, 'error');
}, { immediate: true, deep: true });
</script>

<template>
    <div class="min-h-screen flex flex-col bg-slate-100">
        <Toast ref="toastRef" />

        <!-- Top Navbar -->
        <Navbar
            :user="$page.props.auth.user"
            :tipes="$page.props.navTipes"
            @open-login-modal="$emit('open-login-modal')"
            @toggle-cart="$emit('toggle-cart')"
            @open-image-search="$emit('open-image-search')"
        />

        <!-- Redesigned Page Sub-Header -->
        <header v-if="$slots.header" class="bg-white border-b border-slate-200/80 shadow-xs py-3 md:py-4 px-3 sm:px-4 lg:px-6">
            <div class="max-w-[1800px] w-full mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full shrink-0"></div>
                    <h2 class="font-bold text-xl md:text-2xl text-slate-900 tracking-tight leading-tight">
                        <slot name="header" />
                    </h2>
                </div>
            </div>
        </header>

        <!-- Widened Main Page Content -->
        <main class="flex-1 p-3 sm:p-4 md:p-5 max-w-[1800px] w-full mx-auto">
            <slot />
        </main>
    </div>
</template>