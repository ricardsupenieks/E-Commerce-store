<?php

namespace App\Repositories;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CartRepository
{
    public function getProducts(): array
    {
        return DB::select('select * from products join cart on products.id = product_id');
    }

    public function addProduct(int $productId): array
    {
        DB::table('cart')->insert([
            'product_id' => $productId,
            'created_at'=> Carbon::now(),
            'updated_at'=> Carbon::now(),
        ]);

        $cartResult = DB::select('select * from cart where product_id = ?', [$productId]);

        return $cartResult;
    }

    public function removeProduct(int $productId): void
    {
        DB::delete('delete from cart where product_id = ?', [$productId]);
    }
}
