<?php

namespace Tests\Feature;

use App\Models\Money;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2022-01-01 00:00:00');
    }

    public function testIndex()
    {
        for ($i = 0; $i < 3; $i++) {
            $product = new Product();
            $product->setName($this->faker->name);
            $product->setPrice((new Money())->setCents(100 * $i));
            $product->setAvailable($this->faker->randomNumber(4));
            $product->setVatRate($this->faker->randomFloat(2, 0, 1));
            $product->setImage($this->faker->name);

            DB::table('products')->insert([
                'name' => $product->getName(),
                'available' => $product->getAvailable(),
                'price' => $product->getPrice()->getCents(),
                'vat_rate' => $product->getVatRate(),
                'image' => $product->getImage(),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $productsInDatabase = DB::select('select * from products');

        foreach ($productsInDatabase as $productInDatabase) {
            DB::table('cart')->insert([
                'product_id' => $productInDatabase->id,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $response = $this->get('/api/v1/cart');

        $responseContent = json_decode($response->getContent());

        $response->assertStatus(200);

        $response->assertExactJson(
            [
                'products' => [
                    [
                        'id' => $productsInDatabase[0]->id,
                        'name' => $productsInDatabase[0]->name,
                        'vatRate' => $productsInDatabase[0]->vat_rate,
                        'price' => $productsInDatabase[0]->price / 100, // divided by hundred because the price in the database is in cents, while the response is in euros
                        'available' => $productsInDatabase[0]->available,
                        'image' => $productsInDatabase[0]->image
                    ],
                    [
                        'id' => $productsInDatabase[1]->id,
                        'name' => $productsInDatabase[1]->name,
                        'vatRate' => $productsInDatabase[1]->vat_rate,
                        'price' => $productsInDatabase[1]->price / 100, // divided by hundred because the price in the database is in cents, while the json is in euros
                        'available' => $productsInDatabase[1]->available,
                        'image' => $productsInDatabase[1]->image,
                    ],
                    [
                        'id' => $productsInDatabase[2]->id,
                        'name' => $productsInDatabase[2]->name,
                        'vatRate' => $productsInDatabase[2]->vat_rate,
                        'price' => $productsInDatabase[2]->price / 100, // divided by hundred because the price in the database is in cents, while the json is in euros
                        'available' => $productsInDatabase[2]->available,
                        'image' => $productsInDatabase[2]->image,
                    ]
                ],
                'subtotal' => $responseContent->subtotal,
                'vatAmount' => $responseContent->vatAmount,
                'total' => $responseContent->total
            ]
        );
    }

    public function testCreate()
    {
        $product = new Product();
        $product->setName($this->faker->name);
        $product->setPrice((new Money())->setCents($this->faker->randomFloat()));
        $product->setAvailable($this->faker->randomNumber(4));
        $product->setVatRate($this->faker->randomFloat(2, 0, 1));
        $product->setImage($this->faker->name);

        DB::table('products')->insert([
            'name' => $product->getName(),
            'available' => $product->getAvailable(),
            'price' => $product->getPrice()->getCents(),
            'vat_rate' => $product->getVatRate(),
            'image' => $product->getImage(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $productInDatabase = DB::select('select id from products where name = ?', [$product->getName()]);

        $response = $this->post('/api/v1/cart', [
            'productId' => $productInDatabase[0]->id,
        ]);

        $responseContent = json_decode($response->getContent());

        $response->assertStatus(201);
        $response->assertExactJson([
            [
                'id' => $responseContent[0]->id,
                'product_id' => $productInDatabase[0]->id,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]
        ]);

        $this->assertDatabaseHas('cart',
            [
                'id' => $responseContent[0]->id,
                'product_id' => $productInDatabase[0]->id,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function testCreateIfProductIsInCartAlready()
    {
        $product = new Product();
        $product->setName($this->faker->name);
        $product->setPrice((new Money())->setCents($this->faker->randomFloat()));
        $product->setAvailable($this->faker->randomNumber(4));
        $product->setVatRate($this->faker->randomFloat(2, 0, 1));
        $product->setImage($this->faker->name);

        DB::table('products')->insert([
            'name' => $product->getName(),
            'available' => $product->getAvailable(),
            'price' => $product->getPrice()->getCents(),
            'vat_rate' => $product->getVatRate(),
            'image' => $product->getImage(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $productInDatabase = DB::select('select id from products where name = ?', [$product->getName()]);

        $this->post('/api/v1/cart', [
            'productId' => $productInDatabase[0]->id,
        ]);

        $response = $this->post('/api/v1/cart', [
            'productId' => $productInDatabase[0]->id,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function testDestroy()
    {
        $product = new Product();
        $product->setName($this->faker->name);
        $product->setPrice((new Money())->setCents($this->faker->randomFloat()));
        $product->setAvailable($this->faker->randomNumber(4));
        $product->setVatRate($this->faker->randomFloat(2, 0, 1));
        $product->setImage($this->faker->name);

        DB::table('products')->insert([
            'name' => $product->getName(),
            'available' => $product->getAvailable(),
            'price' => $product->getPrice()->getCents(),
            'vat_rate' => $product->getVatRate(),
            'image' => $product->getImage(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $productInDatabase = DB::select('select id from products where name = ?', [$product->getName()]);

        DB::table('cart')->insert([
            'product_id' => $productInDatabase[0]->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->delete('/api/v1/cart/' . $productInDatabase[0]->id);

        $response->assertStatus(204);
        $response->assertNoContent();
    }
}
