@extends('admin.layout')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Semua Pesanan</h2>
            <p class="text-sm text-gray-500">Kelola semua transaksi dan status produksi.</p>
        </div>
        <button onclick="window.location.reload()" class="text-sm text-blue-600 font-bold hover:text-blue-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Refresh
        </button>
    </div>

    {{-- BAGIAN FILTER TANGGAL --}}
    <div class="mb-8">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="inline-flex items-center bg-white border border-gray-200 shadow-sm rounded-xl px-4 py-2">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                
                <div class="flex flex-col">
                    <label for="date_filter" class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Filter Tanggal Ambil</label>
                    <select name="date" id="date_filter" onchange="this.form.submit()" class="border-none p-0 text-sm font-bold text-gray-700 focus:ring-0 cursor-pointer bg-transparent">
                        <option value="">Semua Tanggal</option>
                        @foreach($availableDates as $date)
                            <option value="{{ $date }}" {{ request('date') == $date ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(request('date'))
                <div class="ml-4 pl-4 border-l border-gray-200">
                    <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Reset
                    </a>
                </div>
            @endif
        </form>
    </div>

    <div class="space-y-8 pb-20"> 
        @forelse($groupedOrders as $kategori => $orders)
            {{-- Container Tabel harus overflow-visible --}}
            <div class="bg-white rounded-2xl shadow-sm overflow-visible border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-gray-800 font-bold text-lg tracking-wide flex items-center gap-2">
                        <span class="w-2 h-6 bg-[#606C38] rounded-full"></span> 
                        {{ $kategori }}
                    </h3>
                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-lg border border-gray-200 font-medium">{{ count($orders) }} Pesanan</span>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#606C38] text-white text-xs uppercase tracking-wider border-b border-[#606C38]">
                            <th class="px-6 py-4 font-semibold">Faktur</th>
                            <th class="px-6 py-4 font-semibold">Pelanggan</th>
                            <th class="px-6 py-4 font-semibold">Tanggal Ambil</th>
                            <th class="px-6 py-4 font-semibold text-center">Qty</th>
                            <th class="px-6 py-4 font-semibold text-right">Total</th>
                            <th class="px-6 py-4 font-semibold text-center">Status Saat Ini</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        {{-- LOOPING DENGAN INDEX ($index) --}}
                        @foreach($orders as $index => $orderItem)
                        @php $order = $orderItem->order; @endphp
                        @if($order)
                        
                        <tr class="hover:bg-gray-50 transition">
                            
                            <td class="px-6 py-4 font-medium text-gray-900">
                                #APM-{{ $order->id }}
                                <div class="text-xs text-gray-400 font-normal mt-1">{{ $order->created_at->format('d/m H:i') }}</div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $order->user->name ?? 'Guest' }}</div>
                                <div class="text-xs text-gray-500">{{ $order->delivery_type == 'delivery' ? 'Delivery' : 'Pickup' }}</div>
                            </td>
                            
                            <td class="px-6 py-4 text-gray-600">
                                {{ \Carbon\Carbon::parse($order->pickup_date)->format('d M Y') }}
                            </td>
                            
                            <td class="px-6 py-4 text-center font-bold text-gray-700">
                                {{ $orderItem->quantity }}
                            </td>
                            
                            <td class="px-6 py-4 text-right font-medium text-gray-900">
                                Rp {{ number_format($order->total_price, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusBadge = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'production' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'ready' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                        'completed' => 'bg-green-100 text-green-800 border-green-200',
                                        'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                                    ][$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>

                            {{-- FIX CSS: Z-INDEX PADA TD --}}
                            <td class="px-6 py-4 text-center relative td-dropdown-container" style="z-index: {{ 50 - $index }};">
                                <div class="relative inline-block text-left">
                                    <button onclick="toggleDropdown('dropdown-{{ $order->id }}', this)" 
                                        type="button" 
                                        class="inline-flex justify-center items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                                        <svg class="mr-1.5 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        Update
                                        <svg class="-mr-1 ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    {{-- DROPDOWN MENU --}}
                                    <div id="dropdown-{{ $order->id }}" 
                                         class="hidden absolute right-0 mt-2 w-44 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 overflow-hidden divide-y divide-gray-100 dropdown-menu-item ">
                                        
                                        @foreach(['pending', 'processing', 'production', 'ready', 'completed', 'cancelled'] as $statusOption)
                                            @if($statusOption !== $order->status)
                                                @php
                                                    $dropdownColor = match($statusOption) {
                                                        'pending' => 'text-yellow-600 hover:bg-yellow-50',
                                                        'processing' => 'text-blue-600 hover:bg-blue-50',
                                                        'production' => 'text-purple-600 hover:bg-purple-50',
                                                        'ready' => 'text-indigo-600 hover:bg-indigo-50',
                                                        'completed' => 'text-green-600 hover:bg-green-50',
                                                        'cancelled' => 'text-red-600 hover:bg-red-50',
                                                        default => 'text-gray-700 hover:bg-gray-50',
                                                    };
                                                    
                                                    $dotColor = match($statusOption) {
                                                        'pending' => 'bg-yellow-400',
                                                        'processing' => 'bg-blue-500',
                                                        'production' => 'bg-purple-500',
                                                        'ready' => 'bg-indigo-500',
                                                        'completed' => 'bg-green-500',
                                                        'cancelled' => 'bg-red-500',
                                                        default => 'bg-gray-400',
                                                    };
                                                @endphp

                                                <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $statusOption }}">
                                                    <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium transition flex items-center gap-3 {{ $dropdownColor }}">
                                                        <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }}"></span>
                                                        {{ ucfirst($statusOption) }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-gray-300">
                <div class="flex flex-col items-center justify-center text-gray-500">
                    @if(request('date'))
                        <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p>Tidak ada pesanan untuk tanggal <span class="font-bold">{{ \Carbon\Carbon::parse(request('date'))->translatedFormat('d F Y') }}</span>.</p>
                        <a href="{{ route('admin.orders.index') }}" class="mt-2 text-blue-600 hover:underline text-sm">Tampilkan Semua</a>
                    @else
                        <p>Belum ada data pesanan.</p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- FIX JAVASCRIPT: BOOSTER Z-INDEX --}}
    <script>
        function toggleDropdown(dropdownId, buttonElement) {
            const dropdown = document.getElementById(dropdownId);
            const allDropdowns = document.querySelectorAll('.dropdown-menu-item');
            
            // 1. Reset z-index semua parent TD ke 'auto' agar tidak ada konflik
            document.querySelectorAll('.td-dropdown-container').forEach(td => {
                td.style.zIndex = 'auto'; // Reset ke default
            });

            // 2. Tutup semua dropdown lain
            allDropdowns.forEach(item => {
                if (item.id !== dropdownId) {
                    item.classList.add('hidden');
                }
            });

            // 3. Logic Buka/Tutup
            if (dropdown.classList.contains('hidden')) {
                // BUKA DROPDOWN
                dropdown.classList.remove('hidden');
                
                // --- KUNCI KEBERHASILAN ---
                // Cari elemen TD pembungkus tombol ini, dan paksa z-index nya jadi 50
                // Ini akan membuat TD ini "melompat" ke layer paling atas mengalahkan baris bawahnya
                const parentTd = buttonElement.closest('td');
                if (parentTd) {
                    parentTd.style.zIndex = '50'; 
                }
            } else {
                // TUTUP DROPDOWN
                dropdown.classList.add('hidden');
            }
        }

        // Tutup dropdown jika klik di luar area
        window.onclick = function(event) {
            if (!event.target.closest('button')) {
                const allDropdowns = document.querySelectorAll('.dropdown-menu-item');
                allDropdowns.forEach(item => {
                    item.classList.add('hidden');
                });
                
                // Reset semua z-index TD saat klik di luar
                document.querySelectorAll('.td-dropdown-container').forEach(td => {
                    td.style.zIndex = 'auto';
                });
            }
        }
    </script>
@endsection