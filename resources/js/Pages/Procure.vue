<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Procure" />

    <AuthenticatedLayout>
        <template #header>
            Procure
        </template>

        <div class="py-6 px-2 md:px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500 font-medium">Total Procurement</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">1,284</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500 font-medium">Active Vendors</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">42</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500 font-medium">Pending Approvals</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">08</h3>
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 md:p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Welcome Back!</h3>
                <p class="text-gray-600 leading-relaxed max-w-2xl">
                    You're logged in and ready to go. Manage your procurement cycles, 
                    monitor vendor performance, and track your daily transactions 
                    directly from this dashboard.
                </p>
                
                <div class="mt-8 flex gap-3">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                        Start New Procurement
                    </button>
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                        View Reports
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>