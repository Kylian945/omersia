import { Box, Lock, Undo2 } from "lucide-react";

export function CheckoutReassurance() {
  return (
    <div className="rounded-2xl bg-white border border-neutral-200 p-3 text-xxs text-neutral-500 space-y-2">
      <div className="flex items-center gap-2">
        <Lock className="w-4 h-4" />
        Paiement sécurisé par notre prestataire.
      </div>
      <div className="flex items-center gap-2">
        <Box className="w-4 h-4" />
        Suivi de commande et notifications par email.
      </div>
      <div className="flex items-center gap-2">
        <Undo2 className="w-4 h-4" />
        Retours possibles selon nos CGV.
      </div>
    </div>
  );
}
