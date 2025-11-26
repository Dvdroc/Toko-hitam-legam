<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\ProductClosure;
use App\Models\OrderItem;

class SlotController extends Controller
{
    public function index(Request $request, $date = null)
    {
        // 1. Tentukan Tanggal Target (Default: Hari Ini, atau ambil dari URL)
        $targetDate = $date ? Carbon::parse($date) : Carbon::now();
        
        // --- LOGIKA DETAIL SLOT (Untuk Tampilan Utama) ---
        $products = Product::all();
foreach ($products as $product) {
    $dateStr = $targetDate->format('Y-m-d'); // wajib format string

    $sold = OrderItem::where('product_id', $product->id)
        ->whereHas('order', fn($q) => $q->whereDate('pickup_date', $dateStr)
                                            ->where('status', '!=', 'cancelled'))
        ->sum('quantity');

    $product->sold_today = $sold;
    $product->remaining_quota = max(0, $product->daily_quota - $sold);
    $product->is_full = ($product->remaining_quota == 0);
    $product->is_closed_manual = $product->isClosedOn($dateStr);
}


        // --- LOGIKA KALENDER (Untuk Popup) ---
        $year = $targetDate->year;
        $month = $targetDate->month;
        $monthName = $targetDate->translatedFormat('F Y');
        
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $startDayIndex = $firstDayOfMonth->dayOfWeek;
        $daysInMonth = $firstDayOfMonth->daysInMonth;

        // Cari tanggal yang ada pesanan (untuk tanda merah di kalender)
        $markedDates = OrderItem::whereHas('order', function($q) use ($year, $month) {
                            $q->whereYear('pickup_date', $year)
                              ->whereMonth('pickup_date', $month)
                              ->where('status', '!=', 'cancelled');
                        })
                        ->get()
                        ->map(fn($item) => Carbon::parse($item->order->pickup_date)->day)
                        ->unique()
                        ->toArray();

        // Link Navigasi Bulan di Popup
        $prevMonthDate = $targetDate->copy()->subMonth()->format('Y-m-d');
        $nextMonthDate = $targetDate->copy()->addMonth()->format('Y-m-d');

        return view('admin.slots.index', compact(
            'targetDate', 'products', // Data Detail
            'monthName', 'startDayIndex', 'daysInMonth', 'markedDates', // Data Kalender
            'prevMonthDate', 'nextMonthDate'
        ));
    }

    public function update(Request $request)
    {
         $request->validate([
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'action' => 'required|in:open,close',
        ]);

        if ($request->action === 'close') {
            ProductClosure::firstOrCreate([
                'product_id' => $request->product_id,
                'date' => $request->date
            ]);
        } else { // open
            ProductClosure::where('product_id', $request->product_id)
                ->where('date', $request->date)
                ->delete();
        }

        return back()->with('success', 'Slot berhasil diperbarui.');
    }
}