<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'pickup_date', 'pickup_time', 'delivery_type', 'delivery_address', 'status', 'total_price', 'payment_status', 'dp_amount', 'payment_proof'
    ];

    // Tambahkan relasi ke User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke OrderItem
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
