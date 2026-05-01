<script setup lang="ts">
import { 
  Plus, 
  Edit, 
  Trash2, 
  Eye, 
  Download, 
  Save, 
  X, 
  AlertCircle, 
  CheckCircle2, 
  Info,
  Loader2,
  Mail,
  ArrowRight
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogClose
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PageHeader from '@/components/ui/page-header/PageHeader.vue';
import StatusBadge from '@/components/ui/status-badge/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

// Mock Data for DataTable
const mockData = {
  data: [
    { id: 1, user: { name: 'John Doe' }, amount: 150000, status: 'APPROVED', date: '2024-03-01' },
    { id: 2, user: { name: 'Jane Smith' }, amount: 75000, status: 'PENDING', date: '2024-03-02' },
    { id: 3, user: { name: 'Bob Johnson' }, amount: 500000, status: 'REJECTED', date: '2024-02-28' },
    { id: 4, user: { name: 'Alice Brown' }, amount: 1200000, status: 'PAID', date: '2024-02-25' },
    { id: 5, user: { name: 'Charlie Wilson' }, amount: 25000, status: 'DRAFT', date: '2024-03-03' },
  ],
  links: [
    { url: null, label: '&laquo; Previous', active: false },
    { url: '#', label: '1', active: true },
    { url: '#', label: 'Next &raquo;', active: false },
  ],
  from: 1,
  to: 5,
  total: 5,
  last_page: 1,
  current_page: 1,
  path: '/settings/components',
  per_page: 10,
};

const columns = [
  { header: 'Date', key: 'date' },
  { header: 'User', key: 'user.name' },
  { header: 'Amount', key: 'amount', format: (val: number) => `Rp ${val.toLocaleString('id-ID')}`, align: 'right' as const },
  { header: 'Status', key: 'status', align: 'center' as const },
  { header: 'Actions', slot: 'actions', align: 'right' as const },
];

const handleRowClick = (row: any) => {
  console.log('Row clicked:', row);
};
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'Settings', href: '/settings' }, { title: 'UI Components', href: '#' }]">
    <SettingsLayout>
      <div class="space-y-8">
        <!-- Intro -->
        <div>
          <h2 class="text-2xl font-bold tracking-tight mb-2">UI Components</h2>
          <p class="text-zinc-500 dark:text-zinc-400">
            Reusable components for consistent application styling.
          </p>
        </div>

        <!-- Buttons Section -->
        <Card>
          <CardHeader>
            <CardTitle>Buttons</CardTitle>
            <CardDescription>Button variants, sizes, and states.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Variants -->
            <div class="space-y-2">
              <h3 class="text-sm font-medium text-zinc-500">Variants</h3>
              <div class="flex flex-wrap gap-2">
                <Button>Default</Button>
                <Button variant="secondary">Secondary</Button>
                <Button variant="destructive">Destructive</Button>
                <Button variant="outline">Outline</Button>
                <Button variant="ghost">Ghost</Button>
                <Button variant="link">Link</Button>
              </div>
            </div>

            <!-- Sizes -->
            <div class="space-y-2">
              <h3 class="text-sm font-medium text-zinc-500">Sizes</h3>
              <div class="flex flex-wrap items-center gap-2">
                <Button size="lg">Large</Button>
                <Button>Default</Button>
                <Button size="sm">Small</Button>
                <Button size="icon"><Plus class="h-4 w-4" /></Button>
              </div>
            </div>

            <!-- With Icons -->
            <div class="space-y-2">
              <h3 class="text-sm font-medium text-zinc-500">With Icons</h3>
              <div class="flex flex-wrap gap-2">
                <Button>
                  <Mail class="mr-2 h-4 w-4" /> Login with Email
                </Button>
                <Button variant="outline">
                  <Loader2 class="mr-2 h-4 w-4 animate-spin" /> Please wait
                </Button>
                <Button>
                  Next Step <ArrowRight class="ml-2 h-4 w-4" />
                </Button>
              </div>
            </div>

            <!-- Action Buttons (Icon Only) -->
            <div class="space-y-2">
              <h3 class="text-sm font-medium text-zinc-500">Action Buttons</h3>
              <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="icon" title="Edit">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" class="text-destructive hover:text-destructive border-destructive/50 hover:border-destructive hover:bg-destructive/10" title="Delete">
                  <Trash2 class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" title="View">
                  <Eye class="h-4 w-4" />
                </Button>
                <Button variant="secondary" size="icon" title="Download">
                  <Download class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Alerts Section -->
        <Card>
          <CardHeader>
            <CardTitle>Alerts</CardTitle>
            <CardDescription>Callout components for user attention.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Alert>
              <Info class="h-4 w-4" />
              <AlertTitle>Heads up!</AlertTitle>
              <AlertDescription>
                You can add components to your app using the cli.
              </AlertDescription>
            </Alert>
            
            <Alert variant="destructive">
              <AlertCircle class="h-4 w-4" />
              <AlertTitle>Error</AlertTitle>
              <AlertDescription>
                Your session has expired. Please log in again.
              </AlertDescription>
            </Alert>

            <!-- Success Style (Custom) -->
            <div class="relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start border-emerald-500/50 text-emerald-900 dark:text-emerald-400 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 [&>svg]:text-emerald-600 dark:[&>svg]:text-emerald-400">
              <CheckCircle2 class="h-4 w-4 translate-y-0.5" />
              <h5 class="font-medium leading-none tracking-tight">Success</h5>
              <div class="text-sm [&_p]:leading-relaxed">
                Your changes have been saved successfully.
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Modals Section -->
        <Card>
          <CardHeader>
            <CardTitle>Modals (Dialog)</CardTitle>
            <CardDescription>Overlay dialogs for confirming actions or forms.</CardDescription>
          </CardHeader>
          <CardContent>
            <Dialog>
              <DialogTrigger as-child>
                <Button variant="outline">Open Dialog</Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                  <DialogTitle>Edit Profile</DialogTitle>
                  <DialogDescription>
                    Make changes to your profile here. Click save when you're done.
                  </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                  <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="name" class="text-right">
                      Name
                    </Label>
                    <Input id="name" value="Pedro Duarte" class="col-span-3" />
                  </div>
                  <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="username" class="text-right">
                      Username
                    </Label>
                    <Input id="username" value="@peduarte" class="col-span-3" />
                  </div>
                </div>
                <DialogFooter>
                  <DialogClose as-child>
                    <Button type="submit">Save changes</Button>
                  </DialogClose>
                </DialogFooter>
              </DialogContent>
            </Dialog>
          </CardContent>
        </Card>

        <!-- Status Badges -->
        <Card>
          <CardHeader>
            <CardTitle>Status Badges</CardTitle>
            <CardDescription>Standardized status indicators for transactions and states.</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex flex-wrap gap-4">
              <StatusBadge status="APPROVED" />
              <StatusBadge status="PENDING" />
              <StatusBadge status="REJECTED" />
              <StatusBadge status="PAID" />
              <StatusBadge status="DRAFT" />
              <StatusBadge status="SUBMITTED" />
              <StatusBadge status="CANCELLED" />
              <StatusBadge status="CUSTOM" variant="secondary" />
            </div>
          </CardContent>
        </Card>

        <!-- Page Header -->
        <Card>
          <CardHeader>
            <CardTitle>Page Header</CardTitle>
            <CardDescription>Standard header with breadcrumbs and actions.</CardDescription>
          </CardHeader>
          <CardContent class="bg-zinc-50 dark:bg-zinc-900/50 p-6 rounded-lg border border-zinc-100 dark:border-zinc-800">
            <PageHeader 
              title="Example Page" 
              description="This is how a page header looks."
              :breadcrumbs="[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Settings', href: '/settings' },
                { title: 'Components' }
              ]"
            >
              <template #actions>
                <Button variant="outline">Export</Button>
                <Button>
                  <Plus class="h-4 w-4 mr-2" />
                  New Item
                </Button>
              </template>
            </PageHeader>
          </CardContent>
        </Card>

        <!-- Data Table -->
        <Card>
          <CardHeader>
            <CardTitle>Data Table</CardTitle>
            <CardDescription>Advanced table with search, pagination, and status support.</CardDescription>
          </CardHeader>
          <CardContent>
            <DataTable 
              :columns="columns" 
              :data="mockData" 
              search-placeholder="Search transactions..."
              @row-click="handleRowClick"
              row-clickable
            >
              <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                  <Button variant="ghost" size="icon" class="h-8 w-8 text-zinc-500 hover:text-indigo-600">
                    <Eye class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-8 w-8 text-zinc-500 hover:text-destructive">
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </template>
            </DataTable>
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
