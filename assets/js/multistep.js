console.log("JS LOADED");

(function bindNextButtonDebug() {
    function attach() {
        const nextBtn = document.getElementById("nextBtn");
        console.log("Button:", nextBtn);

        if (!nextBtn) {
            console.error("Next button NOT FOUND");
            return;
        }

        if (nextBtn.dataset.debugBound === "1") return;
        nextBtn.dataset.debugBound = "1";
        nextBtn.addEventListener("click", function () {
            console.log("Next button CLICKED");
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", attach);
    } else {
        attach();
    }
})();

window.initComplaintWizard = function (options) {
    const form = document.querySelector(options.formSelector);
    if (!form) {
        console.error("[wizard] form not found", options.formSelector);
        return null;
    }

    const progress = document.querySelector(options.progressSelector);
    const steps = Array.from(form.querySelectorAll(options.stepSelector));
    const nextButtons = Array.from(form.querySelectorAll(options.nextSelector));
    const prevButtons = Array.from(form.querySelectorAll(options.prevSelector));
    const submitButton = form.querySelector(options.submitSelector);
    let currentStep = Math.max(1, steps.findIndex(function (step) {
        return step.classList.contains("is-active") || step.classList.contains("active");
    }) + 1);

    function getCurrentStepElement() {
        return steps.find(function (step) {
            const stepNumber = Number(step.getAttribute("data-step"));
            return stepNumber === currentStep;
        }) || null;
    }

    function focusFirstInvalid(step) {
        if (!step) return;
        const invalidField = step.querySelector(".is-invalid, input:invalid, select:invalid, textarea:invalid");
        if (invalidField) invalidField.focus();
    }

    function updateProgressBar() {
        if (progress) progress.setAttribute("data-current-step", String(currentStep));
        steps.forEach(function (step, index) {
            const stepNumber = Number(step.getAttribute("data-step")) || (index + 1);
            const isActive = stepNumber === currentStep;
            step.classList.toggle("is-active", isActive);
            step.classList.toggle("active", isActive);
            step.hidden = !isActive;
        });
        if (progress) {
            progress.querySelectorAll(".progress-step").forEach(function (item) {
                const stepNumber = Number(item.getAttribute("data-step"));
                item.classList.toggle("is-complete", stepNumber < currentStep);
                item.classList.toggle("is-active", stepNumber === currentStep);
                item.classList.toggle("active", stepNumber === currentStep);
            });
        }
        if (submitButton) submitButton.disabled = currentStep !== steps.length;
    }

    function validateStep(step, stepNumber) {
        if (!step) return true;
        if (typeof options.onStepValidate === "function") {
            const valid = options.onStepValidate(stepNumber, step);
            if (!valid) {
                console.warn("[wizard] validation failed", stepNumber);
                focusFirstInvalid(step);
            }
            return valid;
        }

        let valid = true;
        step.querySelectorAll("input, select, textarea").forEach(function (field) {
            if (field.type === "hidden") return;
            if (!field.checkValidity()) {
                field.classList.add("is-invalid");
                valid = false;
            } else {
                field.classList.remove("is-invalid");
            }
        });

        if (!valid) {
            console.warn("[wizard] validation failed", stepNumber);
            focusFirstInvalid(step);
        }
        return valid;
    }

    function goToStep(stepNumber) {
        currentStep = Math.min(Math.max(stepNumber, 1), steps.length);
        updateProgressBar();
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function goToNextStep() {
        const current = getCurrentStepElement();
        if (!current) {
            console.error("[wizard] active step not found");
            return;
        }

        if (!validateStep(current, currentStep)) return;

        const next = steps.find(function (step) {
            return Number(step.getAttribute("data-step")) === currentStep + 1;
        });

        if (!next) return;

        current.classList.remove("is-active", "active");
        next.classList.add("is-active", "active");
        currentStep += 1;
        updateProgressBar();
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    nextButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            console.log("[wizard] next clicked", { currentStep: currentStep, id: button.id || null });
            goToNextStep();
        });
    });

    prevButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            console.log("[wizard] previous clicked", { currentStep: currentStep, id: button.id || null });
            goToStep(currentStep - 1);
        });
    });

    form.addEventListener("input", function (event) {
        if (event.target.matches("input, select, textarea")) {
            event.target.classList.remove("is-invalid");
        }
    });

    const api = {
        goToStep: goToStep,
        goToNextStep: goToNextStep,
        validateStep: validateStep,
        updateProgressBar: updateProgressBar
    };

    form.addEventListener("submit", function (event) {
        if (typeof options.onBeforeSubmit === "function" && !options.onBeforeSubmit.call(api)) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    updateProgressBar();
    return api;
};
