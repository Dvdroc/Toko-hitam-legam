<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductClosure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\Order;
// use App\Models\ProductionSlot; <-- DIHAPUS
// use App\Http\Controllers\Admin\SlotController; <-- DIHAPUS (jika ada, saya hapus saja untuk memastikan)
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // FUNGSI INDEX: Untuk menampilkan halaman Status Pesan
    public function index()
    {
        // Ambil item pesanan yang statusnya tidak 'batal'
        $items = OrderItem::with(['product', 'order.user'])
            ->whereHas('order', function($q) {
                // Menampilkan semua pesanan kecuali yang dibatalkan
                $q->where('status', '!=', 'cancelled'); 
            })
            ->latest()
            ->get();

        // Kelompokkan pesanan berdasarkan Nama Kue (Bikang, Kukang, dll)
        $groupedOrders = $items->groupBy(function($item) {
            return $item->product->name;
        });

        return view('admin.orders.index', compact('groupedOrders'));
    }

    
    // FUNGSI UPDATE STATUS: Untuk mengubah status pesanan (Pending -> Selesai)
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        // Validasi status yang boleh dipilih
        $request->validate([
            'status' => 'required|in:pending,processing,production,ready,completed,cancelled'
        ]);

        $order->update(['status' => $request->status]);
        
        // TIDAK ADA LOGIKA ProductionSlot di sini
        
        return back()->with('success', 'Status pesanan #' . $id . ' berhasil diperbarui.');
    }

    // FUNGSI show() - Menampilkan ketersediaan kuota produk per tanggal
    public function show($id, Request $request){
        $product = Product::findOrFail($id);
        $pickupDate = $request->query('pickup_date'); // ambil dari query string jika ada

        // Hitung slot
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();
        $slots = [];     

        for ($date = $monthStart; $date->lte($monthEnd); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            $isClosed = ProductClosure::where('product_id', $product->id)
                        ->where('date', $dateStr)
                        ->exists();

            // hitung total yang sudah dipesan (Hanya menghitung yang TIDAK DIBATALKAN)
            $sold = OrderItem::where('product_id', $product->id)
                        ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $dateStr)->where('status', '!=', 'cancelled')) // MODIFIKASI: Filter status
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
            'delivery_type' => 'required|in:pickup,delivery',
            'delivery_address' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $requestedQuantity = $request->quantity;
        $pickupDate = $request->pickup_date;
        $totalPrice = $product->price * $requestedQuantity;

        // --- MULAI LOGIKA PENGECKEKAN KUOTA BARU (MENGGANTIKAN ProductionSlot) ---

        // A. Cek penutupan manual
        $isClosed = ProductClosure::where('product_id', $product->id)
                    ->where('date', $pickupDate)
                    ->exists();

        if ($isClosed) {
             return back()->with('error', 'Produk ini tidak tersedia untuk tanggal pengambilan tersebut.');
        }

        // B. Hitung total kuantitas yang sudah dipesan (tidak termasuk yang dibatalkan)
        $sold = OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $pickupDate)->where('status', '!=', 'cancelled'))
                ->sum('quantity');
                
        $dailyQuota = $product->daily_quota;
        $remainingQuota = max(0, $dailyQuota - $sold);

        // C. Tolak pesanan jika melebihi kuota
        if ($requestedQuantity > $remainingQuota) {
            return back()->with('error', 'Pesanan melebihi kuota harian produk (Sisa kuota: ' . $remainingQuota . ' pcs). Pesanan ditolak.');
        }

        // --- AKHIR LOGIKA PENGECKEKAN KUOTA BARU ---

        $order = Order::create([
            'user_id' => Auth::id(),
            'pickup_date' => $request->pickup_date,
            'pickup_time' => '09:00:00',
            'delivery_type' => $request->delivery_type,
            'delivery_address' => $request->delivery_address,
            'status' => 'pending', // status awal user membuat pesanan
            'total_price' => $totalPrice,
        ]);

        // SEMUA PESANAN MASUK KE ORDER ITEMS
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price_per_unit' => $product->price,
            'subtotal' => $totalPrice,
        ]);

        // Redirect user ke list pesanan
        return redirect()->route('user.list-pesanan')->with('success', 'Pesanan berhasil dibuat! Tunggu konfirmasi admin.');
    }
    
    // FUNGSI userOrders, cancelOrder tidak diubah
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