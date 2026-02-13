"use client";

import { useState } from "react";
import { PenIcon, Trash2 } from "lucide-react";
import type { Address } from "@/lib/types/addresses-types";
import { Button } from "@/components/common/Button";
import { logger } from "@/lib/logger";

type Props = {
  initialAddresses: Address[];
};

type AddressForm = {
  label: string;
  line1: string;
  line2: string;
  postcode: string;
  city: string;
  country: string;
  is_default_billing: boolean;
  is_default_shipping: boolean;
};

export function AccountAddresses({ initialAddresses }: Props) {
  const [addresses, setAddresses] = useState<Address[]>(initialAddresses || []);

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<"create" | "edit">("create");
  const [currentAddressId, setCurrentAddressId] = useState<number | null>(null);

  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [addressToDelete, setAddressToDelete] = useState<Address | null>(null);
  const [deleteError, setDeleteError] = useState<string | null>(null);

  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const [form, setForm] = useState<AddressForm>({
    label: "",
    line1: "",
    line2: "",
    postcode: "",
    city: "",
    country: "France",
    is_default_billing: false,
    is_default_shipping: false,
  });

  const openCreateModal = () => {
    setModalMode("create");
    setCurrentAddressId(null);
    setForm({
      label: "",
      line1: "",
      line2: "",
      postcode: "",
      city: "",
      country: "France",
      is_default_billing: false,
      is_default_shipping: false,
    });
    setErrorMsg(null);
    setIsModalOpen(true);
  };

  const openEditModal = (address: Address) => {
    setModalMode("edit");
    setCurrentAddressId(address.id);
    setForm({
      label: address.label,
      line1: address.line1,
      line2: address.line2 ?? "",
      postcode: address.postcode,
      city: address.city,
      country: address.country || "France",
      is_default_billing: address.is_default_billing,
      is_default_shipping: address.is_default_shipping,
    });
    setErrorMsg(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    if (loading) return;
    setIsModalOpen(false);
  };

  const handleChange = (
    field: keyof AddressForm,
    value: string | boolean
  ) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async () => {
    if (
      !form.label.trim() ||
      !form.line1.trim() ||
      !form.postcode.trim() ||
      !form.city.trim()
    ) {
      setErrorMsg("Veuillez remplir les champs obligatoires.");
      return;
    }

    setLoading(true);
    setErrorMsg(null);

    try {
      const payload = {
        label: form.label,
        line1: form.line1,
        line2: form.line2 || null,
        postcode: form.postcode,
        city: form.city,
        country: form.country || "FR",
        is_default_billing: form.is_default_billing,
        is_default_shipping: form.is_default_shipping,
      };

      let res: Response;

      if (modalMode === "create") {
        res = await fetch("/api/account/addresses", {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        });
      } else {
        if (!currentAddressId) return;
        res = await fetch(`/api/account/addresses/${currentAddressId}`, {
          method: "PUT",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        });
      }

      if (!res.ok) {
        const errJson = await res.json().catch(() => null);
        setErrorMsg(errJson?.message || "Une erreur est survenue.");
        return;
      }

      const saved: Address = await res.json();

      setAddresses((prev) => {
        if (modalMode === "create") {
          return [saved, ...prev];
        }
        return prev.map((a) => (a.id === saved.id ? saved : a));
      });

      setIsModalOpen(false);
    } catch (e) {
      logger.error(e instanceof Error ? e.message : String(e));
      setErrorMsg("Erreur réseau lors de l'envoi du formulaire.");
    } finally {
      setLoading(false);
    }
  };

  // --- Delete : ouverture / confirmation / fermeture ---

  const openDeleteModal = (address: Address) => {
    setAddressToDelete(address);
    setDeleteError(null);
    setIsDeleteModalOpen(true);
  };

  const closeDeleteModal = () => {
    if (loading) return;
    setIsDeleteModalOpen(false);
    setAddressToDelete(null);
    setDeleteError(null);
  };

  const handleConfirmDelete = async () => {
    if (!addressToDelete) return;

    try {
      setLoading(true);
      setDeleteError(null);

      const res = await fetch(`/api/account/addresses/${addressToDelete.id}`, {
        method: "DELETE",
        credentials: "include",
        headers: {
          Accept: "application/json",
        },
      });

      // notre route renvoie 204 en cas de succès
      if (!res.ok && res.status !== 204) {
        const errJson = await res.json().catch(() => null);
        setDeleteError(
          errJson?.message || "Erreur lors de la suppression de l’adresse."
        );
        return;
      }

      setAddresses((prev) => prev.filter((a) => a.id !== addressToDelete.id));
      closeDeleteModal();
    } catch (e) {
      logger.error(e instanceof Error ? e.message : String(e));
      setDeleteError("Erreur réseau lors de la suppression.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="self-start flex-1 flex flex-col">
      <div className="flex items-center justify-between mb-2">
        <div className="text-xs text-[var(--theme-heading-color,#111827)] font-semibold">Adresses</div>
        <Button
          type="button"
          onClick={openCreateModal}
          variant="primary"
          size="sm"
        >
          Ajouter une adresse
        </Button>
      </div>

      <div className="theme-account-card flex-1 rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5 space-y-3">
        {addresses.length === 0 ? (
          <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
            Vous n’avez pas encore enregistré d’adresse.
          </p>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {addresses.map((addr) => (
              <div
                key={addr.id}
                className="text-xs font-medium text-[var(--theme-heading-color,#111827)] flex flex-col relative border border-[var(--theme-border-default,#e5e7eb)] rounded-xl p-3 bg-[var(--theme-page-bg,#f6f6f7)]"
              >
                <div className="flex items-center justify-between mb-2 gap-2">
                  <span className="truncate">{addr.label || "Adresse"}</span>
                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      onClick={() => openEditModal(addr)}
                      className="w-5 h-5 flex items-center justify-center rounded-md border border-[var(--theme-border-default,#e5e7eb)] text-[var(--theme-heading-color,#111827)] hover:bg-[var(--theme-input-bg,#ffffff)]"
                    >
                      <PenIcon className="w-2 h-2" />
                    </button>
                    <button
                      type="button"
                      onClick={() => openDeleteModal(addr)}
                      className="w-5 h-5 flex items-center justify-center rounded-md border border-[var(--theme-border-default,#e5e7eb)] text-red-600 hover:bg-red-50"
                    >
                      <Trash2 className="w-2 h-2" />
                    </button>
                  </div>
                </div>

                <span>{addr.line1}</span>
                {addr.line2 && <span>{addr.line2}</span>}
                <span>
                  {addr.postcode} {addr.city}
                </span>
                <span className="uppercase text-[var(--theme-muted-color,#6b7280)]">
                  {addr.country || "FRANCE"}
                </span>

                <div className="mt-2 flex flex-wrap gap-1 text-xxxs">
                  {addr.is_default_shipping && (
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-[var(--theme-input-bg,#ffffff)] text-[var(--theme-muted-color,#6b7280)] border border-[var(--theme-border-default,#e5e7eb)]">
                      Livraison par défaut
                    </span>
                  )}
                  {addr.is_default_billing && (
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-[var(--theme-input-bg,#ffffff)] text-[var(--theme-muted-color,#6b7280)] border border-[var(--theme-border-default,#e5e7eb)]">
                      Facturation par défaut
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* MODAL CREATE / EDIT */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="theme-account-card bg-[var(--theme-card-bg,#ffffff)] rounded-2xl shadow-xl w-full max-w-md p-4 border border-[var(--theme-border-default,#e5e7eb)]">
            <div className="flex items-center justify-between mb-2">
              <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
                {modalMode === "create"
                  ? "Ajouter une adresse"
                  : "Modifier l’adresse"}
              </h2>
              <button
                type="button"
                onClick={closeModal}
                className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)]"
              >
                Fermer
              </button>
            </div>

            <div className="space-y-2 text-xs">
              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Surnom *
                </label>
                <input
                  type="text"
                  value={form.label}
                  onChange={(e) => handleChange("label", e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Adresse *
                </label>
                <input
                  type="text"
                  value={form.line1}
                  onChange={(e) => handleChange("line1", e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Complément d’adresse
                </label>
                <input
                  type="text"
                  value={form.line2}
                  onChange={(e) => handleChange("line2", e.target.value)}
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
                    value={form.postcode}
                    onChange={(e) =>
                      handleChange("postcode", e.target.value)
                    }
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
                <div>
                  <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                    Ville *
                  </label>
                  <input
                    type="text"
                    value={form.city}
                    onChange={(e) => handleChange("city", e.target.value)}
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Pays *
                </label>
                <input
                  type="text"
                  value={form.country}
                  onChange={(e) => handleChange("country", e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div className="flex flex-col gap-1 mt-2">
                <label className="inline-flex items-center gap-2 text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                  <input
                    type="checkbox"
                    checked={form.is_default_shipping}
                    onChange={(e) =>
                      handleChange("is_default_shipping", e.target.checked)
                    }
                    className="rounded border-[var(--theme-border-default,#e5e7eb)]"
                  />
                  Adresse de livraison par défaut
                </label>

                <label className="inline-flex items-center gap-2 text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                  <input
                    type="checkbox"
                    checked={form.is_default_billing}
                    onChange={(e) =>
                      handleChange("is_default_billing", e.target.checked)
                    }
                    className="rounded border-[var(--theme-border-default,#e5e7eb)]"
                  />
                  Adresse de facturation par défaut
                </label>
              </div>

              {errorMsg && (
                <p className="text-xxs text-red-500 mt-1">{errorMsg}</p>
              )}
            </div>

            <div className="mt-4 flex justify-end gap-2">
              <Button
                type="button"
                onClick={closeModal}
                disabled={loading}
                variant="secondary"
                size="sm"
              >
                Annuler
              </Button>
              <Button
                type="button"
                onClick={handleSubmit}
                disabled={loading}
                variant="primary"
                size="sm"
              >
                {modalMode === "create" ? "Ajouter" : "Enregistrer"}
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL DELETE */}
      {isDeleteModalOpen && addressToDelete && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="theme-account-card bg-[var(--theme-card-bg,#ffffff)] rounded-2xl shadow-xl w-full max-w-sm p-4 border border-[var(--theme-border-default,#e5e7eb)]">
            <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)] mb-1">
              Supprimer cette adresse ?
            </h2>
            <p className="text-xs text-[var(--theme-muted-color,#6b7280)] mb-3">
              Cette action est définitive. Voulez-vous vraiment supprimer
              l’adresse <span className="font-semibold">
                {addressToDelete.label || "sans titre"}
              </span>
              ?
            </p>

            <div className="text-xxs text-[var(--theme-muted-color,#6b7280)] mb-3 space-y-0.5">
              <div>{addressToDelete.line1}</div>
              {addressToDelete.line2 && <div>{addressToDelete.line2}</div>}
              <div>
                {addressToDelete.postcode} {addressToDelete.city}
              </div>
              <div className="uppercase">
                {addressToDelete.country || "FRANCE"}
              </div>
            </div>

            {deleteError && (
              <p className="text-xxs text-red-500 mb-2">{deleteError}</p>
            )}

            <div className="mt-3 flex justify-end gap-2">
              <Button
                type="button"
                onClick={closeDeleteModal}
                disabled={loading}
                variant="secondary"
                size="sm"
              >
                Annuler
              </Button>
              <Button
                type="button"
                onClick={handleConfirmDelete}
                disabled={loading}
                variant="primary"
                size="sm"
              >
                Supprimer
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
