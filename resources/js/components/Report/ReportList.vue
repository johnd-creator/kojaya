<script setup lang="ts">
import { FileText, Download, Calendar, Users, Shield, Clock } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { availableReports  } from '@/components/Report/helpers'
import type {Report} from '@/components/Report/helpers';
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const emit = defineEmits<{
  select: [report: Report]
}>()

const selectedCategory = ref<string>('all')
const searchQuery = ref<string>('')

const categories = [
  { value: 'all', label: 'All Reports', icon: FileText },
  { value: 'payroll', label: 'Payroll', icon: Users },
  { value: 'attendance', label: 'Attendance', icon: Clock },
  { value: 'leave', label: 'Leave', icon: Calendar },
  { value: 'compliance', label: 'Compliance', icon: Shield }
]

const filteredReports = computed(() => {
  let reports = availableReports

  if (selectedCategory.value !== 'all') {
    reports = reports.filter(r => r.category === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    reports = reports.filter(r => 
      r.name.toLowerCase().includes(query) || 
      r.description.toLowerCase().includes(query)
    )
  }

  return reports
})

const getCategoryIcon = (category: string) => {
  const cat = categories.find(c => c.value === category)
  return cat?.icon || FileText
}

const getCategoryColor = (category: string): string => {
  const colors: Record<string, string> = {
    payroll: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    attendance: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    leave: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    compliance: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
  }
  return colors[category] || 'bg-gray-100 text-gray-800'
}

const selectReport = (report: Report) => {
  emit('select', report)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Available Reports</h2>
        <p class="text-muted-foreground">Select a report to generate</p>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-4">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="cat in categories"
          :key="cat.value"
          @click="selectedCategory = cat.value"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            selectedCategory === cat.value
              ? 'bg-primary text-primary-foreground'
              : 'bg-muted hover:bg-muted/80'
          ]"
        >
          <component :is="cat.icon" class="w-4 h-4 inline-block mr-2" />
          {{ cat.label }}
        </button>
      </div>

      <div class="relative flex-1 max-w-sm">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search reports..."
          class="w-full px-4 py-2 pl-10 rounded-lg border border-input bg-background"
        />
        <FileText class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card
        v-for="report in filteredReports"
        :key="report.id"
        class="cursor-pointer hover:shadow-md transition-shadow"
        @click="selectReport(report)"
      >
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <CardTitle class="text-lg">{{ report.name }}</CardTitle>
              <CardDescription class="mt-1">{{ report.description }}</CardDescription>
            </div>
            <component :is="getCategoryIcon(report.category)" class="w-5 h-5 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium', getCategoryColor(report.category)]">
                {{ report.category }}
              </span>
            </div>

            <div class="flex flex-wrap gap-1">
              <span
                v-for="format in report.formats"
                :key="format"
                class="px-2 py-0.5 rounded text-xs bg-secondary text-secondary-foreground"
              >
                {{ format.toUpperCase() }}
              </span>
            </div>

            <Button class="w-full" size="sm">
              <Download class="w-4 h-4 mr-2" />
              Generate
            </Button>
          </div>
        </CardContent>
      </Card>

      <div v-if="filteredReports.length === 0" class="col-span-full text-center py-12">
        <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
        <p class="text-muted-foreground">No reports found matching your criteria</p>
      </div>
    </div>
  </div>
</template>
