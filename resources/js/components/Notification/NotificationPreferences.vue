<script setup lang="ts">
import {
  Loader2,
  Save,
  Mail,
  Database,
  Bell as BellIcon,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import {
  fetchNotificationPreferences as getPreferences,
  updateNotificationPreferences as updatePreferences,
} from "@/api/notifications";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import type { NotificationPreference } from "@/types";

const preferences = ref<NotificationPreference>({
  email_enabled: true,
  database_enabled: true,
  push_enabled: false,
  channels: ["mail", "database"],
});

const loading = ref(false);
const saving = ref(false);
const saveSuccess = ref(false);
const saveError = ref(false);

const availableChannels = [
  { value: "mail", label: "Email", icon: Mail },
  { value: "database", label: "In-App", icon: Database },
  { value: "broadcast", label: "Push Notification", icon: BellIcon },
];

const fetchPreferencesData = async () => {
  loading.value = true;
  try {
    const response = await getPreferences();
    preferences.value = response;
  } catch (error) {
    console.error("Failed to fetch preferences:", error);
    saveError.value = true;
    setTimeout(() => {
      saveError.value = false;
    }, 3000);
  } finally {
    loading.value = false;
  }
};

const handleSave = async () => {
  saving.value = true;
  saveSuccess.value = false;
  saveError.value = false;

  try {
    await updatePreferences(preferences.value);
    saveSuccess.value = true;
    setTimeout(() => {
      saveSuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error("Failed to save preferences:", error);
    saveError.value = true;
    setTimeout(() => {
      saveError.value = false;
    }, 3000);
  } finally {
    saving.value = false;
  }
};

const toggleChannel = (channel: string) => {
  const index = preferences.value.channels.indexOf(channel);
  if (index > -1) {
    preferences.value.channels.splice(index, 1);
  } else {
    preferences.value.channels.push(channel);
  }
};

const isChannelEnabled = (channel: string) => {
  return preferences.value.channels.includes(channel);
};

onMounted(() => {
  fetchPreferencesData();
});
</script>

<template>
  <div class="space-y-6">
    <div v-if="loading" class="flex items-center justify-center py-12">
      <Loader2 class="h-8 w-8 animate-spin text-neutral-400" />
    </div>

    <div v-else class="space-y-6">
      <div
        class="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <h3
          class="mb-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          Notification Channels
        </h3>
        <p class="mb-6 text-sm text-neutral-600 dark:text-neutral-400">
          Select which channels you want to receive notifications through.
        </p>

        <div class="space-y-4">
          <div
            v-for="channel in availableChannels"
            :key="channel.value"
            class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
          >
            <div class="flex items-center gap-3">
              <component
                :is="channel.icon"
                class="h-5 w-5 text-neutral-600 dark:text-neutral-400"
              />
              <div>
                <Label
                  :for="`channel-${channel.value}`"
                  class="cursor-pointer font-medium"
                >
                  {{ channel.label }}
                </Label>
                <p class="text-xs text-neutral-500 dark:text-neutral-500">
                  {{
                    channel.value === "mail"
                      ? "Receive notifications via email"
                      : channel.value === "database"
                        ? "Receive in-app notifications"
                        : "Receive push notifications"
                  }}
                </p>
              </div>
            </div>
            <Switch
              :id="`channel-${channel.value}`"
              :checked="isChannelEnabled(channel.value)"
              @update:checked="toggleChannel(channel.value)"
            />
          </div>
        </div>
      </div>

      <div
        class="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <h3
          class="mb-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          Notification Preferences
        </h3>
        <p class="mb-6 text-sm text-neutral-600 dark:text-neutral-400">
          Manage your notification settings.
        </p>

        <div class="space-y-4">
          <div
            class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
          >
            <div class="flex items-center gap-3">
              <Mail class="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
              <div>
                <Label for="email-enabled" class="cursor-pointer font-medium">
                  Email Notifications
                </Label>
                <p class="text-xs text-neutral-500 dark:text-neutral-500">
                  Enable or disable email notifications
                </p>
              </div>
            </div>
            <Switch
              id="email-enabled"
              v-model:checked="preferences.email_enabled"
            />
          </div>

          <div
            class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
          >
            <div class="flex items-center gap-3">
              <Database
                class="h-5 w-5 text-neutral-600 dark:text-neutral-400"
              />
              <div>
                <Label
                  for="database-enabled"
                  class="cursor-pointer font-medium"
                >
                  In-App Notifications
                </Label>
                <p class="text-xs text-neutral-500 dark:text-neutral-500">
                  Enable or disable in-app notifications
                </p>
              </div>
            </div>
            <Switch
              id="database-enabled"
              v-model:checked="preferences.database_enabled"
            />
          </div>

          <div
            class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
          >
            <div class="flex items-center gap-3">
              <BellIcon
                class="h-5 w-5 text-neutral-600 dark:text-neutral-400"
              />
              <div>
                <Label for="push-enabled" class="cursor-pointer font-medium">
                  Push Notifications
                </Label>
                <p class="text-xs text-neutral-500 dark:text-neutral-500">
                  Enable or disable push notifications
                </p>
              </div>
            </div>
            <Switch
              id="push-enabled"
              v-model:checked="preferences.push_enabled"
            />
          </div>
        </div>
      </div>

      <div
        class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <div>
          <p
            v-if="saveSuccess"
            class="text-sm font-medium text-green-600 dark:text-green-400"
          >
            Preferences saved successfully!
          </p>
          <p
            v-else-if="saveError"
            class="text-sm font-medium text-red-600 dark:text-red-400"
          >
            Failed to save preferences. Please try again.
          </p>
        </div>

        <Button :disabled="saving" @click="handleSave">
          <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
          <Save v-else class="mr-2 h-4 w-4" />
          {{ saving ? "Saving..." : "Save Preferences" }}
        </Button>
      </div>
    </div>
  </div>
</template>
