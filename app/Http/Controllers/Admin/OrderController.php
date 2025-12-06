<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductClosure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // FUNGSI INDEX: Untuk menampilkan halaman Status Pesan
    public function index(Request $request)
    {
        // 1. AMBIL DATA TANGGAL UNIK (Untuk Dropdown Filter)
        // Hanya ambil tanggal yang benar-benar ada pesanan (pickup_date)
        $availableDates = Order::select('pickup_date')
            ->distinct()
            ->whereNotNull('pickup_date')
            ->orderBy('pickup_date', 'desc')
            ->pluck('pickup_date');

        // 2. QUERY ITEM PESANAN
        $query = OrderItem::with(['product', 'order.user'])
            ->whereHas('order'); // Pastikan relasi order ada

        // 3. TERAPKAN FILTER JIKA ADA INPUT TANGGAL
        if ($request->has('date') && $request->date != '') {
            $query->whereHas('order', function($q) use ($request) {
                $q->whereDate('pickup_date', $request->date);
            });
        }

        // 4. EKSEKUSI QUERY
        $items = $query->latest()->get();

        // Kelompokkan pesanan berdasarkan Nama Kue
        $groupedOrders = $items->groupBy(function($item) {
            return $item->product->name;
        });

        return view('admin.orders.index', compact('groupedOrders', 'availableDates'));
    }

    // FUNGSI APPROVE (TERIMA PESANAN)
    public function approve($id)
    {
        $order = Order::findOrFail($id);
        
        // Ubah status menjadi 'processing'
        $order->update(['status' => 'processing']);

        return back()->with('success', 'Pesanan berhasil diterima. Status kini Diproses.');
    }
    
    // FUNGSI UPDATE STATUS: Menangani perubahan status manual & PEMBATALAN (Tolak)
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        // Validasi status
        $request->validate([
            'status' => 'required|in:pending,processing,production,ready,completed,cancelled'
        ]);

        $order->update(['status' => $request->status]);
        
        $msg = $request->status == 'cancelled' ? 'Pesanan berhasil ditolak/dibatalkan.' : 'Status pesanan diperbarui.';

        return back()->with('success', $msg);
    }

    // ... (Fungsi show, store, userOrders, cancelOrder biarkan tetap sama) ...
    
    // FUNGSI show() - Menampilkan ketersediaan kuota produk per tanggal
    public function show($id, Request $request){
        $product = Product::findOrFail($id);
        $pickupDate = $request->query('pickup_date'); 

        // Hitung slot
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();
        $slots = [];     

        for ($date = $monthStart; $date->lte($monthEnd); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            $isClosed = ProductClosure::where('product_id', $product->id)
                        ->where('date', $dateStr)
                        ->exists();

            // Note: Untuk perhitungan kuota, kita tetap HANYA menghitung yang TIDAK CANCELLED
            // Agar kuota kembali tersedia jika pesanan dibatalkan.
            $sold = OrderItem::where('product_id', $product->id)
                        ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $dateStr)->where('status', '!=', 'cancelled')) 
                        ->sum('quantity');

            $slots[$dateStr] = [
                'remaining' => max(0, $product->daily_quota - $sold),
                'is_closed' => $isClosed
            ];
        }
            return view('user.pesanan', compact('product', 'slots', 'pickupDate'));
    }

    // FUNGSI store() - Logika Checkout dengan Kuota Produk
    public function store(Request $request){
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required',
            'delivery_type' => 'required|in:pickup,delivery',
            'delivery_address' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $requestedQuantity = $request->quantity;
        $pickupDate = $request->pickup_date;
        $totalPrice = $product->price * $requestedQuantity;

        // Cek penutupan manual
        $isClosed = ProductClosure::where('product_id', $product->id)
                    ->where('date', $pickupDate)
                    ->exists();

        if ($isClosed) {
             return back()->with('error', 'Produk ini tidak tersedia untuk tanggal pengambilan tersebut.');
        }

        // Hitung total kuantitas yang sudah dipesan
        $sold = OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $pickupDate)->where('status', '!=', 'cancelled'))
                ->sum('quantity');
                
        $dailyQuota = $product->daily_quota;
        $remainingQuota = max(0, $dailyQuota - $sold);

        // Tolak pesanan jika melebihi kuota
        if ($requestedQuantity > $remainingQuota) {
            return back()->with('error', 'Pesanan melebihi kuota harian produk (Sisa kuota: ' . $remainingQuota . ' pcs). Pesanan ditolak.');
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'pickup_date' => $request->pickup_date,
            'pickup_time' => $request->pickup_time,
            'delivery_type' => $request->delivery_type,
            'delivery_address' => $request->delivery_address,
            'status' => 'pending', 
            'total_price' => $totalPrice,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price_per_unit' => $product->price,
            'subtotal' => $totalPrice,
        ]);

        return redirect()->route('user.list-pesanan')->with('success', 'Pesanan berhasil dibuat! Tunggu konfirmasi admin.');
    }
    
    public function userOrders(){
        $orders = Order::with('orderItems.product')
                    ->where('user_id', Auth::id())
                    ->latest()
                    ->get();

        return view('user.datapesanan', compact('orders'));
    }

    public function cancelOrder($id){
        $order = Order::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

        if($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
            return back()->with('success', 'Pesanan dibatalkan.');
        }

        return back()->with('error', 'Pesanan tidak bisa dibatalkan.');
    }
}