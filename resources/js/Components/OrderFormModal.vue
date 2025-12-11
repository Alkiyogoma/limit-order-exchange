<script setup>
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    user: Object,
});

const emit = defineEmits(['close', 'success']);
const toast = useToast();

const symbol = ref('BTC');
const side = ref('buy');
const price = ref('');
const amount = ref('');
const isSubmitting = ref(false);
const errors = ref({});

const COMMISSION_RATE = 0.015; // 1.5%

// Calculate total cost
const totalCost = computed(() => {
    if (!price.value || !amount.value) return 0;
    return parseFloat(price.value) * parseFloat(amount.value);
});

// Calculate commission
const commission = computed(() => {
    return totalCost.value * COMMISSION_RATE;
});

// Get user's available balance
const availableBalance = computed(() => {
    return parseFloat(props.user?.balance || 0);
});

// Get user's available asset
const availableAsset = computed(() => {
    const asset = props.user?.assets?.find((a) => a.symbol === symbol.value);
    return parseFloat(asset?.amount || 0);
});

// Validation
const validate = () => {
    errors.value = {};

    if (!price.value || parseFloat(price.value) <= 0) {
        errors.value.price = 'Price must be greater than 0';
    }

    if (!amount.value || parseFloat(amount.value) <= 0) {
        errors.value.amount = 'Amount must be greater than 0';
    }

    if (side.value === 'buy' && totalCost.value > availableBalance.value) {
        errors.value.balance = `Insufficient balance. You need $${totalCost.value.toFixed(2)} but have $${availableBalance.value.toFixed(2)}`;
    }

    if (
        side.value === 'sell' &&
        parseFloat(amount.value) > availableAsset.value
    ) {
        errors.value.asset = `Insufficient ${symbol.value}. You need ${amount.value} but have ${availableAsset.value}`;
    }

    return Object.keys(errors.value).length === 0;
};

// Submit order
const submitOrder = async () => {
    if (!validate()) return;

    isSubmitting.value = true;

    try {
        await axios.post('/api/orders', {
            symbol: symbol.value,
            side: side.value,
            price: price.value,
            amount: amount.value,
        });

        // Reload page data to update balance and orders
        router.reload({ only: ['auth'] });

        // Clear form
        resetForm();

        // Emit success event
        emit('success', {
            message: `${side.value === 'buy' ? 'Buy' : 'Sell'} order placed successfully!`,
            symbol: symbol.value,
            amount: amount.value,
            price: price.value,
        });
        toast.success('Order submitted successfully!');

        // Close modal
        emit('close');
    } catch (error) {
        if (error.response?.data?.message) {
            errors.value.submit = error.response.data.message;
            toast.error(error.response.data.message);
        } else {
            errors.value.submit = 'Failed to place order. Please try again.';
            toast.error('Failed to place order. Please try again.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Reset form
const resetForm = () => {
    price.value = '';
    amount.value = '';
    errors.value = {};
};

// Reset form when modal opens/closes
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            resetForm();
        }
    },
);
</script>

<template>
    <!-- Modal Backdrop -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click.self="emit('close')"
        >
            <!-- Modal Content -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-if="show"
                    class="w-full max-w-md rounded-lg bg-white shadow-xl"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b p-4">
                        <h3 class="text-lg font-bold">Place Limit Order</h3>
                        <button
                            @click="emit('close')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4 p-6">
                        <!-- Symbol Selection -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Symbol
                            </label>
                            <select
                                v-model="symbol"
                                class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="BTC">Bitcoin (BTC)</option>
                                <option value="ETH">Ethereum (ETH)</option>
                            </select>
                        </div>

                        <!-- Side Selection -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Side
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    @click="side = 'buy'"
                                    :class="
                                        side === 'buy'
                                            ? 'bg-gray-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    "
                                    class="rounded-lg py-2.5 font-semibold transition"
                                >
                                    Buy
                                </button>
                                <button
                                    @click="side = 'sell'"
                                    :class="
                                        side === 'sell'
                                            ? 'bg-red-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    "
                                    class="rounded-lg py-2.5 font-semibold transition"
                                >
                                    Sell
                                </button>
                            </div>
                        </div>

                        <!-- Price Input -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Price (USD)
                            </label>
                            <input
                                v-model="price"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': errors.price }"
                            />
                            <p
                                v-if="errors.price"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.price }}
                            </p>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Amount ({{ symbol }})
                            </label>
                            <input
                                v-model="amount"
                                type="number"
                                step="0.00000001"
                                placeholder="0.00000000"
                                class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': errors.amount }"
                            />
                            <p
                                v-if="errors.amount"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.amount }}
                            </p>
                            <p
                                v-if="side === 'sell'"
                                class="mt-1 text-xs text-gray-500"
                            >
                                Available: {{ availableAsset.toFixed(8) }}
                                {{ symbol }}
                            </p>
                        </div>

                        <!-- Order Summary -->
                        <div class="space-y-2 rounded-lg bg-gray-50 p-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Cost:</span>
                                <span class="font-semibold"
                                    >${{ totalCost.toFixed(2) }}</span
                                >
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600"
                                    >Commission (1.5%):</span
                                >
                                <span class="font-semibold text-orange-600"
                                    >${{ commission.toFixed(2) }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between border-t pt-2 text-sm"
                            >
                                <span class="font-medium text-gray-700">
                                    {{
                                        side === 'buy'
                                            ? 'You Pay:'
                                            : 'You Receive:'
                                    }}
                                </span>
                                <span class="font-bold">
                                    {{
                                        side === 'buy'
                                            ? `$${totalCost.toFixed(2)}`
                                            : `$${(totalCost - commission).toFixed(2)}`
                                    }}
                                </span>
                            </div>
                            <div
                                v-if="side === 'buy'"
                                class="text-xs text-gray-500"
                            >
                                Available Balance: ${{
                                    availableBalance.toFixed(2)
                                }}
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div
                            v-if="
                                errors.balance || errors.asset || errors.submit
                            "
                            class="rounded-lg bg-red-50 p-3 text-sm text-red-700"
                        >
                            {{
                                errors.balance || errors.asset || errors.submit
                            }}
                        </div>

                        <!-- Submit Button -->
                        <button
                            @click="submitOrder"
                            :disabled="isSubmitting"
                            :class="
                                side === 'buy'
                                    ? 'bg-gray-800 hover:bg-gray-700 disabled:bg-gray-300'
                                    : 'bg-red-600 hover:bg-red-700 disabled:bg-red-300'
                            "
                            class="w-full rounded-lg py-3 font-semibold text-white transition disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmitting">Processing...</span>
                            <span v-else>
                                Place
                                {{ side === 'buy' ? 'Buy' : 'Sell' }} Order
                            </span>
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
