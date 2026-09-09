<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Api\Admin\ProductOptionController as AdminProductOptionController;
use App\Http\Controllers\Api\Admin\ProductOptionValueController as AdminProductOptionValueController;
use App\Http\Controllers\Api\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Store\AddressController;
use App\Http\Controllers\Api\Store\CartController;
use App\Http\Controllers\Api\Store\CategoryController;
use App\Http\Controllers\Api\Store\CheckoutController;
use App\Http\Controllers\Api\Store\OrderController;
use App\Http\Controllers\Api\Store\ProductController;
use App\Http\Controllers\Api\Store\ReviewController;
use App\Http\Controllers\Api\Store\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalogue public
|--------------------------------------------------------------------------
*/

Route::middleware(
    'throttle:120,1'
)->group(function (): void {
    Route::get(
        '/categories',
        [
            CategoryController::class,
            'index',
        ]
    )->name(
        'catalog.categories.index'
    );

    Route::get(
        '/categories/{category:slug}',
        [
            CategoryController::class,
            'show',
        ]
    )->name(
        'catalog.categories.show'
    );

    Route::get(
        '/products',
        [
            ProductController::class,
            'index',
        ]
    )->name(
        'catalog.products.index'
    );

    Route::get(
        '/products/{product:slug}',
        [
            ProductController::class,
            'show',
        ]
    )->name(
        'catalog.products.show'
    );

    Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index'])
        ->name('catalog.products.reviews.index');
});

/*
|--------------------------------------------------------------------------
| Authentication - Guest
|--------------------------------------------------------------------------
*/

Route::middleware(
    'guest'
)->group(function (): void {
    Route::post(
        '/auth/register',
        [
            AuthController::class,
            'register',
        ]
    )->middleware(
        'throttle:5,1'
    );

    Route::post(
        '/auth/login',
        [
            AuthController::class,
            'login',
        ]
    )->middleware(
        'throttle:10,1'
    );

    Route::post(
        '/auth/forgot-password',
        [
            PasswordResetController::class,
            'forgot',
        ]
    )->middleware(
        'throttle:5,1'
    );

    Route::post(
        '/auth/reset-password',
        [
            PasswordResetController::class,
            'reset',
        ]
    )->middleware(
        'throttle:5,1'
    );
});

Route::get(
    '/auth/session',
    [
        AuthController::class,
        'sessionStatus',
    ]
)->middleware('throttle:120,1');

/*
|--------------------------------------------------------------------------
| Authentication - Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active',
])->group(function (): void {
    Route::get(
        '/auth/me',
        [
            AuthController::class,
            'me',
        ]
    );

    Route::post(
        '/auth/logout',
        [
            AuthController::class,
            'logout',
        ]
    );
});

/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
|
| Accessible aux invités et aux utilisateurs authentifiés.
|
| Pour un invité, CartService utilise un cookie HttpOnly contenant
| le guest_token chiffré.
|
| Pour un utilisateur connecté, le panier est rattaché à user_id.
|
*/

Route::middleware(
    'throttle:120,1'
)->group(function (): void {
    Route::get(
        '/cart',
        [
            CartController::class,
            'show',
        ]
    )->name(
        'cart.show'
    );

    Route::post(
        '/cart/items',
        [
            CartController::class,
            'storeItem',
        ]
    )->name(
        'cart.items.store'
    );

    Route::patch(
        '/cart/items/{item}',
        [
            CartController::class,
            'updateItem',
        ]
    )
        ->whereNumber('item')
        ->name(
            'cart.items.update'
        );

    Route::delete(
        '/cart/items/{item}',
        [
            CartController::class,
            'destroyItem',
        ]
    )
        ->whereNumber('item')
        ->name(
            'cart.items.destroy'
        );

    Route::delete(
        '/cart',
        [
            CartController::class,
            'clear',
        ]
    )->name(
        'cart.clear'
    );
});

/*
|--------------------------------------------------------------------------
| Cart - Authenticated Merge
|--------------------------------------------------------------------------
|
| Fusion du panier invité vers le panier de l'utilisateur après
| authentification.
|
*/

