<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps<{ 
  glAccounts: string[]
  spareParts?: any[]
}>()

const form = useForm({
  title: '',
  cost_center: '',
  items: [{ 
    description: '', 
    spare_part_id: null as string | null, 
    gl_account: props.glAccounts?.[0] ?? '', 
    qty: 1, 
    price: 0 
  }],
})

const totalAmount = computed(() => {
  return form.items.reduce((sum, item) => sum + (item.qty * item.price), 0)
})

function addItem() {
  form.items.push({ 
    description: '', 
    spare_part_id: null,
    gl_account: props.glAccounts?.[0] ?? '', 
    qty: 1, 
    price: 0 
  })
}

function removeItem(i: number) {
  form.items.splice(i, 1)
}

function onSparePartChange(item: any) {
  if (item.spare_part_id) {
    const sp = props.spareParts?.find(s => s.id === item.spare_part_id)
    if (sp) {
      item.description = `${sp.name} (${sp.code})`
      // Optional: Set default price or GL account if available in master data
    }
  }
}

function submit() {
  form.post('/procurement/purchase-requests')
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)
}
</script>

<template>
  <Head title="Create Purchase Request" />
  <AppLayout>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Purchase Request</h1>
      </div>

      <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input v-model="form.title" class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-2 bg-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="e.g. Office Supplies Q1" />
            <div class="text-xs text-red-500 mt-1" v-if="form.errors.title">{{ form.errors.title }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Cost Center</label>
            <input v-model="form.cost_center" class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-2 bg-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="e.g. IT-001" />
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <h2 class="text-lg font-medium">Items</h2>
            <button type="button" class="text-sm px-3 py-1.5 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium transition-colors" @click="addItem">+ Add Item</button>
          </div>
          
          <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
              <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 font-medium border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                  <th class="px-4 py-3 w-[40%]">Item</th>
                  <th class="px-4 py-3 w-[20%]">GL Account</th>
                  <th class="px-4 py-3 w-[10%] text-right">Qty</th>
                  <th class="px-4 py-3 w-[15%] text-right">Price</th>
                  <th class="px-4 py-3 w-[15%] text-right">Total</th>
                  <th class="px-4 py-3 w-[5%]"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <tr v-for="(it, i) in form.items" :key="i" class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                  <td class="px-4 py-2 space-y-2">
                    <select 
                      v-if="props.spareParts && props.spareParts.length > 0"
                      v-model="it.spare_part_id" 
                      @change="onSparePartChange(it)"
                      class="w-full border-0 bg-transparent focus:ring-0 p-0 text-zinc-600 dark:text-zinc-300 text-sm mb-1"
                    >
                      <option :value="null">-- Custom Item --</option>
                      <option v-for="sp in props.spareParts" :key="sp.id" :value="sp.id">{{ sp.name }} ({{ sp.code }})</option>
                    </select>
                    <input v-model="it.description" placeholder="Description / Specification" class="w-full border-0 bg-transparent focus:ring-0 p-0 placeholder-zinc-400 text-sm" />
                  </td>
                  <td class="px-4 py-2 align-top">
                    <select v-model="it.gl_account" class="w-full border-0 bg-transparent focus:ring-0 p-0 text-zinc-600 dark:text-zinc-300">
                      <option v-for="gl in props.glAccounts" :key="gl" :value="gl">{{ gl }}</option>
                    </select>
                  </td>
                  <td class="px-4 py-2 align-top">
                    <input type="number" v-model.number="it.qty" class="w-full border-0 bg-transparent focus:ring-0 p-0 text-right" min="1" />
                  </td>
                  <td class="px-4 py-2 align-top">
                    <input type="number" v-model.number="it.price" class="w-full border-0 bg-transparent focus:ring-0 p-0 text-right" min="0" />
                  </td>
                  <td class="px-4 py-2 text-right font-medium text-zinc-700 dark:text-zinc-300 align-top">
                    {{ formatCurrency(it.qty * it.price) }}
                  </td>
                  <td class="px-4 py-2 text-center">
                    <button type="button" class="text-zinc-400 hover:text-red-600 transition-colors" @click="removeItem(i)" v-if="form.items.length > 1" title="Remove item">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-800 font-semibold">
                <tr>
                  <td colspan="4" class="px-4 py-3 text-right">Grand Total</td>
                  <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalAmount) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="text-xs text-red-500 mt-1" v-if="form.errors['items']">{{ form.errors['items'] }}</div>
        </div>

        <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-800">
          <button class="px-6 py-2.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" @click="submit" :disabled="form.processing">
            <span v-if="form.processing">Saving...</span>
            <span v-else>Create Purchase Request</span>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
