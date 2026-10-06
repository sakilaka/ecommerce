<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();
const { addressError, addresses, deleteAddress, isAddressLoading, loadAddresses, saveAddress, user } = useAuth();

const editingId = ref(null);
const addressNotice = ref('');

const emptyForm = () => ({
    label: 'Home',
    name: user.value?.name ?? '',
    phone: user.value?.phone ?? '',
    district: 'Dhaka',
    area: '',
    postal_code: '',
    address_line: '',
    is_default: addresses.value.length === 0,
});

const form = reactive(emptyForm());

const text = computed(() => locale.value === 'bn' ? {
    title: 'à¦¡à§‡à¦²à¦¿à¦­à¦¾à¦°à¦¿ à¦ à¦¿à¦•à¦¾à¦¨à¦¾',
    hint: 'à¦šà§‡à¦•à¦†à¦‰à¦Ÿà§‡à¦° à¦¸à¦®à§Ÿ à¦¦à§à¦°à§à¦¤ à¦¡à§‡à¦²à¦¿à¦­à¦¾à¦°à¦¿à¦° à¦œà¦¨à§à¦¯ à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦¸à§‡à¦­ à¦•à¦°à§à¦¨à¥¤',
    addTitle: editingId.value ? 'à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦†à¦ªà¦¡à§‡à¦Ÿ' : 'à¦¨à¦¤à§à¦¨ à¦ à¦¿à¦•à¦¾à¦¨à¦¾',
    saved: editingId.value ? 'à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦†à¦ªà¦¡à§‡à¦Ÿ à¦¹à§Ÿà§‡à¦›à§‡à¥¤' : 'à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦¸à§‡à¦­ à¦¹à§Ÿà§‡à¦›à§‡à¥¤',
    deleted: 'à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦®à§à¦›à§‡ à¦«à§‡à¦²à¦¾ à¦¹à§Ÿà§‡à¦›à§‡à¥¤',
    label: 'à¦²à§‡à¦¬à§‡à¦²',
    name: 'à¦°à¦¿à¦¸à¦¿à¦­à¦¾à¦°à§‡à¦° à¦¨à¦¾à¦®',
    phone: 'à¦®à§‹à¦¬à¦¾à¦‡à¦² à¦¨à¦®à§à¦¬à¦°',
    district: 'à¦œà§‡à¦²à¦¾',
    area: 'à¦à¦²à¦¾à¦•à¦¾',
    postalCode: 'à¦ªà§‹à¦¸à§à¦Ÿ à¦•à§‹à¦¡',
    addressLine: 'à¦¸à¦®à§à¦ªà§‚à¦°à§à¦£ à¦ à¦¿à¦•à¦¾à¦¨à¦¾',
    defaultAddress: 'à¦¡à¦¿à¦«à¦²à§à¦Ÿ à¦ à¦¿à¦•à¦¾à¦¨à¦¾',
    save: editingId.value ? 'à¦†à¦ªà¦¡à§‡à¦Ÿ à¦•à¦°à§à¦¨' : 'à¦¸à§‡à¦­ à¦•à¦°à§à¦¨',
    cancel: 'à¦¬à¦¾à¦¤à¦¿à¦²',
    edit: 'à¦à¦¡à¦¿à¦Ÿ',
    remove: 'à¦®à§à¦›à§à¦¨',
    makeDefault: 'à¦¡à¦¿à¦«à¦²à§à¦Ÿ à¦•à¦°à§à¦¨',
    noAddress: 'à¦à¦–à¦¨à¦“ à¦•à§‹à¦¨à§‹ à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦¸à§‡à¦­ à¦•à¦°à¦¾ à¦¨à§‡à¦‡à¥¤',
    defaultBadge: 'à¦¡à¦¿à¦«à¦²à§à¦Ÿ',
} : {
    title: 'Delivery Addresses',
    hint: 'Save addresses for faster checkout across Bangladesh.',
    addTitle: editingId.value ? 'Update Address' : 'Add New Address',
    saved: editingId.value ? 'Address updated.' : 'Address saved.',
    deleted: 'Address removed.',
    label: 'Label',
    name: 'Receiver name',
    phone: 'Mobile number',
    district: 'District',
    area: 'Area',
    postalCode: 'Postal code',
    addressLine: 'Full address',
    defaultAddress: 'Default address',
    save: editingId.value ? 'Update Address' : 'Save Address',
    cancel: 'Cancel',
    edit: 'Edit',
    remove: 'Remove',
    makeDefault: 'Make Default',
    noAddress: 'No saved address yet.',
    defaultBadge: 'Default',
});

const resetForm = () => {
    Object.assign(form, emptyForm());
    editingId.value = null;
};

