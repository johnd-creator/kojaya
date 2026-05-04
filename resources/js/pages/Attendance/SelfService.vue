<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { MapPin, Clock, Calendar, User, LogOut } from "lucide-vue-next";
import { ref, onMounted } from "vue";
import {
  attendancesIndex,
  selfService as attendancesSelfService,
} from "@/actions/App/Http/Controllers/AttendanceController";
import AppLayout from "@/layouts/AppLayout.vue";
import { logout } from "@/routes";
import { selfService as leavesSelfService } from "@/routes/leaves";
import { index as payrollsIndex } from "@/routes/payrolls";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Attendance", href: "/attendances" },
  { title: "Self Service", href: "/attendances/self-service" },
];

const user = ref<any>(null);
const todayAttendance = ref<any>(null);
const thisWeekAttendance = ref<any[]>([]);
const loading = ref(false);

onMounted(() => {
  loadAttendanceData();
});

const loadAttendanceData = async () => {
  loading.value = true;
  try {
    // Simulate API call - in production this would be actual API
    setTimeout(() => {
      user.value = {
        name: "John Doe",
        nik: "EMP001",
        position: "Teknisi",
        unit: "PLTMG Pantai Barat",
      };

      todayAttendance.value = {
        date: new Date().toISOString().split("T")[0],
        checkIn: "07:55",
        checkOut: null,
        status: "checked_in",
        location: "Site Office",
      };

      thisWeekAttendance.value = [
        {
          date: "2026-03-01",
          checkIn: "07:58",
          checkOut: "17:02",
          status: "present",
        },
        {
          date: "2026-03-02",
          checkIn: "08:05",
          checkOut: "17:00",
          status: "present",
        },
        {
          date: "2026-03-03",
          checkIn: "07:45",
          checkOut: "17:15",
          status: "present",
        },
        { date: "2026-03-04", checkIn: null, checkOut: null, status: "absent" },
        {
          date: "2026-03-05",
          checkIn: "07:50",
          checkOut: null,
          status: "present",
        },
      ];

      loading.value = false;
    }, 300);
  } catch (error) {
    console.error("Error loading attendance:", error);
    loading.value = false;
  }
};

const handleCheckIn = async () => {
  // In production, this would use geolocation API
  if (!navigator.geolocation) {
    alert("Browser tidak mendukung geolocation");
    return;
  }

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords;
      console.log("GPS:", latitude, longitude);

      // Simulate check-in API call
      todayAttendance.value = {
        ...todayAttendance.value,
        checkIn: new Date().toLocaleTimeString("id-ID", {
          hour: "2-digit",
          minute: "2-digit",
        }),
        status: "checked_in",
        location: `GPS: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`,
      };
    },
    (error) => {
      alert("Gagal mendapatkan lokasi: " + error.message);
    },
  );
};

const handleCheckOut = async () => {
  // In production, this would call the checkout API
  todayAttendance.value = {
    ...todayAttendance.value,
    checkOut: new Date().toLocaleTimeString("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
    }),
    status: "completed",
  };
};

const getStatusBadge = (status: string) => {
  const badges = {
    present:
      "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400",
    absent: "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400",
    checked_in:
      "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400",
    completed:
      "bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400",
  };
  return badges[status as keyof typeof badges] || badges.present;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString("id-ID", {
    weekday: "short",
    day: "numeric",
    month: "short",
  });
};
</script>

