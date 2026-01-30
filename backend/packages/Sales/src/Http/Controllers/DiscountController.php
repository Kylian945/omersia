<?php

declare(strict_types=1);

namespace Omersia\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;
use Omersia\Sales\DTO\DiscountCreateDTO;
use Omersia\Sales\DTO\DiscountUpdateDTO;
use Omersia\Sales\Http\Requests\DiscountStoreRequest;
use Omersia\Sales\Http\Requests\DiscountUpdateRequest;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Services\DiscountCreationService;

class DiscountController extends Controller
{
    public function __construct(
        private readonly DiscountCreationService $discountCreationService
    ) {}

    public function index(): View
    {
        $this->authorize('discounts.view');

        $discounts = Discount::forShop(1)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin::discounts.index', compact('discounts'));
    }

    public function create(): View
    {
        $this->authorize('discounts.create');

        $shopId = 1;

        $products = Product::where('shop_id', $shopId)
            ->where('is_active', true)
            ->with(['translations', 'mainImage'])
            ->orderBy('sku')
            ->get();

        $collections = Category::where('shop_id', $shopId)
            ->where('is_active', true)
            ->with(['translations'])
            ->orderBy('position')
            ->get();

        $customerGroups = CustomerGroup::where('shop_id', $shopId)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('shop_id', $shopId)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $initialType = request('type', 'order');

        return view('admin::discounts.create', [
            'discount' => null,
            'products' => $products,
            'collections' => $collections,
            'customerGroups' => $customerGroups,
            'customers' => $customers,
            'initialType' => $initialType,
        ]);
    }

    public function store(DiscountStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $dto = DiscountCreateDTO::fromArray($validated, shopId: 1);

        $this->discountCreationService->createDiscount($dto);

        return redirect()
            ->route('discounts.index')
            ->with('success', 'Réduction créée.');
    }

    public function edit(Discount $discount): View
    {
        $this->authorize('discounts.update');
        $this->authorizeDiscount($discount);

        $discount->load(['products', 'collections', 'customerGroups', 'customers']);

        $shopId = 1;

        $products = Product::where('shop_id', $shopId)
            ->with(['translations', 'mainImage'])
            ->orderBy('id', 'desc')
            ->get();

        $collections = Category::where('shop_id', $shopId)
            ->with('translations')
            ->orderBy('position')
            ->get();

        $customerGroups = CustomerGroup::where('shop_id', $shopId)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('shop_id', $shopId)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $initialType = old('type', $discount->type);

        return view('admin::discounts.edit', [
            'discount' => $discount,
            'initialType' => $initialType,
            'products' => $products,
            'collections' => $collections,
            'customerGroups' => $customerGroups,
            'customers' => $customers,
        ]);
    }

    public function update(DiscountUpdateRequest $request, Discount $discount): RedirectResponse
    {
        $this->authorizeDiscount($discount);

        $validated = $request->validated();
        $dto = DiscountUpdateDTO::fromArray($validated);

        $this->discountCreationService->updateDiscount($discount, $dto);

        return redirect()
            ->route('discounts.index')
            ->with('success', 'Réduction mise à jour.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $this->authorize('discounts.delete');
        $this->authorizeDiscount($discount);
        $discount->delete();

        return redirect()
            ->route('discounts.index')
            ->with('success', 'Réduction supprimée.');
    }

    protected function authorizeDiscount(Discount $discount): void
    {
        if ($discount->shop_id !== 1) {
            abort(404);
        }
    }
}
