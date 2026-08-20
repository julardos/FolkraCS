<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tenants: Array });
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Tenants</h1>
        <p class="text-sm text-muted-foreground">Manage tenant accounts and pending invites.</p>
      </div>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
          <tr>
            <th class="p-3">Name</th>
            <th class="p-3">Slug</th>
            <th class="p-3">Status</th>
            <th class="p-3">Created</th>
            <th class="p-3">Pending Invites</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in tenants" :key="t.id" class="border-t">
            <td class="p-3 font-medium">{{ t.name }}</td>
            <td class="p-3 font-mono text-sm">{{ t.slug }}</td>
            <td class="p-3">{{ t.status }}</td>
            <td class="p-3">{{ t.created_at }}</td>
            <td class="p-3">{{ t.pending_invites }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
