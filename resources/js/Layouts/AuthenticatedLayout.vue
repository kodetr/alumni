<script setup>
import { computed, onMounted, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

const successMessage = computed(() => page.props.flash?.success);
const isSuperAdmin = computed(() => ['superadmin', 'admin'].includes(page.props.auth?.user?.role));
const isAdmin = computed(() => ['superadmin', 'admin'].includes(page.props.auth?.user?.role));
const roleLabel = computed(() => (isAdmin.value ? 'Superadmin' : 'Alumni'));
const isSocialMenuActive = computed(() => page.url?.startsWith('/jejaring-sosial'));
const isCareerMenuActive = computed(() => page.url?.startsWith('/karier'));
const isEventMenuActive = computed(() => page.url?.startsWith('/event-alumni'));
const isMappingMenuActive = computed(() => page.url?.startsWith('/mapping'));
const isDonationMenuActive = computed(() => page.url?.startsWith('/donasi'));
const isBusinessMenuActive = computed(() => page.url?.startsWith('/bisnis'));

const notificationPayload = computed(() => page.props.notifications ?? {
    items: [],
    unreadCount: 0,
    eventCount: 0,
    reminderCount: 0,
});
const notificationItems = computed(() => notificationPayload.value.items ?? []);
const unreadNotificationCount = computed(() => notificationPayload.value.unreadCount ?? 0);
const eventNotificationCount = computed(() => notificationPayload.value.eventCount ?? 0);
const reminderNotificationCount = computed(() => notificationPayload.value.reminderCount ?? 0);
const eventNotificationItems = computed(() => notificationItems.value.filter((item) => item.type === 'event'));
const reminderNotificationItems = computed(() => notificationItems.value.filter((item) => item.type === 'reminder'));

const pushPermission = ref('default');
const pushEnabled = ref(false);

const pushSupported = computed(() => typeof window !== 'undefined' && 'Notification' in window);
const pushStatusText = computed(() => {
    if (!pushSupported.value) {
        return 'Browser tidak mendukung push notification.';
    }

    if (pushPermission.value === 'granted') {
        return pushEnabled.value ? 'Push notification aktif.' : 'Izin aktif, notifikasi belum diaktifkan.';
    }

    if (pushPermission.value === 'denied') {
        return 'Push notification diblokir. Aktifkan dari pengaturan browser.';
    }

    return 'Push notification belum diaktifkan.';
});

const requestPushNotification = async () => {
    if (!pushSupported.value) {
        return;
    }

    const permission = await Notification.requestPermission();
    pushPermission.value = permission;

    if (permission !== 'granted') {
        pushEnabled.value = false;
        localStorage.removeItem('alumni_push_notifications_enabled');

        return;
    }

    pushEnabled.value = true;
    localStorage.setItem('alumni_push_notifications_enabled', '1');

    new Notification('Push notification aktif', {
        body: 'Anda akan menerima notifikasi event dan reminder update data.',
    });
};

const sendPushPreview = () => {
    if (!pushSupported.value || pushPermission.value !== 'granted' || !pushEnabled.value) {
        return;
    }

    new Notification('Pengingat Alumni', {
        body: 'Jangan lupa cek event terbaru dan update data profil Anda.',
    });
};

onMounted(() => {
    if (!pushSupported.value) {
        return;
    }

    pushPermission.value = Notification.permission;
    pushEnabled.value = pushPermission.value === 'granted'
        && localStorage.getItem('alumni_push_notifications_enabled') === '1';
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    v-if="isSuperAdmin"
                                    :href="route('admin.users.index')"
                                    :active="route().current('admin.users.*')"
                                >
                                    Users
                                </NavLink>
                                <NavLink
                                    v-if="isAdmin"
                                    :href="route('alumni.index')"
                                    :active="route().current('alumni.*')"
                                >
                                    Alumni
                                </NavLink>
                                <NavLink
                                    v-if="isAdmin"
                                    :href="route('berita.index')"
                                    :active="route().current('berita.*')"
                                >
                                    Berita
                                </NavLink>
                                <NavLink
                                    v-if="isAdmin"
                                    :href="route('agenda.index')"
                                    :active="route().current('agenda.*')"
                                >
                                    Agenda
                                </NavLink>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isEventMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Event
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('eventmenu.reunion')">
                                            Reuni
                                        </DropdownLink>
                                        <DropdownLink :href="route('eventmenu.webinar')">
                                            Webinar / Seminar
                                        </DropdownLink>
                                        <DropdownLink :href="route('eventmenu.networking')">
                                            Networking
                                        </DropdownLink>
                                        <DropdownLink :href="route('eventmenu.rsvp')">
                                            RSVP / Pendaftaran
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isMappingMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Mapping
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('mapping.locations')">
                                            Lokasi Alumni
                                        </DropdownLink>
                                        <DropdownLink :href="route('mapping.global')">
                                            Sebaran Global
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isDonationMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Donasi
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('donation.online')">
                                            Donasi Online
                                        </DropdownLink>
                                        <DropdownLink :href="route('donation.scholarship')">
                                            Program Beasiswa
                                        </DropdownLink>
                                        <DropdownLink :href="route('donation.crowdfunding')">
                                            Crowdfunding
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isBusinessMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Bisnis
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('business.marketplace')">
                                            Marketplace
                                        </DropdownLink>
                                        <DropdownLink :href="route('business.partnership')">
                                            Kerjasama
                                        </DropdownLink>
                                        <DropdownLink :href="route('business.mentorship')">
                                            Mentorship
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isSocialMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Forum
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('social.forum')">
                                            Diskusi
                                        </DropdownLink>
                                        <DropdownLink :href="route('social.chat')">
                                            Chat
                                        </DropdownLink>
                                        <DropdownLink :href="route('social.groups')">
                                            Grup
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown class="flex items-center" align="left" width="48" content-classes="py-1 bg-white min-w-[17rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isCareerMenuActive
                                                ? 'border-indigo-400 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        >
                                            Karier
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('career.jobs')">
                                            Posting Loker
                                        </DropdownLink>
                                        <DropdownLink :href="route('career.center')">
                                            Career Center
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative">
                                <Dropdown align="right" width="48" content-classes="py-2 bg-white min-w-[22rem]">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 focus:outline-none"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <span
                                                v-if="unreadNotificationCount > 0"
                                                class="absolute -right-1 -top-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
                                            >
                                                {{ unreadNotificationCount > 9 ? '9+' : unreadNotificationCount }}
                                            </span>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2">
                                            <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
                                            <p class="mt-0.5 text-xs text-gray-500">Event, reminder update data, dan push notification.</p>
                                        </div>

                                        <div class="max-h-72 overflow-y-auto border-t border-gray-100 px-2 py-2">
                                            <div class="mb-2 rounded-md bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
                                                Event: {{ eventNotificationCount }} • Reminder: {{ reminderNotificationCount }}
                                            </div>

                                            <div v-if="notificationItems.length === 0" class="rounded-md px-3 py-2 text-sm text-gray-500">
                                                Belum ada notifikasi baru.
                                            </div>

                                            <div v-if="eventNotificationItems.length > 0" class="mb-1">
                                                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Event</p>
                                                <a
                                                    v-for="item in eventNotificationItems"
                                                    :key="item.id"
                                                    :href="item.url"
                                                    class="mb-1 block rounded-md px-3 py-2 transition hover:bg-gray-50"
                                                >
                                                    <p class="text-sm font-medium text-gray-800">{{ item.title }}</p>
                                                    <p class="text-xs text-gray-500">{{ item.message }}</p>
                                                </a>
                                            </div>

                                            <div v-if="reminderNotificationItems.length > 0">
                                                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Reminder Update Data</p>
                                                <a
                                                    v-for="item in reminderNotificationItems"
                                                    :key="item.id"
                                                    :href="item.url"
                                                    class="mb-1 block rounded-md px-3 py-2 transition hover:bg-gray-50"
                                                >
                                                    <p class="text-sm font-medium text-gray-800">{{ item.title }}</p>
                                                    <p class="text-xs text-gray-500">{{ item.message }}</p>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="border-t border-gray-100 px-4 py-3">
                                            <p class="text-sm font-medium text-gray-800">Push notification</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ pushStatusText }}</p>
                                            <div class="mt-2 flex gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-500"
                                                    @click.stop="requestPushNotification"
                                                >
                                                    Aktifkan Push
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                                    :disabled="!pushEnabled"
                                                    @click.stop="sendPushPreview"
                                                >
                                                    Uji Notifikasi
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}
                                                <span
                                                    class="ms-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-600"
                                                >
                                                    {{ roleLabel }}
                                                </span>

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                     <template #content>
                                         <DropdownLink
                                             :href="route('profile.edit')"
                                         >
                                             Profile
                                         </DropdownLink>
                                         <DropdownLink
                                             v-if="isAdmin"
                                             :href="route('settings.integration.index')"
                                         >
                                             Pengaturan
                                         </DropdownLink>
                                         <DropdownLink
                                             :href="route('logout')"
                                             method="post"
                                             as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isAdmin"
                            :href="route('alumni.index')"
                            :active="route().current('alumni.*')"
                        >
                            Alumni
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isAdmin"
                            :href="route('berita.index')"
                            :active="route().current('berita.*')"
                        >
                            Berita
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isAdmin"
                            :href="route('agenda.index')"
                            :active="route().current('agenda.*')"
                        >
                            Agenda
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Event
                        </div>
                        <ResponsiveNavLink
                            :href="route('eventmenu.reunion')"
                            :active="route().current('eventmenu.reunion')"
                        >
                            Reuni
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('eventmenu.webinar')"
                            :active="route().current('eventmenu.webinar')"
                        >
                            Webinar / Seminar
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('eventmenu.networking')"
                            :active="route().current('eventmenu.networking')"
                        >
                            Networking
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('eventmenu.rsvp')"
                            :active="route().current('eventmenu.rsvp')"
                        >
                            RSVP / Pendaftaran
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Mapping
                        </div>
                        <ResponsiveNavLink
                            :href="route('mapping.locations')"
                            :active="route().current('mapping.locations')"
                        >
                            Lokasi Alumni
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('mapping.global')"
                            :active="route().current('mapping.global')"
                        >
                            Sebaran Global
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Donasi
                        </div>
                        <ResponsiveNavLink
                            :href="route('donation.online')"
                            :active="route().current('donation.online')"
                        >
                            Donasi Online
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('donation.scholarship')"
                            :active="route().current('donation.scholarship')"
                        >
                            Program Beasiswa
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('donation.crowdfunding')"
                            :active="route().current('donation.crowdfunding')"
                        >
                            Crowdfunding
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Bisnis
                        </div>
                        <ResponsiveNavLink
                            :href="route('business.marketplace')"
                            :active="route().current('business.marketplace')"
                        >
                            Marketplace
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('business.partnership')"
                            :active="route().current('business.partnership')"
                        >
                            Kerjasama
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('business.mentorship')"
                            :active="route().current('business.mentorship')"
                        >
                            Mentorship
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Jejaring Sosial
                        </div>
                        <ResponsiveNavLink
                            :href="route('social.forum')"
                            :active="route().current('social.forum')"
                        >
                            Forum diskusi / komunitas
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('social.chat')"
                            :active="route().current('social.chat')"
                        >
                            Chat antar alumni
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('social.groups')"
                            :active="route().current('social.groups')"
                        >
                            Grup berdasarkan angkatan/jurusan
                        </ResponsiveNavLink>
                        <div class="px-4 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Karier
                        </div>
                        <ResponsiveNavLink
                            :href="route('career.jobs')"
                            :active="route().current('career.jobs')"
                        >
                            Posting Loker
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('career.center')"
                            :active="route().current('career.center')"
                        >
                            Career Center
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isSuperAdmin"
                            :href="route('admin.users.index')"
                            :active="route().current('admin.users.*')"
                        >
                            Users
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ roleLabel }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="isAdmin"
                                :href="route('settings.integration.index')"
                                :active="route().current('settings.integration.*')"
                            >
                                Pengaturan
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div
                v-if="successMessage"
                class="mt-6 w-full px-4 sm:px-6 lg:px-8"
            >
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ successMessage }}
                </div>
            </div>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
