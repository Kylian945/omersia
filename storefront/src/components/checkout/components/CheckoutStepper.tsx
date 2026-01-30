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
    <div className="rounded-2xl bg-white border border-neutral-200 px-4 py-3">
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
                      ${step.id <= currentStep ? "bg-neutral-900" : "bg-neutral-200"}
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
                        ? "bg-black text-white border-black"
                        : isDone
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-400 border-neutral-300"
                    }
                  `}
                >
                  {isDone ? <Check className="w-3 h-3" /> : step.id}
                </div>

                {/* Trait droit */}
                {index < steps.length - 1 && (
                  <div
                    className={`
                      absolute left-1/2 right-0 h-px
                      ${step.id < currentStep ? "bg-neutral-900" : "bg-neutral-200"}
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
                      ? "text-neutral-900 font-medium"
                      : "text-neutral-500"
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