const editAddress = (address) => {
    editingId.value = address.id;
    Object.assign(form, {
        label: address.label,
        name: address.name,
        phone: address.phone,
        district: address.district,
        area: address.area,
        postal_code: address.postal_code ?? '',
        address_line: address.address_line,
        is_default: address.is_default,
    });
};

const submitAddress = async () => {
    addressNotice.value = '';
    const saved = await saveAddress({ id: editingId.value, ...form });

    if (saved) {
        addressNotice.value = text.value.saved;
        resetForm();
    }
};

const removeAddress = async (addressId) => {
    addressNotice.value = '';
    const removed = await deleteAddress(addressId);

    if (removed) {
        addressNotice.value = text.value.deleted;

        if (editingId.value === addressId) {
            resetForm();
        }
    }
};

const makeDefault = async (address) => {
    addressNotice.value = '';
    const saved = await saveAddress({ ...address, is_default: true });

    if (saved) {
        addressNotice.value = text.value.saved;
    }
};

watch(user, (currentUser) => {
    if (currentUser) {
        loadAddresses();
        resetForm();
    }
}, { immediate: true });
</script>

<template>
    <section class="panel-card">
        <div class="flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-ink text-sm text-white dark:bg-wine">
                <FontAwesomeIcon :icon="icons.house" />
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-ink dark:text-white">{{ text.title }}</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ text.hint }}</p>
            </div>
        </div>

        <div v-if="addressError" class="note-wine mt-4">{{ addressError }}</div>
        <div v-if="addressNotice" class="note-wine mt-4">{{ addressNotice }}</div>

        <div class="mt-4 grid gap-3">
            <article v-for="address in addresses" :key="address.id" class="rounded-2xl bg-ink/5 p-4 dark:bg-white/8">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-semibold text-ink dark:text-white">{{ address.label }}</h4>
                            <span v-if="address.is_default" class="rounded-full bg-wine/10 px-2 py-0.5 text-[10px] font-semibold uppercase text-wine">{{ text.defaultBadge }}</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-ink dark:text-white">{{ address.name }} Â· {{ address.phone }}</p>
                        <p class="mt-1 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ address.address_line }}, {{ address.area }}, {{ address.district }}<span v-if="address.postal_code"> - {{ address.postal_code }}</span></p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button class="btn-ghost h-8 px-3" type="button" @click="editAddress(address)">{{ text.edit }}</button>
                    <button v-if="!address.is_default" class="btn-ghost h-8 px-3" type="button" :disabled="isAddressLoading" @click="makeDefault(address)">{{ text.makeDefault }}</button>
                    <button class="h-8 rounded-full bg-wine/10 px-3 text-xs font-semibold text-wine" type="button" :disabled="isAddressLoading" @click="removeAddress(address.id)">{{ text.remove }}</button>
                </div>
            </article>
            <p v-if="addresses.length === 0" class="rounded-2xl bg-ink/5 px-4 py-3 text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">{{ text.noAddress }}</p>
        </div>

        <form class="mt-4 rounded-2xl bg-ink/5 p-4 dark:bg-white/8" @submit.prevent="submitAddress">
            <div class="flex items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-ink dark:text-white">{{ text.addTitle }}</h4>
                <button v-if="editingId" class="text-xs font-semibold text-wine" type="button" @click="resetForm">{{ text.cancel }}</button>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="field-label">{{ text.label }}<input v-model="form.label" required class="field-input" type="text" placeholder="Home"></label>
                <label class="field-label">{{ text.phone }}<input v-model="form.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel" placeholder="01712345678"></label>
                <label class="field-label sm:col-span-2">{{ text.name }}<input v-model="form.name" required class="field-input" type="text"></label>
                <label class="field-label">{{ text.district }}<input v-model="form.district" required class="field-input" type="text"></label>
                <label class="field-label">{{ text.area }}<input v-model="form.area" required class="field-input" type="text"></label>
                <label class="field-label">{{ text.postalCode }}<input v-model="form.postal_code" class="field-input" type="text"></label>
                <label class="flex items-center gap-3 self-end rounded-full bg-white px-4 py-3 text-sm font-semibold text-ink dark:bg-white/8 dark:text-white"><input v-model="form.is_default" class="size-4 accent-wine" type="checkbox">{{ text.defaultAddress }}</label>
                <label class="field-label sm:col-span-2">{{ text.addressLine }}<textarea v-model="form.address_line" required class="field-textarea"></textarea></label>
            </div>
            <button class="btn-ink mt-4 w-full" type="submit" :disabled="isAddressLoading">{{ text.save }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
        </form>
    </section>
</template>