<template>
  <Head title="Absensi Saya" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex flex-col lg:flex-row gap-6 p-4 lg:p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Mobile-Optimized Card -->
      <div class="flex-1 space-y-4 lg:space-y-6">
        <!-- Header Section -->
        <div
          class="bg-gradient-to-br from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 rounded-2xl p-6 text-white shadow-lg"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-blue-100 text-sm">Selamat datang,</p>
              <h1 class="text-2xl lg:text-3xl font-bold mt-1">
                {{ user?.name }}
              </h1>
              <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <span class="bg-white/20 px-3 py-1 rounded-full">{{
                  user?.nik
                }}</span>
                <span class="bg-white/20 px-3 py-1 rounded-full">{{
                  user?.position
                }}</span>
              </div>
            </div>
            <Link
              :href="logout.url()"
              method="post"
              as="button"
              class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
            >
              <LogOut class="w-5 h-5" />
            </Link>
          </div>
        </div>

        <!-- Today's Status Card -->
        <div
          class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden"
        >
          <div class="p-4 lg:p-6 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-lg font-semibold flex items-center gap-2">
              <Calendar class="w-5 h-5 text-blue-600" />
              Absensi Hari Ini
            </h2>
            <p class="text-sm text-zinc-500 mt-1">
              {{
                new Date().toLocaleDateString("id-ID", {
                  weekday: "long",
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                })
              }}
            </p>
          </div>

          <div class="p-4 lg:p-6">
            <div v-if="!todayAttendance?.checkIn" class="text-center py-8">
              <MapPin class="w-16 h-16 mx-auto text-zinc-400 mb-4" />
              <p class="text-zinc-500 mb-4">Anda belum check-in hari ini</p>
              <button
                @click="handleCheckIn"
                class="w-full max-w-xs mx-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
              >
                <MapPin class="w-5 h-5" />
                Check-In Sekarang
              </button>
            </div>

            <div v-else class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                  <p class="text-sm text-zinc-500 mb-1">Check-In</p>
                  <p class="text-xl font-bold text-green-600">
                    {{ todayAttendance.checkIn }}
                  </p>
                </div>
                <div
                  v-if="todayAttendance.checkOut"
                  class="bg-gray-50 dark:bg-gray-900/20 rounded-xl p-4"
                >
                  <p class="text-sm text-zinc-500 mb-1">Check-Out</p>
                  <p class="text-xl font-bold text-gray-600">
                    {{ todayAttendance.checkOut }}
                  </p>
                </div>
                <div
                  v-else
                  class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4"
                >
                  <p class="text-sm text-zinc-500 mb-1">Status</p>
                  <p class="text-xl font-bold text-blue-600">Aktif</p>
                </div>
              </div>

              <div
                v-if="todayAttendance.location"
                class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3 flex items-center gap-2 text-sm"
              >
                <MapPin class="w-4 h-4 text-zinc-500" />
                <span class="text-zinc-600 dark:text-zinc-400">{{
                  todayAttendance.location
                }}</span>
              </div>

              <button
                v-if="!todayAttendance.checkOut"
                @click="handleCheckOut"
                class="w-full bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
              >
                <Clock class="w-5 h-5" />
                Check-Out
              </button>
            </div>
          </div>
        </div>

        <!-- This Week Attendance -->
        <div
          class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden"
        >
          <div class="p-4 lg:p-6 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-lg font-semibold flex items-center gap-2">
              <Clock class="w-5 h-5 text-purple-600" />
              Riwayat Minggu Ini
            </h2>
          </div>

          <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            <div
              v-for="attendance in thisWeekAttendance"
              :key="attendance.date"
              class="p-4 lg:p-6 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
            >
              <div class="flex-1">
                <p class="font-medium">{{ formatDate(attendance.date) }}</p>
                <div class="flex items-center gap-4 mt-1 text-sm text-zinc-500">
                  <span
                    v-if="attendance.checkIn"
                    class="flex items-center gap-1"
                  >
                    <Clock class="w-3 h-3" />
                    In: {{ attendance.checkIn }}
                  </span>
                  <span
                    v-if="attendance.checkOut"
                    class="flex items-center gap-1"
                  >
                    <Clock class="w-3 h-3" />
                    Out: {{ attendance.checkOut }}
                  </span>
                  <span v-else class="text-zinc-400">Belum check-out</span>
                </div>
              </div>
              <span
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium capitalize',
                  getStatusBadge(attendance.status),
                ]"
              >
                {{ attendance.status.replace("_", " ") }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-4">
          <Link
            :href="leavesSelfService.url()"
            class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-4 lg:p-6 hover:shadow-md transition-shadow"
          >
            <Calendar class="w-8 h-8 text-purple-600 mb-2" />
            <h3 class="font-semibold">Pengajuan Cuti</h3>
            <p class="text-sm text-zinc-500 mt-1">Ajukan cuti & lihat status</p>
          </Link>

          <Link
            :href="payrollsIndex.url()"
            class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-4 lg:p-6 hover:shadow-md transition-shadow"
          >
            <User class="w-8 h-8 text-green-600 mb-2" />
            <h3 class="font-semibold">Slip Gaji</h3>
            <p class="text-sm text-zinc-500 mt-1">Lihat riwayat payroll</p>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
