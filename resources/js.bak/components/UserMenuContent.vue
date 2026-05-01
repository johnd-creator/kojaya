<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Settings, Building, Check } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubTrigger,
    DropdownMenuSubContent,
    DropdownMenuPortal,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

const page = usePage();
const activeOrg = computed(() => page.props.active_organization as Record<string, any> | null);
const userOrgs = computed(() => (page.props.user_organizations || []) as Record<string, any>[]);

const switchOrganization = (orgId: string | null) => {
    router.post('/switch-organization', { organization_id: orgId }, { preserveScroll: true });
};

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuGroup v-if="userOrgs.length > 0">
        <DropdownMenuSub>
            <DropdownMenuSubTrigger class="w-full cursor-pointer">
                <Building class="mr-2 h-4 w-4" />
                <span>{{ activeOrg ? activeOrg.name : 'Switch Branch' }}</span>
            </DropdownMenuSubTrigger>
            <DropdownMenuPortal>
                <DropdownMenuSubContent class="w-56">
                    <DropdownMenuItem 
                        class="cursor-pointer" 
                        @click="switchOrganization(null)"
                    >
                        <span>All Branches (Consolidated)</span>
                        <Check v-if="!activeOrg" class="ml-auto h-4 w-4" />
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem 
                        v-for="org in userOrgs" 
                        :key="org.id" 
                        class="cursor-pointer" 
                        @click="switchOrganization(org.id)"
                    >
                        <span>{{ org.name }}</span>
                        <Check v-if="activeOrg && activeOrg.id === org.id" class="ml-auto h-4 w-4" />
                    </DropdownMenuItem>
                </DropdownMenuSubContent>
            </DropdownMenuPortal>
        </DropdownMenuSub>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
