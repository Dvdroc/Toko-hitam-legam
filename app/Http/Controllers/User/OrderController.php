<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductClosure;
use App\Models\ProductionSlot;
use App\Http\Controllers\Admin\SlotController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController extends Controller
{
    // PROSES SIMPAN PESANAN (CHECKOUT)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required',
            'delivery_type' => 'required|in:pickup,delivery',
            'delivery_address' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $totalPrice = $product->price * $request->quantity;

        $slot = ProductionSlot::firstOrCreate(
            ['date' => $request->pickup_date],
            ['quota' => 200, 'used_quota' => 0, 'is_closed' => false]
        );

        if($slot->is_closed || $slot->quota - $slot->used_quota < $request->quantity){
            return back()->with('error','Slot tanggal ini tidak tersedia');
        }
        // 2. Simpan ke Tabel Orders (Induk)
        $order = Order::create([
            'user_id' => Auth::id(), // Ambil ID user yang sedang login
            'pickup_date' => $request->pickup_date,
            'pickup_time' => $request->pickup_time, 
            'delivery_type' => $request->delivery_type,
            'delivery_address' => $request->delivery_address,
            'status' => 'pending', // PENTING: Status awal 'pending' agar muncul di Notifikasi Admin
            'total_price' => $totalPrice,
        ]);

        // 3. Simpan ke Tabel Order Items (Rincian Kue)
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price_per_unit' => $product->price,
            'subtotal' => $totalPrice,
        ]);

        // 4. Redirect ke Halaman List Pesanan
        return redirect()->route('list-pesanan')->with('success', 'Pesanan berhasil dibuat! Tunggu konfirmasi admin.');
    }
    public function show($id, Request $request){
        $product = Product::findOrFail($id);
    
        $today = Carbon::today();
        $minDate = $today->copy()->addDays(7); // minimal 7 hari sebelum pickup
        $maxDate = $today->copy()->addDays(30); // maksimal 30 hari ke depan
    
        $slots = [];
        for ($date = $minDate->copy(); $date->lte($maxDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
    
            // cek jika admin menutup slot
            $isClosed = ProductClosure::where('product_id', $product->id)
                        ->where('date', $dateStr)
                        ->exists();
    
            // hitung total yang sudah dipesan
            $sold = OrderItem::where('product_id', $product->id)
                    ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $dateStr))
                    ->sum('quantity');
            $slots[$dateStr] = [
                'remaining' => max(0, $product->daily_quota - $sold),
                'is_closed' => $isClosed
            ];
        }
    
        return view('user.order', compact('product', 'slots', 'minDate', 'maxDate') );
    }
    public function pesanan($id, Request $request){
        $product = Product::findOrFail($id);
    
        $today = Carbon::today();
        $minDate = $today->copy()->addDays(7); // minimal 7 hari sebelum pickup
        $maxDate = $today->copy()->addDays(30); // maksimal 30 hari ke depan
    
        $slots = [];
        for ($date = $minDate->copy(); $date->lte($maxDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
    
            // cek jika admin menutup slot
            $isClosed = ProductClosure::where('product_id', $product->id)
                        ->where('date', $dateStr)
                        ->exists();
    
            // hitung total yang sudah dipesan
            $sold = OrderItem::where('product_id', $product->id)
                    ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $dateStr))
                    ->sum('quantity');
            $kuota = ProductionSlot::where('date', $dateStr)->first();
    
            $slots[$dateStr] = [
                'remaining' => max(0, $product->daily_quota - $sold),
                'is_closed' => $isClosed
            ];
        }
    
        $selectedDate = request()->query('pickup_date', $minDate->format('Y-m-d'));
        $user = Auth::user();
        return view('user.pesanan', compact('product', 'slots', 'minDate', 'maxDate', 'user', 'selectedDate'));


    }

    public function updateStatus(Order $order, $status){
        $order->status = $status;
        $order->save();

        if($status == 'production' || $status == 'ready'){ // admin accept
            $slot = ProductionSlot::where('date', $order->pickup_date)->first();
            if($slot){
                $slot->used_quota += $order->items->sum('quantity');
                $slot->save();
            }
        }

        if($status == 'cancelled'){ // kalo admin tolak pesanan
            // tidak perlu ubah slot karena belum dikurangi
        }

        return back()->with('success','Status pesanan berhasil diubah');
    }

    public function getAvailableSlots(){
        $today = Carbon::today();
        $minDate = $today->copy()->addDays(7);
        $maxDate = $today->copy()->addDays(30);

        $slots = [];
        for ($date = $minDate->copy(); $date->lte($maxDate); $date->addDay()) {
            $slot = ProductionSlot::firstOrCreate(
                ['date' => $date->format('Y-m-d')],
                ['quota' => 200, 'used_quota' => 0, 'is_closed' => false]
            );

            $slots[$date->format('Y-m-d')] = [
                'remaining' => max(0, $slot->quota - $slot->used_quota),
                'is_closed' => $slot->is_closed
            ];
        }

        return $slots;
    }



    
}