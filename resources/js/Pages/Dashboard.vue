<script setup>
import OrderFormModal from '@/Components/OrderFormModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const orders = ref([]);
const orderbook = ref({ buys: [], sells: [] });
const selectedSymbol = ref('BTC');
const filterSymbol = ref('all');
const filterSide = ref('all');
const filterStatus = ref('all');
const notification = ref({ show: false, message: '', type: 'success' });
const showOrderModal = ref(false);

let echoChannel = null;

onMounted(() => {
    fetchOrders();
    fetchOrderbook();
    echoChannel = setupWebSocket();
});

const setupWebSocket = () => {
    if (!window.Echo) {
        console.error('Laravel Echo is not initialized');
        return;
    }

    const channel = window.Echo.private(`user.${user.value.id}`);

    channel.listen('OrderMatched', (e) => {
        // Reload shared data to update user balance and assets
        router.reload({ only: ['auth'] });

        // Refresh orders and orderbook
        fetchOrders();
        fetchOrderbook();

        // Show notification with trade details
        const side = e.buyer_id === user.value.id ? 'bought' : 'sold';
        const total = (parseFloat(e.amount) * parseFloat(e.price)).toFixed(2);
        showNotification(
            `Trade executed: ${side} ${e.amount} ${e.symbol} @ $${parseFloat(e.price).toLocaleString()} (Total: $${parseFloat(total).toLocaleString()})`,
            'success',
        );
    });

    return channel;
};

onUnmounted(() => {
    if (echoChannel) {
        echoChannel.stopListening('OrderMatched');
        window.Echo.leave(`user.${user.value.id}`);
    }
});

const fetchOrders = async () => {
    try {
        const response = await axios.get('/api/orders/my');
        orders.value = response.data;
    } catch (error) {
        console.error('Error fetching orders:', error);
        showNotification('Failed to fetch orders', 'error');
    }
};

const fetchOrderbook = async () => {
    try {
        const response = await axios.get(
            `/api/orders?symbol=${selectedSymbol.value}`,
        );
        orderbook.value = response.data;
    } catch (error) {
        console.error('Error fetching orderbook:', error);
        showNotification('Failed to fetch orderbook', 'error');
    }
};

const cancelOrder = async (orderId) => {
    try {
        await axios.post(`/api/orders/${orderId}/cancel`);
        await fetchOrders();
        await fetchOrderbook();
        // Reload auth data to update balance
        router.reload({ only: ['auth'] });
        showNotification('Order cancelled successfully', 'success');
    } catch (error) {
        console.error('Error cancelling order:', error);
        showNotification('Failed to cancel order', 'error');
    }
};

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 5000);
};

const changeSymbol = async (symbol) => {
    selectedSymbol.value = symbol;
    await fetchOrderbook();
};

// Computed filtered orders
const filteredOrders = computed(() => {
    return orders.value.filter((order) => {
        const matchSymbol =
            filterSymbol.value === 'all' || order.symbol === filterSymbol.value;
        const matchSide =
            filterSide.value === 'all' || order.side === filterSide.value;
        const matchStatus =
            filterStatus.value === 'all' ||
            order.status === parseInt(filterStatus.value);
        return matchSymbol && matchSide && matchStatus;
    });
});

