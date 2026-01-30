import { useCheckoutContext } from "../CheckoutContext";
import { ModuleHooks } from "@/components/modules/ModuleHooks";

export function ShippingStep() {
  const {
    shippingMethods,
    shippingMethodId,
    setShippingMethodId,
    shippingLoading,
    shippingError,
    address,
  } = useCheckoutContext();

  if (shippingLoading) {
    return (
      <p className="text-xs text-neutral-500">
        Chargement des modes de livraison…
      </p>
    );
  }

  if (shippingError) {
    return (
      <div className="text-xs text-red-500">
        {shippingError || "Impossible de charger les modes de livraison."}
      </div>
    );
  }

  if (!shippingMethods.length) {
    return (
      <p className="text-xs text-neutral-500">
        Aucun mode de livraison disponible pour le moment.
      </p>
    );
  }

  return (
    <div className="space-y-2">
      <h2 className="text-sm font-semibold text-neutral-900">
        3. Mode de livraison
      </h2>

      <div className="space-y-2">
        {shippingMethods.map((m) => {
          const isSelected = shippingMethodId === m.id;

          return (
            <button
              key={m.id}
              type="button"
              onClick={() => setShippingMethodId(m.id)}
              className={`w-full flex items-center justify-between rounded-xl border px-3 py-2 text-left text-xs transition ${
                isSelected
                  ? "border-black bg-gray-50"
                  : "border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-800"
              }`}
            >
              <div>
                <div className="font-medium">{m.name}</div>
                {m.description && (
                  <div className="text-xxs text-neutral-700">
                    {m.description}
                  </div>
                )}
                {m.delivery_time && (
                  <div className="text-xxxs text-neutral-500">
                    Délai prévu: {m.delivery_time}
                  </div>
                )}
              </div>
              <div className="font-semibold text-xs">
                {Number(m.price).toFixed(2)} €
              </div>
            </button>
          );
        })}
      </div>

      {/* Module Hooks - Permet aux modules d'injecter du contenu après les méthodes de livraison */}
      <ModuleHooks
        hookName="checkout.shipping.after-methods"
        context={{
          shippingMethodId,
          shippingMethodCode: shippingMethods?.find(m => m.id === shippingMethodId)?.code,
          shippingAddress: address,
        }}
      />
    </div>
  );
}