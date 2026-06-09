<script setup lang="ts">
import { Form, Head, Link, usePage } from "@inertiajs/vue3";
import { CheckCircle2, CircleSlash } from "lucide-vue-next";
import { computed } from "vue";
import ProfileController from "@/actions/App/Http/Controllers/Settings/ProfileController";
import DeleteUser from "@/components/DeleteUser.vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import { edit } from "@/routes/profile";
import { send } from "@/routes/verification";
import type { BreadcrumbItem } from "@/types";

type Props = {
  mustVerifyEmail: boolean;
  status?: string;
  googleSsoEnabled?: boolean;
  googleLinked?: boolean;
  googleProviderEmail?: string | null;
  googleLastLoginAt?: string | null;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: "Profile settings",
    href: edit(),
  },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const googleSsoEnabled = computed<boolean>(() =>
  Boolean(props.googleSsoEnabled),
);
const googleLinked = computed<boolean>(() => Boolean(props.googleLinked));
const googleProviderEmail = computed<string>(
  () => props.googleProviderEmail ?? "-",
);
const googleLastLoginAt = computed<string>(() => {
  if (!props.googleLastLoginAt) return "Belum pernah";
  try {
    return new Date(props.googleLastLoginAt).toLocaleString("id-ID", {
      dateStyle: "medium",
      timeStyle: "short",
    });
  } catch {
    return props.googleLastLoginAt;
  }
});

const startGoogleLink = (): void => {
  window.location.href = "/auth/google/link";
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile Settings</h1>

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <Heading
          variant="small"
          title="Profile information"
          description="Update your name and email address"
        />

        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <CardContent
            class="space-y-6 bg-gradient-to-br from-white to-zinc-50 py-6 dark:from-zinc-900 dark:to-zinc-950"
          >
            <Form
              v-bind="ProfileController.update.form()"
              class="space-y-6"
              v-slot="{ errors, processing, recentlySuccessful }"
            >
              <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                  id="name"
                  class="mt-1 block w-full"
                  name="name"
                  :default-value="user.name"
                  required
                  autocomplete="name"
                  placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
              </div>

              <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                  id="email"
                  type="email"
                  class="mt-1 block w-full"
                  name="email"
                  :default-value="user.email"
                  required
                  autocomplete="username"
                  placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
              </div>

              <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                  Your email address is unverified.
                  <Link
                    :href="send()"
                    as="button"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                  >
                    Click here to resend the verification email.
                  </Link>
                </p>

                <div
                  v-if="status === 'verification-link-sent'"
                  class="mt-2 text-sm font-medium text-green-600"
                >
                  A new verification link has been sent to your email address.
                </div>
              </div>

              <div class="flex items-center gap-4">
                <Button
                  :disabled="processing"
                  data-test="update-profile-button"
                >
                  Save
                </Button>

                <Transition
                  enter-active-class="transition ease-in-out"
                  enter-from-class="opacity-0"
                  leave-active-class="transition ease-in-out"
                  leave-to-class="opacity-0"
                >
                  <p
                    v-show="recentlySuccessful"
                    class="text-sm text-neutral-600"
                  >
                    Saved.
                  </p>
                </Transition>
              </div>
            </Form>
          </CardContent>
        </Card>

        <Heading
          variant="small"
          title="Akun Login"
          description="Metode login yang terhubung ke akun Anda"
        />

        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          data-test="settings-google-account-card"
        >
          <CardContent
            class="space-y-4 bg-gradient-to-br from-white to-zinc-50 py-6 dark:from-zinc-900 dark:to-zinc-950"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold">Google</p>
                <p class="text-xs text-muted-foreground">
                  {{
                    googleSsoEnabled
                      ? "Login dengan Google tersedia untuk akun Anda."
                      : "Login dengan Google belum diaktifkan oleh administrator."
                  }}
                </p>
              </div>
              <span
                v-if="googleLinked"
                class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700"
              >
                <CheckCircle2 class="h-3.5 w-3.5" />
                Terhubung
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
              >
                <CircleSlash class="h-3.5 w-3.5" />
                Belum terhubung
              </span>
            </div>

            <dl class="grid gap-2 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-xs text-muted-foreground">Email Google</dt>
                <dd class="font-medium">{{ googleProviderEmail }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Login terakhir</dt>
                <dd class="font-medium">{{ googleLastLoginAt }}</dd>
              </div>
            </dl>

            <p v-if="!googleSsoEnabled" class="text-xs text-muted-foreground">
              Aktifkan <code>GOOGLE_SSO_ENABLED=true</code> di environment untuk
              mengaktifkan login dengan Google.
            </p>

            <Button
              v-if="googleSsoEnabled && !googleLinked"
              type="button"
              variant="outline"
              class="w-fit"
              data-test="settings-link-google"
              @click="startGoogleLink"
            >
              Hubungkan Google
            </Button>
          </CardContent>
        </Card>
      </div>

      <DeleteUser />
    </SettingsLayout>
  </AppLayout>
</template>
