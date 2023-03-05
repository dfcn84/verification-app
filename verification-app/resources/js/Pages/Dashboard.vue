<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FileUpload from '@/Components/FileUpload.vue';
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

console.log("User ID", user.id);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-bold text-lg text-gray-900">Verify here</h3>
                    <FileUpload />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
