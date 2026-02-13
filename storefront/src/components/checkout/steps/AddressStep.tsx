import { useCheckoutContext } from "../CheckoutContext";
import { CountrySelect, getCountryName } from "../components/CountrySelect";
import { useState } from "react";
import { getErrorMessage } from "@/lib/utils/error-utils";
import { Button } from "@/components/common/Button";
import { ModuleHooks } from "@/components/modules/ModuleHooks";

export function AddressStep() {
  const {
    effectiveUser,
    addresses,
    address,
    selectedAddressId,
    setSelectedAddressId,
    updateAddress,
    useSameAddressForBilling,
    setUseSameAddressForBilling,
    billingAddress,
    updateBillingAddress,
    setAddresses,
  } = useCheckoutContext();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalLabel, setModalLabel] = useState("");
  const [modalLine1, setModalLine1] = useState("");
  const [modalLine2, setModalLine2] = useState("");
  const [modalZip, setModalZip] = useState("");
  const [modalCity, setModalCity] = useState("");
  const [modalCountry, setModalCountry] = useState("FR");
  const [modalSaving, setModalSaving] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const handleSelectAddress = (id: number | "new") => {
    setSelectedAddressId(id);
    if (id !== "new") {
      const addr = addresses.find((a) => a.id === id);
      if (addr) {
        updateAddress({
          line1: addr.line1,
          line2: addr.line2 ?? "",
          zip: addr.postcode,
          city: addr.city,
          country: addr.country || "FR",
        });
      }
    }
  };

  const openNewAddressModal = () => {
    setModalLabel("");
    setModalLine1("");
    setModalLine2("");
    setModalZip("");
    setModalCity("");
    setModalCountry("FR");
    setModalError(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setModalError(null);
  };

  const handleSaveNewAddress = async () => {
    if (!effectiveUser) {
      setModalError("Vous devez être connecté");
      return;
    }

    // Validation
    if (!modalLabel.trim()) {
      setModalError("Le nom de l'adresse est requis");
      return;
    }
    if (!modalLine1.trim()) {
      setModalError("L'adresse est requise");
      return;
    }
    if (!modalZip.trim()) {
      setModalError("Le code postal est requis");
      return;
    }
    if (!modalCity.trim()) {
      setModalError("La ville est requise");
      return;
    }

    setModalSaving(true);
    setModalError(null);

    try {
      const res = await fetch("/api/account/addresses", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          label: modalLabel,
          line1: modalLine1,
          line2: modalLine2,
          postcode: modalZip,
          city: modalCity,
          country: modalCountry,
          is_default_shipping: addresses.length === 0,
          is_default_billing: false,
        }),
      });

      if (!res.ok) {
        const errorText = await res.text().catch(() => "Erreur inconnue");
        setModalError(errorText || "Erreur lors de l'ajout de l'adresse");
        return;
      }

      const newAddress = await res.json();
      setAddresses((prev) => [...prev, newAddress]);
      setSelectedAddressId(newAddress.id);
      updateAddress({
        line1: newAddress.line1,
        line2: newAddress.line2 ?? "",
        zip: newAddress.postcode,
        city: newAddress.city,
        country: newAddress.country || "FR",
      });
      closeModal();
    } catch (err: unknown) {
      setModalError(getErrorMessage(err) || "Erreur réseau");
    } finally {
      setModalSaving(false);
    }
  };

  return (
    <div className="space-y-4">
      <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
        2. Adresse de livraison
      </h2>

      {/* Bloc adresses existantes */}
      {effectiveUser && addresses.length > 0 && (
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-xxs text-[var(--theme-muted-color,#6b7280)]">
              Utiliser une adresse enregistrée
            </span>
          </div>

          <div className="space-y-2 flex flex-wrap">
            <div className="w-full grid grid-cols-1 md:grid-cols-3 gap-3">
              {addresses.map((addr) => {
                const isSelected = selectedAddressId === addr.id;
                return (
                  <button
                    key={addr.id}
                    type="button"
                    onClick={() => handleSelectAddress(addr.id)}
                    className={`text-left rounded-xl border px-3 py-2 text-xs transition ${
                      isSelected
                        ? "border-[var(--theme-border-hover,#111827)] bg-[var(--theme-page-bg,#f6f6f7)]"
                        : "border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] hover:bg-[var(--theme-page-bg,#f6f6f7)] text-[var(--theme-body-color,#374151)]"
                    }`}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <div>
                        <div className="font-medium">{addr.label}</div>
                        <div className="text-xxxs opacity-80">
                          {addr.line1}
                          {addr.line2 && `, ${addr.line2}`}
                          <br />
                          {addr.postcode} {addr.city}
                          <br/>
                          {getCountryName(addr.country)}
                        </div>
                      </div>
                    </div>
                  </button>
                );
              })}

              <button
                type="button"
                onClick={openNewAddressModal}
                className="text-left rounded-xl border border-dashed border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] hover:bg-[var(--theme-page-bg,#f6f6f7)] text-[var(--theme-body-color,#374151)] px-3 py-2 text-xxs transition"
              >
                + Ajouter une nouvelle adresse
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Formulaire adresse de livraison */}
      <div className="space-y-2 mt-2">
        <div className="flex items-center justify-between">
          <span className="text-xxs text-[var(--theme-muted-color,#6b7280)]">
            {effectiveUser && addresses.length > 0
              ? "Détails de l'adresse sélectionnée"
              : "Saisissez votre adresse de livraison"}
          </span>
        </div>

        <div>
          <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
            Adresse *
          </label>
          <input
            type="text"
            value={address.line1}
            onChange={(e) => updateAddress({ line1: e.target.value })}
            className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
          />
        </div>
        <div>
          <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
            Complément d&apos;adresse
          </label>
          <input
            type="text"
            value={address.line2}
            onChange={(e) => updateAddress({ line2: e.target.value })}
            className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
          />
        </div>
        <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Code postal *
            </label>
            <input
              type="text"
              value={address.zip}
              onChange={(e) => updateAddress({ zip: e.target.value })}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
            />
          </div>
          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Ville *
            </label>
            <input
              type="text"
              value={address.city}
              onChange={(e) => updateAddress({ city: e.target.value })}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
            />
          </div>
          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Pays *
            </label>
            <CountrySelect
              value={address.country}
              onChange={(value) => updateAddress({ country: value })}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
            />
          </div>
        </div>

      </div>

      {/* Hook: checkout.address.validators - Permet d'ajouter des validateurs d'adresse personnalisés */}
      <ModuleHooks
        hookName="checkout.address.validators"
        context={{
          shippingAddress: address,
          billingAddress,
          useSameAddressForBilling,
        }}
      />

      {/* Case à cocher pour utiliser la même adresse de facturation */}
      <div className="mt-4 pt-4 border-t border-[var(--theme-border-default,#e5e7eb)]">
        <label className="inline-flex items-center gap-2 text-xs text-[var(--theme-body-color,#374151)] cursor-pointer">
          <input
            type="checkbox"
            checked={useSameAddressForBilling}
            onChange={(e) => setUseSameAddressForBilling(e.target.checked)}
            className="rounded border-[var(--theme-border-default,#e5e7eb)]"
          />
          Utiliser la même adresse de facturation
        </label>
      </div>

      {/* Formulaire adresse de facturation (si différente) */}
      {!useSameAddressForBilling && (
        <div className="space-y-2 mt-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-[var(--theme-heading-color,#111827)]">
              Adresse de facturation
            </span>
          </div>

          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Adresse *
            </label>
            <input
              type="text"
              value={billingAddress.line1}
              onChange={(e) => updateBillingAddress({ line1: e.target.value })}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
            />
          </div>
          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Complément d&apos;adresse
            </label>
            <input
              type="text"
              value={billingAddress.line2}
              onChange={(e) => updateBillingAddress({ line2: e.target.value })}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
            />
          </div>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
            <div>
              <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                Code postal *
              </label>
              <input
                type="text"
                value={billingAddress.zip}
                onChange={(e) => updateBillingAddress({ zip: e.target.value })}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              />
            </div>
            <div>
              <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                Ville *
              </label>
              <input
                type="text"
                value={billingAddress.city}
                onChange={(e) => updateBillingAddress({ city: e.target.value })}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              />
            </div>
            <div>
              <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                Pays *
              </label>
              <CountrySelect
                value={billingAddress.country}
                onChange={(value) => updateBillingAddress({ country: value })}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              />
            </div>
          </div>
        </div>
      )}

      {/* Modal pour ajouter une nouvelle adresse */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-md rounded-2xl bg-[var(--theme-card-bg,#ffffff)] p-6 shadow-xl">
            <div className="mb-4">
              <h3 className="text-lg font-semibold text-[var(--theme-heading-color,#111827)]">
                Ajouter une nouvelle adresse
              </h3>
            </div>

            <div className="space-y-3">
              {modalError && (
                <p className="text-xs text-red-600">{modalError}</p>
              )}

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Nom de l&apos;adresse *
                </label>
                <input
                  type="text"
                  value={modalLabel}
                  onChange={(e) => setModalLabel(e.target.value)}
                  placeholder="Ex: Domicile, Bureau..."
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Adresse *
                </label>
                <input
                  type="text"
                  value={modalLine1}
                  onChange={(e) => setModalLine1(e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Complément d&apos;adresse
                </label>
                <input
                  type="text"
                  value={modalLine2}
                  onChange={(e) => setModalLine2(e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                    Code postal *
                  </label>
                  <input
                    type="text"
                    value={modalZip}
                    onChange={(e) => setModalZip(e.target.value)}
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
                <div>
                  <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                    Ville *
                  </label>
                  <input
                    type="text"
                    value={modalCity}
                    onChange={(e) => setModalCity(e.target.value)}
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Pays *
                </label>
                <CountrySelect
                  value={modalCountry}
                  onChange={(value) => setModalCountry(value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 mt-6">
              <Button
                type="button"
                onClick={closeModal}
                disabled={modalSaving}
                variant="secondary"
                size="md"
              >
                Annuler
              </Button>
              <Button
                type="button"
                onClick={handleSaveNewAddress}
                disabled={modalSaving}
                variant="primary"
                size="md"
              >
                {modalSaving ? "Enregistrement..." : "Enregistrer"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
