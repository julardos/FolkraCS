<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { MessageSquare, User, Bot, UserCheck } from 'lucide-vue-next';

const props = defineProps({ conversations: Object });

function takeover(customerId) {
  router.post(`/customers/${customerId}/takeover`);
}
function release(customerId) {
  router.delete(`/customers/${customerId}/takeover`);
}

const statusVariant = (s) => ({ active: 'success', escalated: 'destructive', closed: 'secondary' }[s] ?? 'outline');
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Conversations</h1>
      <p class="text-muted-foreground text-sm mt-1">{{ conversations.total }} total · {{ conversations.data.filter(c => c.status === 'active').length }} active on this page</p>
    </div>

    <div v-if="!conversations.data.length" class="text-center py-20">
      <MessageSquare class="w-10 h-10 mx-auto text-muted-foreground mb-3" />
      <p class="text-muted-foreground">No conversations yet. Messages from customers will appear here.</p>
    </div>

    <div class="space-y-2">
      <Card v-for="conv in conversations.data" :key="conv.id" class="hover:shadow-sm transition-shadow">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-9 h-9 rounded-full bg-muted flex items-center justify-center">
                <User class="w-4 h-4 text-muted-foreground" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-sm">{{ conv.customer?.name ?? conv.customer?.push_name ?? conv.customer?.phone }}</p>
                  <Badge :variant="statusVariant(conv.status)" class="text-xs">{{ conv.status }}</Badge>
                  <Badge v-if="conv.customer?.is_human_takeover" variant="outline" class="text-xs text-amber-600 border-amber-300">
                    <UserCheck class="w-3 h-3 mr-1" /> Agent
                  </Badge>
                </div>
                <p class="text-xs text-muted-foreground">{{ conv.customer?.phone }} · {{ conv.messages_count }} messages</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Button
                v-if="!conv.customer?.is_human_takeover"
                variant="outline"
                size="sm"
                @click="takeover(conv.customer?.id)"
              >
                <UserCheck class="w-3 h-3 mr-1" /> Take Over
              </Button>
              <Button
                v-else
                variant="ghost"
                size="sm"
                @click="release(conv.customer?.id)"
              >
                <Bot class="w-3 h-3 mr-1" /> Return to Bot
              </Button>
              <Link :href="`/conversations/${conv.id}`">
                <Button variant="ghost" size="sm">View</Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center gap-2 mt-6" v-if="conversations.last_page > 1">
      <Link v-for="link in conversations.links" :key="link.label" :href="link.url ?? '#'">
        <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" />
      </Link>
    </div>
  </TenantLayout>
</template>
