import { useCheckoutContext } from "../CheckoutContext";
import { CheckoutAuth } from "../components/CheckoutAuth";

export function IdentityStep() {
  const { effectiveUser, identity, updateIdentity } = useCheckoutContext();

  // Si l'utilisateur n'est pas connecté, afficher le formulaire d'authentification
  if (!effectiveUser) {
    return (
      <CheckoutAuth
        onAuthSuccess={() => window.location.reload()}
        initialEmail={identity.email}
      />
    );
  }

  // Sinon, afficher le formulaire d'identité
  return (
    <div className="space-y-3">
      <h2 className="text-sm font-semibold text-neutral-900">
        1. Informations personnelles
      </h2>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div>
          <label className="block text-xxxs text-neutral-600 mb-1">
            Prénom *
          </label>
          <input
            type="text"
            value={identity.firstName}
            onChange={(e) => updateIdentity({ firstName: e.target.value })}
            className="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
          />
        </div>
        <div>
          <label className="block text-xxxs text-neutral-600 mb-1">
            Nom *
          </label>
          <input
            type="text"
            value={identity.lastName}
            onChange={(e) => updateIdentity({ lastName: e.target.value })}
            className="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
          />
        </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div>
          <label className="block text-xxxs text-neutral-600 mb-1">
            Email *
          </label>
          <input
            type="email"
            value={identity.email}
            onChange={(e) => updateIdentity({ email: e.target.value })}
            className="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
          />
        </div>
        <div>
          <label className="block text-xxxs text-neutral-600 mb-1">
            Téléphone
          </label>
          <input
            type="tel"
            value={identity.phone}
            onChange={(e) => updateIdentity({ phone: e.target.value })}
            className="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
          />
        </div>
      </div>
    </div>
  );
}
