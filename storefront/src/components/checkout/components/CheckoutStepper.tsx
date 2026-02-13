import { Check } from "lucide-react";

type Step = {
  id: number;
  label: string;
};

type CheckoutStepperProps = {
  steps: Step[];
  currentStep: number;
  onStepClick?: (step: number) => void;
};

export function CheckoutStepper({ steps, currentStep }: CheckoutStepperProps) {
  return (
    <div className="theme-checkout-stepper rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] px-4 py-3">
      <div className="flex items-center justify-between">
        {steps.map((step, index) => {
          const isActive = step.id === currentStep;
          const isDone = step.id < currentStep;

          return (
            <div
              key={step.id}
              className="flex-1 flex flex-col items-center"
            >
              <div className="relative flex items-center justify-center w-full">
                {/* Trait gauche */}
                {index > 0 && (
                  <div
                    className={`
                      absolute left-0 right-1/2 h-px
                      ${step.id <= currentStep ? "bg-[var(--theme-primary,#111827)]" : "bg-[var(--theme-border-default,#e5e7eb)]"}
                    `}
                  />
                )}

                {/* Pastille */}
                <div
                  className={`
                    relative z-10 w-6 h-6 rounded-full flex items-center justify-center
                    text-xs border transition
                    ${
                      isActive
                        ? "bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] border-[var(--theme-border-hover,#111827)]"
                        : isDone
                        ? "bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] border-[var(--theme-primary,#111827)]"
                        : "bg-[var(--theme-card-bg,#ffffff)] text-[var(--theme-muted-color,#6b7280)] border-[var(--theme-border-default,#e5e7eb)]"
                    }
                  `}
                  style={{ borderRadius: "var(--theme-checkout-stepper-node-radius, 9999px)" }}
                >
                  {isDone ? <Check className="w-3 h-3" /> : step.id}
                </div>

                {/* Trait droit */}
                {index < steps.length - 1 && (
                  <div
                    className={`
                      absolute left-1/2 right-0 h-px
                      ${step.id < currentStep ? "bg-[var(--theme-primary,#111827)]" : "bg-[var(--theme-border-default,#e5e7eb)]"}
                    `}
                  />
                )}
              </div>

              {/* Label */}
              <div
                className={`
                  mt-1 text-xxxs text-center
                  ${
                    isActive
                      ? "text-[var(--theme-heading-color,#111827)] font-medium"
                      : "text-[var(--theme-muted-color,#6b7280)]"
                  }
                `}
              >
                {step.label}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