// Format price for display
const formatPrice = (price) => {
    return parseFloat(price).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

// Format amount for display
const formatAmount = (amount) => {
    return parseFloat(amount).toFixed(8);
};

// Calculate order total
const calculateTotal = (price, amount) => {
    return (parseFloat(price) * parseFloat(amount)).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const closeNotification = () => {
    notification.value.show = false;
};

const handleOrderSuccess = (data) => {
    showNotification(data.message, 'success');
    fetchOrders();
    fetchOrderbook();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Orders & Wallet Overview" />

        <!-- Order Form Modal -->
        <OrderFormModal
            :show="showOrderModal"
            :user="user"
            @close="showOrderModal = false"
            @success="handleOrderSuccess"
        />

        <!-- Toast Notification -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="notification.show"
                class="fixed right-4 top-4 z-50 max-w-md rounded-lg border p-4 shadow-lg"
                :class="{
                    'border-green-200 bg-green-50':
                        notification.type === 'success',
                    'border-red-200 bg-red-50': notification.type === 'error',
                }"
            >
                <div class="flex items-start">
                    <div class="flex-1">
                        <p
                            class="text-sm font-medium"
                            :class="{
                                'text-green-800':
                                    notification.type === 'success',
                                'text-red-800': notification.type === 'error',
                            }"
                        >
                            {{ notification.message }}
                        </p>
                    </div>
                    <button
                        @click="closeNotification"
                        class="ml-4 text-gray-400 hover:text-gray-600"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <div class="mx-4 mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Wallet -->
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-xl font-bold">Wallet</h3>
                <div class="space-y-3">
                    <div class="flex justify-between rounded bg-gray-50 p-3">
                        <span class="font-medium">USD Balance:</span>
                        <span class="font-bold text-green-600">
                            ${{ formatPrice(user.balance) }}
                        </span>
                    </div>
                    <div
                        v-if="!user.assets || user.assets.length === 0"
                        class="text-sm text-gray-500"
                    >
                        No assets yet
                    </div>
                    <div
                        v-for="asset in user.assets"
                        :key="asset.symbol"
                        class="rounded bg-gray-50 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ asset.symbol }}:</span>
                            <div class="text-right">
                                <div class="font-bold">
                                    {{ formatAmount(asset.amount) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ formatAmount(asset.locked_amount) }}
                                    locked
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orderbook -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold">Orderbook</h3>
                    <div class="flex gap-2">
                        <button
                            @click="changeSymbol('BTC')"
                            class="rounded px-3 py-1 text-sm font-medium transition"
                            :class="
                                selectedSymbol === 'BTC'
                                    ? 'bg-gray-800 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            "
                        >
                            BTC
                        </button>
                        <button
                            @click="changeSymbol('ETH')"
                            class="rounded px-3 py-1 text-sm font-medium transition"
                            :class="
                                selectedSymbol === 'ETH'
                                    ? 'bg-gray-800 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            "
                        >
                            ETH
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Buy Orders (Bids) -->
                    <div>
                        <h4 class="mb-2 font-semibold text-green-600">
                            Buys ({{ orderbook.buys?.length || 0 }})
                        </h4>
                        <div class="space-y-1">
                            <div
                                v-if="
                                    !orderbook.buys ||
                                    orderbook.buys.length === 0
                                "
                                class="text-xs text-gray-400"
                            >
                                No buy orders
                            </div>
                            <div
                                v-for="order in orderbook.buys"
                                :key="order.id"
                                class="rounded bg-green-50 p-2 text-xs"
                            >
                                <div class="font-semibold text-green-700">
                                    ${{ formatPrice(order.price) }}
                                </div>
                                <div class="text-gray-600">
                                    {{ formatAmount(order.amount) }}
                                    {{ selectedSymbol }}
                                </div>
                                <div class="text-gray-500">
                                    Total: ${{
                                        calculateTotal(
                                            order.price,
                                            order.amount,
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sell Orders (Asks) -->
                    <div>
                        <h4 class="mb-2 font-semibold text-red-600">
                            Sells ({{ orderbook.sells?.length || 0 }})
                        </h4>
                        <div class="space-y-1">
                            <div
                                v-if="
                                    !orderbook.sells ||
                                    orderbook.sells.length === 0
                                "
                                class="text-xs text-gray-400"
                            >
                                No sell orders
                            </div>
                            <div
                                v-for="order in orderbook.sells"
                                :key="order.id"
                                class="rounded bg-red-50 p-2 text-xs"
                            >
                                <div class="font-semibold text-red-700">
                                    ${{ formatPrice(order.price) }}
                                </div>
                                <div class="text-gray-600">
                                    {{ formatAmount(order.amount) }}
                                    {{ selectedSymbol }}
                                </div>
                                <div class="text-gray-500">
                                    Total: ${{
                                        calculateTotal(
                                            order.price,
                                            order.amount,
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Orders -->
            <div class="rounded-lg bg-white p-6 shadow md:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold">My Orders</h3>
                    <button
                        @click="showOrderModal = true"
                        class="inline-flex items-center gap-x-2 rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900"
                    >
                        New Order
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="size-5"
                        >
                            <path
                                d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex flex-wrap gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-gray-700"
                            >Symbol</label
                        >
                        <select
                            v-model="filterSymbol"
                            class="rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-gray-700"
                            >Side</label
                        >
                        <select
                            v-model="filterSide"
                            class="rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-gray-700"
                            >Status</label
                        >
                        <select
                            v-model="filterStatus"
                            class="rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All</option>
                            <option value="1">Open</option>
                            <option value="2">Filled</option>
                            <option value="3">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200 bg-gray-50">
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Symbol
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Side
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Price
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Amount
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Total (USD)
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Status
                                </th>
                                <th
                                    class="py-3 text-left text-sm font-semibold"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredOrders.length === 0">
                                <td
                                    colspan="7"
                                    class="py-4 text-center text-gray-500"
                                >
                                    No orders found
                                </td>
                            </tr>
                            <tr
                                v-for="order in filteredOrders"
                                :key="order.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="py-3">
                                    <span class="font-medium">{{
                                        order.symbol
                                    }}</span>
                                </td>
                                <td class="py-3">
                                    <span
                                        class="rounded px-2 py-1 text-xs font-semibold"
                                        :class="
                                            order.side === 'buy'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ order.side }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    ${{ formatPrice(order.price) }}
                                </td>
                                <td class="py-3">
                                    {{ formatAmount(order.amount) }}
                                </td>
                                <td class="py-3 font-medium">
                                    ${{
                                        calculateTotal(
                                            order.price,
                                            order.amount,
                                        )
                                    }}
                                </td>
                                <td class="py-3">
                                    <span
                                        v-if="order.status === 1"
                                        class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700"
                                    >
                                        Open
                                    </span>
                                    <span
                                        v-else-if="order.status === 2"
                                        class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-700"
                                    >
                                        Filled
                                    </span>
                                    <span
                                        v-else
                                        class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700"
                                    >
                                        Cancelled
                                    </span>
                                </td>
                                <td class="py-3">
                                    <button
                                        v-if="order.status === 1"
                                        @click="cancelOrder(order.id)"
                                        class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-red-700"
                                    >
                                        Cancel
                                    </button>
                                    <span v-else class="text-xs text-gray-400"
                                        >—</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