Route::middleware([
    'auth:sanctum',
    'active',
    'throttle:30,1',
])->group(function (): void {
    Route::post(
        '/cart/merge',
        [
            CartController::class,
            'merge',
        ]
    )->name(
        'cart.merge'
    );

    Route::get('/wishlist', [WishlistController::class, 'show'])->name('wishlist.show');
    Route::post('/wishlist/items', [WishlistController::class, 'storeItem'])->name('wishlist.items.store');
    Route::delete('/wishlist/items/{product}', [WishlistController::class, 'destroyItem'])->name('wishlist.items.destroy');

    Route::apiResource('addresses', AddressController::class)->except(['create', 'edit']);

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');

    Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])
        ->name('products.reviews.store');
});

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('checkout.store');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware([
        'auth:sanctum',
        'active',
        'admin',
    ])
    ->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::apiResource('coupons', AdminCouponController::class)->except(['create', 'edit']);

        Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('admin.inventory.index');
        Route::get('/inventory/{inventory}', [AdminInventoryController::class, 'show'])->name('admin.inventory.show');
        Route::patch('/inventory/{inventory}', [AdminInventoryController::class, 'update'])->name('admin.inventory.update');

        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
        Route::patch('/reviews/{review}', [AdminReviewController::class, 'update'])->name('admin.reviews.update');

        /*
        |--------------------------------------------------------------------------
        | Admin Categories
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/categories',
            [
                AdminCategoryController::class,
                'index',
            ]
        )->name(
            'admin.categories.index'
        );

        Route::post(
            '/categories',
            [
                AdminCategoryController::class,
                'store',
            ]
        )->name(
            'admin.categories.store'
        );

        Route::get(
            '/categories/{category}',
            [
                AdminCategoryController::class,
                'show',
            ]
        )->name(
            'admin.categories.show'
        );

        Route::patch(
            '/categories/{category}',
            [
                AdminCategoryController::class,
                'update',
            ]
        )->name(
            'admin.categories.update'
        );

        Route::delete(
            '/categories/{category}',
            [
                AdminCategoryController::class,
                'destroy',
            ]
        )->name(
            'admin.categories.destroy'
        );

        /*
        |--------------------------------------------------------------------------
        | Admin Products
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products',
            [
                AdminProductController::class,
                'index',
            ]
        )->name(
            'admin.products.index'
        );

        Route::post(
            '/products',
            [
                AdminProductController::class,
                'store',
            ]
        )->name(
            'admin.products.store'
        );

        Route::get(
            '/products/{product}',
            [
                AdminProductController::class,
                'show',
            ]
        )->name(
            'admin.products.show'
        );

        Route::patch(
            '/products/{product}',
            [
                AdminProductController::class,
                'update',
            ]
        )->name(
            'admin.products.update'
        );

        Route::delete(
            '/products/{product}',
            [
                AdminProductController::class,
                'destroy',
            ]
        )->name(
            'admin.products.destroy'
        );

        Route::post(
            '/products/{product}/restore',
            [
                AdminProductController::class,
                'restore',
            ]
        )->name(
            'admin.products.restore'
        );

        /*
        |--------------------------------------------------------------------------
        | Admin Product Options
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products/{product}/options',
            [
                AdminProductOptionController::class,
                'index',
            ]
        )->name(
            'admin.products.options.index'
        );

        Route::post(
            '/products/{product}/options',
            [
                AdminProductOptionController::class,
                'store',
            ]
        )->name(
            'admin.products.options.store'
        );

        Route::get(
            '/products/{product}/options/{option}',
            [
                AdminProductOptionController::class,
                'show',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.show'
            );

        Route::patch(
            '/products/{product}/options/{option}',
            [
                AdminProductOptionController::class,
                'update',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.update'
            );

        Route::delete(
            '/products/{product}/options/{option}',
            [
                AdminProductOptionController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.destroy'
            );

        /*
        |--------------------------------------------------------------------------
        | Admin Product Option Values
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/products/{product}/options/{option}/values',
            [
                AdminProductOptionValueController::class,
                'store',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.values.store'
            );

        Route::patch(
            '/products/{product}/options/{option}/values/{value}',
            [
                AdminProductOptionValueController::class,
                'update',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.values.update'
            );

        Route::delete(
            '/products/{product}/options/{option}/values/{value}',
            [
                AdminProductOptionValueController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.options.values.destroy'
            );

        /*
        |--------------------------------------------------------------------------
        | Admin Product Variants
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products/{product}/variants',
            [
                AdminProductVariantController::class,
                'index',
            ]
        )->name(
            'admin.products.variants.index'
        );

        Route::post(
            '/products/{product}/variants',
            [
                AdminProductVariantController::class,
                'store',
            ]
        )->name(
            'admin.products.variants.store'
        );

        Route::get(
            '/products/{product}/variants/{variant}',
            [
                AdminProductVariantController::class,
                'show',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.variants.show'
            );

        Route::patch(
            '/products/{product}/variants/{variant}',
            [
                AdminProductVariantController::class,
                'update',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.variants.update'
            );

        Route::delete(
            '/products/{product}/variants/{variant}',
            [
                AdminProductVariantController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.variants.destroy'
            );

        Route::post(
            '/products/{product}/variants/{variant}/restore',
            [
                AdminProductVariantController::class,
                'restore',
            ]
        )->name(
            'admin.products.variants.restore'
        );

        /*
        |--------------------------------------------------------------------------
        | Admin Product Images
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products/{product}/images',
            [
                AdminProductImageController::class,
                'index',
            ]
        )->name(
            'admin.products.images.index'
        );

        Route::post(
            '/products/{product}/images',
            [
                AdminProductImageController::class,
                'store',
            ]
        )->name(
            'admin.products.images.store'
        );

        Route::get(
            '/products/{product}/images/{image}',
            [
                AdminProductImageController::class,
                'show',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.images.show'
            );

        Route::patch(
            '/products/{product}/images/{image}',
            [
                AdminProductImageController::class,
                'update',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.images.update'
            );

        Route::delete(
            '/products/{product}/images/{image}',
            [
                AdminProductImageController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->name(
                'admin.products.images.destroy'
            );

        /*
        |--------------------------------------------------------------------------
        | Admin Customers
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/customers',
            [
                AdminCustomerController::class,
                'index',
            ]
        )->name(
            'admin.customers.index'
        );

        Route::get(
            '/customers/{customer}',
            [
                AdminCustomerController::class,
                'show',
            ]
        )
            ->whereNumber('customer')
            ->name(
                'admin.customers.show'
            );

        Route::patch(
            '/customers/{customer}/status',
            [
                AdminCustomerController::class,
                'updateStatus',
            ]
        )
            ->whereNumber('customer')
            ->name(
                'admin.customers.status.update'
            );

        /*
        |--------------------------------------------------------------------------
        | Admin Orders
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/orders',
            [
                AdminOrderController::class,
                'index',
            ]
        )->name(
            'admin.orders.index'
        );

        Route::get(
            '/orders/{order}',
            [
                AdminOrderController::class,
                'show',
            ]
        )
            ->whereNumber('order')
            ->name(
                'admin.orders.show'
            );

        Route::patch(
            '/orders/{order}/status',
            [
                AdminOrderController::class,
                'updateStatus',
            ]
        )
            ->whereNumber('order')
            ->name(
                'admin.orders.status.update'
            );
    });
