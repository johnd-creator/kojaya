<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Calendar, Plus, Clock, CheckCircle2, XCircle, FileText, ChevronRight } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
  leaves: any
  leaveTypes: any[]
  employee: any
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Employee Self Service', href: '#' },
  { title: 'My Leaves', href: '#' },
]

const showModal = ref(false)

const form = useForm({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
  attachment: null as File | null,
})

const openModal = () => {
  form.reset()
  form.clearErrors()
  showModal.value = true
}

const submit = () => {
  form.post('/leaves/self-service', {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
    },
  })
}

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.attachment = target.files[0]
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Approved': return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30 border-green-200 dark:border-green-800'
    case 'Rejected': return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/30 border-red-200 dark:border-red-800'
    default: return 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'Approved': return CheckCircle2
    case 'Rejected': return XCircle
    default: return Clock
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const calculateDays = (start: string, end: string) => {
  const startDate = new Date(start)
  const endDate = new Date(end)
  const diffTime = Math.abs(endDate.getTime() - startDate.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1
}
</script>

<template>
  <Head title="Pengajuan Cuti" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col lg:flex-row gap-6 p-4 lg:p-6 max-w-7xl mx-auto w-full">
      
      <!-- Mobile Header -->
      <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl lg:text-3xl font-bold flex items-center gap-2">
              <Calendar class="h-8 w-8 text-purple-600" />
              Pengajuan Cuti
            </h1>
            <p class="text-zinc-500 mt-1">Kelola cuti dan lihat status persetujuan</p>
          </div>
        </div>

        <!-- FAB for Mobile -->
        <button
          @click="openModal"
          class="lg:hidden fixed bottom-6 right-6 z-50 bg-purple-600 hover:bg-purple-700 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center"
        >
          <Plus class="w-6 h-6" />
        </button>

        <!-- Desktop Button -->
        <Button
          @click="openModal"
          class="hidden lg:flex w-auto"
        >
          <Plus class="w-4 h-4 mr-2" />
          Ajukan Cuti
        </Button>
      </div>

      <!-- Leave Balance Card (Mobile) -->
      <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-900 dark:to-purple-800 rounded-2xl p-6 text-white shadow-lg lg:hidden">
        <h2 class="text-lg font-semibold mb-4">Sisa Cuti Tahun Ini</h2>
        <div class="flex items-end justify-between">
          <div>
            <p class="text-4xl font-bold">12</p>
            <p class="text-purple-100 text-sm">hari tersisa</p>
          </div>
          <div class="text-right">
            <p class="text-purple-100 text-sm">Total kuota</p>
            <p class="text-2xl font-semibold">12 hari</p>
          </div>
        </div>
      </div>

      <!-- Leave Requests List -->
      <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="p-4 lg:p-6 border-b border-zinc-200 dark:border-zinc-800">
          <h2 class="text-lg font-semibold">Riwayat Pengajuan</h2>
        </div>

        <div v-if="props.leaves?.data?.length === 0" class="p-8 text-center text-zinc-500">
          <FileText class="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>Belum ada pengajuan cuti</p>
        </div>

        <div v-else class="divide-y divide-zinc-200 dark:divide-zinc-800">
          <div
            v-for="leave in props.leaves?.data"
            :key="leave.id"
            class="p-4 lg:p-6 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <h3 class="font-semibold truncate">{{ leave.leave_type?.name }}</h3>
                  <span
                    :class="['px-2 py-1 rounded-full text-xs font-medium border flex-shrink-0', getStatusColor(leave.status)]"
                  >
                    {{ leave.status }}
                  </span>
                </div>
                
                <div class="space-y-1 text-sm text-zinc-500">
                  <p class="flex items-center gap-1">
                    <Calendar class="w-3 h-3" />
                    {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                  </p>
                  <p class="flex items-center gap-1">
                    <Clock class="w-3 h-3" />
                    {{ calculateDays(leave.start_date, leave.end_date) }} hari
                  </p>
                </div>

                <div v-if="leave.reason" class="mt-2 text-sm">
                  <p class="text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ leave.reason }}</p>
                </div>
              </div>

              <div class="flex-shrink-0">
                <component :is="getStatusIcon(leave.status)" class="w-5 h-5" :class="getStatusColor(leave.status).split(' ')[0]" />
              </div>
            </div>

            <div v-if="leave.approver" class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-800 text-sm text-zinc-500">
              <p>Disetujui oleh: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ leave.approver?.name }}</span></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop Leave Balance -->
      <div class="hidden lg:block bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-lg font-semibold mb-4">Sisa Cuti Tahun Ini</h2>
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
            <p class="text-3xl font-bold text-purple-600">12</p>
            <p class="text-sm text-zinc-500 mt-1">Tersisa</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/20 rounded-xl">
            <p class="text-3xl font-bold text-gray-600">5</p>
            <p class="text-sm text-zinc-500 mt-1">Digunakan</p>
          </div>
          <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
            <p class="text-3xl font-bold text-green-600">17</p>
            <p class="text-sm text-zinc-500 mt-1">Total Kuota</p>
          </div>
        </div>
      </div>

      <!-- New Leave Request Modal -->
      <Dialog v-model:open="showModal">
        <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Ajukan Cuti Baru</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
              <Label for="leave_type">Tipe Cuti</Label>
              <select
                id="leave_type"
                v-model="form.leave_type_id"
                class="flex h-10 w-full rounded-md border border-zinc-300 bg-transparent px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950"
                required
              >
                <option value="">Pilih tipe cuti</option>
                <option v-for="type in props.leaveTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
              <p v-if="form.errors.leave_type_id" class="text-sm text-red-600">{{ form.errors.leave_type_id }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="start_date">Tanggal Mulai</Label>
                <Input
                  id="start_date"
                  v-model="form.start_date"
                  type="date"
                  required
                />
                <p v-if="form.errors.start_date" class="text-sm text-red-600">{{ form.errors.start_date }}</p>
              </div>

              <div class="space-y-2">
                <Label for="end_date">Tanggal Selesai</Label>
                <Input
                  id="end_date"
                  v-model="form.end_date"
                  type="date"
                  required
                />
                <p v-if="form.errors.end_date" class="text-sm text-red-600">{{ form.errors.end_date }}</p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="reason">Alasan</Label>
              <textarea
                id="reason"
                v-model="form.reason"
                rows="3"
                class="flex min-h-[80px] w-full rounded-md border border-zinc-300 bg-transparent px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950"
                placeholder="Jelaskan alasan cuti Anda"
                required
              ></textarea>
              <p v-if="form.errors.reason" class="text-sm text-red-600">{{ form.errors.reason }}</p>
            </div>

            <div class="space-y-2">
              <Label for="attachment">Lampiran (Opsional)</Label>
              <Input
                id="attachment"
                type="file"
                @change="handleFileChange"
                accept=".pdf,.jpg,.jpeg,.png"
              />
              <p class="text-xs text-zinc-500">Format: PDF, JPG, PNG (Maks 5MB)</p>
              <p v-if="form.errors.attachment" class="text-sm text-red-600">{{ form.errors.attachment }}</p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <Button
                type="button"
                variant="outline"
                @click="showModal = false"
              >
                Batal
              </Button>
              <Button
                type="submit"
                :disabled="form.processing"
              >
                <Plus v-if="!form.processing" class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Mengajukan...' : 'Ajukan' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

    </div>
  </AppLayout>
</template>
