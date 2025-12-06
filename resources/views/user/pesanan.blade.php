<x-app-layout>
<div class="flex justify-center items-center min-h-screen bg-gray-100 pt-24">
    <div class="bg-white rounded-xl shadow-md p-6 max-w-sm w-full">
        <h2 class="text-2xl font-bold text-center mb-4">Ringkasan Pesanan</h2>

        <div class="mb-4">
            <p class="font-semibold">Nama:</p>
            <p id="userNama" class="mb-2">{{ $user->name }}</p>

            <p class="font-semibold">No. HP:</p>
            <p id="userHP" class="mb-2">{{ $user->phone }}</p>

            <p class="font-semibold">Alamat:</p>
            <p id="userAlamat" class="mb-2">{{ $user->address }}</p>
        </div>

        <hr class="my-4 border-gray-300">

        <form id="orderForm" action="{{ route('user.list-pesanan') }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" id="orderQuantity" value="1">
            <input type="hidden" name="delivery_type" value="pickup">
            <input type="hidden" name="delivery_address" value="">
            <input type="hidden" name="service_fee" value="1000">
            <input type="hidden" name="shipping_fee" value="15000">
            <input type="hidden" id="pickupDateInput" name="pickup_date" value="{{ $pickupDate ?? '' }}">

            <p class="font-semibold mt-2">Tanggal Pickup:</p>
            <select id="pickupDate" name="pickup_date" class="w-full border rounded p-2">
                @foreach($slots as $date => $slot)
                    <option value="{{ $date }}"
                        {{ $slot['is_closed'] || $slot['remaining'] <= 0 ? 'disabled' : '' }}
                        {{ $date == $selectedDate ? 'selected' : '' }}>
                        {{ $date }} {{ $slot['remaining'] }} slot tersisa
                    </option>
                @endforeach
            </select>

            <p class="font-semibold mt-2">Jam Pengambilan:</p>
            <input type="time" name="pickup_time" class="w-full border rounded p-2" value="09:00" required>

            <p class="font-semibold mt-2">Nama Produk:</p>
            <p class="mb-2" id="modalProductName">{{ $product->name }}</p>

            <p class="font-semibold">Harga Produk:</p>
            <p class="mb-2" id="modalProductPrice">Rp {{ number_format($product->price, 0, ',', '.') }}</p>

            <p class="font-semibold mt-2">Jumlah:</p>
            <div class="flex items-center gap-2 mb-2">
                <button type="button" id="decrease" class="bg-gray-200 hover:bg-gray-300 px-5 py-1 rounded">-</button>
                <input type="number" id="productQty" class="w-20 text-center border rounded" value="1" min="1">
                <button type="button" id="increase" class="bg-gray-200 hover:bg-gray-300 px-5 py-1 rounded">+</button>
            </div>

            <p class="font-semibold">Biaya Layanan:</p>
            <p id="serviceFee">Rp1.000</p>

            <p class="font-semibold mt-2">Ongkir:</p>
            <p id="shippingFee">Rp15.000</p>

            <p class="font-semibold mt-2">Subtotal:</p>
            <p id="subtotal" class="text-lg font-bold">Rp {{ number_format($product->price + 1000 + 15000, 0, ',', '.') }}</p>

            <button type="submit" class="w-full bg-[#ee2b5c] hover:bg-red-700 text-white py-2 rounded-lg font-bold transition mt-4">
                Buat Pesanan
            </button>
        </form>
    </div>
</div>

<script>

    const product = {
        price: {{ $product->price }},
    };
    const serviceFee = 1000;
    const shippingFee = 15000;

    const qtyInput = document.getElementById('productQty');
    const subtotalEl = document.getElementById('subtotal');
    const orderQtyInput = document.getElementById('orderQuantity');
    const pickupSelect = document.getElementById('pickupDate');

    function updateSubtotal() {
        let qty = parseInt(qtyInput.value) || 1;
        let subtotal = (product.price * qty) + serviceFee + shippingFee;
        subtotalEl.textContent = `Rp ${subtotal.toLocaleString()}`;
        orderQtyInput.value = qty;
    }

    // Tombol tambah/kurang
    document.getElementById('increase').addEventListener('click', () => {
        qtyInput.value = parseInt(qtyInput.value) + 1;
        updateSubtotal();
    });

    document.getElementById('decrease').addEventListener('click', () => {
        let current = parseInt(qtyInput.value);
        if(current > 1) qtyInput.value = current - 1;
        updateSubtotal();
    });

    // Update subtotal saat input diketik
    qtyInput.addEventListener('input', () => {
        if(parseInt(qtyInput.value) < 1 || isNaN(qtyInput.value)) qtyInput.value = 1;
        updateSubtotal();
    });

    // Update subtotal saat tanggal pickup berubah
    pickupSelect.addEventListener('change', updateSubtotal);

    // Initial subtotal
    updateSubtotal();
</script>
</x-app-layout>
