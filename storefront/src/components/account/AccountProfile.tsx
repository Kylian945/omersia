"use client";

import { useState } from "react";
import { PenIcon } from "lucide-react";
import { Button } from "@/components/common/Button";
import { logger } from "@/lib/logger";

type UserProfile = {
  firstname: string | null;
  lastname: string | null;
  email: string;
  phone?: string | null;
};

type Props = {
  initialUser: UserProfile;
};

export function AccountProfile({ initialUser }: Props) {
  const [user, setUser] = useState<UserProfile>(initialUser);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form, setForm] = useState({
    firstname: initialUser.firstname || "",
    lastname: initialUser.lastname || "",
    email: initialUser.email,
    phone: initialUser.phone || "",
  });
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const openModal = () => {
    setForm({
      firstname: user.firstname || "",
      lastname: user.lastname || "",
      email: user.email,
      phone: user.phone || "",
    });
    setErrorMsg(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    if (loading) return;
    setIsModalOpen(false);
  };

  const handleChange = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async () => {
    if (!form.firstname.trim() || !form.lastname.trim() || !form.email.trim()) {
      setErrorMsg("Veuillez remplir les champs obligatoires.");
      return;
    }

    setLoading(true);
    setErrorMsg(null);

    try {
      const res = await fetch("/api/account/profile", {
        method: "PUT",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          firstname: form.firstname.trim(),
          lastname: form.lastname.trim(),
          email: form.email.trim(),
          phone: form.phone.trim() || null,
        }),
      });

      if (!res.ok) {
        let msg = "Une erreur est survenue.";
        try {
          const json = await res.json();
          msg =
            json?.message ||
            json?.backend?.message ||
            json?.error ||
            msg;
        } catch {
          // ignore
        }
        setErrorMsg(msg);
        return;
      }

      const updated = await res.json();
      setUser(updated);
      setIsModalOpen(false);
    } catch (e) {
      logger.error(e instanceof Error ? e.message : String(e));
      setErrorMsg("Erreur réseau lors de la mise à jour du profil.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="theme-account-card flex-1 rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5 space-y-3">
        <div>
          <div className="flex justify-between items-baseline">
            <div className="text-xs text-[var(--theme-muted-color,#6b7280)]">Nom complet</div>
            <button
              type="button"
              onClick={openModal}
              className="w-6 h-6 flex items-center justify-center rounded-md border border-[var(--theme-border-default,#e5e7eb)] text-[var(--theme-heading-color,#111827)] hover:bg-[var(--theme-input-bg,#ffffff)]"
            >
              <PenIcon className="w-3 h-3" />
            </button>
          </div>
          <div className="text-xs font-medium text-[var(--theme-heading-color,#111827)]">
            {(user.firstname || "") + " " + (user.lastname || "")}
          </div>
        </div>
        <div>
          <div className="text-xs text-[var(--theme-muted-color,#6b7280)]">Adresse e-mail</div>
          <div className="text-xs font-medium text-[var(--theme-heading-color,#111827)]">
            {user.email}
          </div>
        </div>
        <div>
          <div className="text-xs text-[var(--theme-muted-color,#6b7280)]">Téléphone</div>
          <div className="text-xs font-medium text-[var(--theme-heading-color,#111827)]">
            {user.phone || "Non renseigné"}
          </div>
        </div>
      </div>

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="theme-account-card bg-[var(--theme-card-bg,#ffffff)] rounded-2xl shadow-xl w-full max-w-md p-4 border border-[var(--theme-border-default,#e5e7eb)]">
            <div className="flex items-center justify-between mb-2">
              <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
                Modifier mon profil
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
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                    Prénom *
                  </label>
                  <input
                    type="text"
                    value={form.firstname}
                    onChange={(e) =>
                      handleChange("firstname", e.target.value)
                    }
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
                <div>
                  <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                    Nom *
                  </label>
                  <input
                    type="text"
                    value={form.lastname}
                    onChange={(e) =>
                      handleChange("lastname", e.target.value)
                    }
                    className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Adresse e-mail *
                </label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => handleChange("email", e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
              </div>

              <div>
                <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                  Téléphone
                </label>
                <input
                  type="text"
                  value={form.phone}
                  onChange={(e) => handleChange("phone", e.target.value)}
                  className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                />
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
                Enregistrer
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
