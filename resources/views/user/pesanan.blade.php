<x-app-layout>
<div class="flex justify-center items-center min-h-screen bg-gray-100 pt-24">
    <div class="bg-white rounded-xl shadow-md p-6 max-w-sm w-full">
        <h2 class="text-2xl font-bold text-center mb-4">Ringkasan Pesanan</h2>

        <div class="mb-4 text-sm bg-gray-50 p-3 rounded">
            <p><span class="font-semibold">Nama:</span> {{ $user->name }}</p>
            <p><span class="font-semibold">No. HP:</span> {{ $user->phone }}</p>
            <p><span class="font-semibold">Alamat:</span> {{ $user->address }}</p>
        </div>

        <hr class="my-4 border-gray-300">

        <form id="orderForm" action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" id="orderQuantity" value="20"> <input type="hidden" name="delivery_type" value="pickup">
            <input type="hidden" name="delivery_address" value="">
            
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="font-semibold text-sm">Tanggal Pickup</label>
                    <select id="pickupDate" name="pickup_date" class="w-full border rounded p-2 text-sm">
                        @foreach($slots as $date => $slot)
                            <option value="{{ $date }}"
                                {{ $slot['is_closed'] || $slot['remaining'] < 20 ? 'disabled' : '' }}
                                {{ $date == $selectedDate ? 'selected' : '' }}>
                                {{ $date }} (Sisa: {{ $slot['remaining'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="font-semibold text-sm">Jam Ambil</label>
                    <input type="time" name="pickup_time" class="w-full border rounded p-2 text-sm" value="09:00" required>
                </div>
            </div>

            <div class="mb-3">
                <p class="font-semibold text-sm">Produk</p>
                <div class="flex justify-between items-center">
                    <span id="modalProductName">{{ $product->name }}</span>
                    <span id="modalProductPrice" class="text-gray-600">Rp {{ number_format($product->price, 0, ',', '.') }} /pcs</span>
                </div>
            </div>

            <div class="mb-4">
                <p class="font-semibold text-sm mb-1">Jumlah (Min. 20)</p>
                <div class="flex items-center gap-2">
                    <button type="button" id="decrease" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-lg font-bold">-</button>
                    <input type="number" id="productQty" class="w-full text-center border rounded py-2 font-bold" value="20" min="20">
                    <button type="button" id="increase" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-lg font-bold">+</button>
                </div>
                <p class="text-xs text-red-500 mt-1">*Minimal pemesanan 20 pcs</p>
            </div>

            <div class="space-y-1 text-sm border-t pt-3">
                <div class="flex justify-between">
                    <span>Biaya Layanan</span>
                    <span id="serviceFee">Rp 1.000</span>
                </div>
                <div class="flex justify-between">
                    <span>Ongkir</span>
                    <span id="shippingFee">Rp 15.000</span>
                </div>
                <div class="flex justify-between font-bold text-gray-800 text-lg mt-2">
                    <span>Total Tagihan</span>
                    <span id="subtotal">Rp 0</span>
                </div>
            </div>

            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-[#ee2b5c] font-bold text-center text-lg mb-1">PEMBAYARAN DP 50%</h3>
                <p class="text-center text-gray-600 text-sm mb-3">Silahkan scan QRIS di bawah ini</p>
                
                <div class="flex flex-col items-center justify-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" 
                        alt="QRIS Toko Hitam Legam" class="w-40 h-40 bg-white p-2 shadow-sm rounded mb-2">
                    
                    <p class="font-bold text-gray-700">Nominal DP yang harus dibayar:</p>
                    <p id="dpDisplay" class="text-2xl font-extrabold text-[#ee2b5c]">Rp 0</p>
                </div>

                <div class="mt-4">
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                    <input type="file" name="payment_proof" accept="image/*" required
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#ee2b5c] file:text-white hover:file:bg-red-700">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG. Maks 2MB.</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#ee2b5c] hover:bg-red-700 text-white py-3 rounded-lg font-bold transition mt-6 text-lg shadow-lg">
                Konfirmasi Pesanan & Bayar DP
            </button>
        </form>
    </div>
</div>

<script>
    const productPrice = {{ $product->price }};
    const serviceFee = 1000;
    const shippingFee = 15000; // Bisa dibuat dinamis jika pickup/delivery berubah

    const qtyInput = document.getElementById('productQty');
    const orderQtyInput = document.getElementById('orderQuantity');
    const subtotalEl = document.getElementById('subtotal');
    const dpDisplayEl = document.getElementById('dpDisplay');

    function updateCalculations() {
        // Validasi Min 20
        let qty = parseInt(qtyInput.value);
        if (isNaN(qty) || qty < 20) {
            qty = 20; 
            // Kita tidak langsung ubah value input agar user bisa mengetik, 
            // tapi saat submit/blur nanti dipaksa 20.
        }
        
        // Update Hidden Input untuk Form
        orderQtyInput.value = qty;

        // Hitung Total
        let total = (productPrice * qty) + serviceFee + shippingFee;
        let dpAmount = total * 0.5; // Rumus DP 50%

        // Render ke HTML
        subtotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        dpDisplayEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
    }

    // Event Listeners
    document.getElementById('increase').addEventListener('click', () => {
        qtyInput.value = parseInt(qtyInput.value || 0) + 1;
        updateCalculations();
    });

    document.getElementById('decrease').addEventListener('click', () => {
        let current = parseInt(qtyInput.value || 0);
        if(current > 20) { // Batas bawah tombol adalah 20
            qtyInput.value = current - 1;
            updateCalculations();
        } else {
            alert('Minimal pemesanan adalah 20 pcs!');
        }
    });

    // Validasi saat mengetik manual
    qtyInput.addEventListener('change', () => {
        if(parseInt(qtyInput.value) < 20) {
            alert('Minimal pemesanan adalah 20 pcs!');
            qtyInput.value = 20;
        }
        updateCalculations();
    });

    // Jalankan sekali saat load
    updateCalculations();
</script>
</x-app-layout>
