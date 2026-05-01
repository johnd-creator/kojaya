<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import StatusBadge from '@/components/ui/status-badge/StatusBadge.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps<{ receipts: any[] }>()

const search = ref('')

const columns = [
  { header: 'GRN Number', key: 'grn_no', format: (v: string) => v || 'DRAFT', class: 'font-mono' },
  { header: 'Status', key: 'status', slot: 'status' },
  { header: 'PO Reference', key: 'purchase_order_id', class: 'text-xs text-zinc-500' }, // Ideally should be PO Number if available
  { header: 'Received Date', key: 'received_at', format: (v: string) => v ? new Date(v).toLocaleDateString() : '-', align: 'right' },
]

const filteredData = computed(() => {
  if (!search.value) return props.receipts
  const q = search.value.toLowerCase()
  return props.receipts.filter(grn => 
    (grn.grn_no || '').toLowerCase().includes(q) || 
    grn.status.toLowerCase().includes(q)
  )
})

function handleRowClick(row: any) {
  router.visit(`/procurement/grns/${row.id}`)
}
</script>

<template>
  <Head title="Goods Receive Notes" />
  
  <AppLayout>
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Goods Receive Notes</h1>
          <p class="text-zinc-500 mt-1">Log of all goods received against purchase orders.</p>
        </div>
      </div>

      <!-- Data Table -->
      <DataTable 
        :columns="columns" 
        :data="filteredData" 
        :searchable="true"
        search-placeholder="Search GRN number..."
        :row-clickable="true"
        @row-click="handleRowClick"
        @search="(q) => search = q"
      >
        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
