window.initComplaintWizard = function (options) {
    const form = document.querySelector(options.formSelector);
    if (!form) return null;

    const progress = document.querySelector(options.progressSelector);
    const steps = Array.from(form.querySelectorAll(options.stepSelector));
    const nextButtons = form.querySelectorAll(options.nextSelector);
    const prevButtons = form.querySelectorAll(options.prevSelector);
    const submitButton = form.querySelector(options.submitSelector);
    let currentStep = 1;

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function syncProgress() {
        if (progress) progress.setAttribute("data-current-step", String(currentStep));
        steps.forEach(function (step) {
            const stepNumber = Number(step.getAttribute("data-step"));
            step.classList.toggle("is-active", stepNumber === currentStep);
        });
        if (progress) {
            progress.querySelectorAll(".progress-step").forEach(function (item) {
                const stepNumber = Number(item.getAttribute("data-step"));
                item.classList.toggle("is-complete", stepNumber < currentStep);
                item.classList.toggle("is-active", stepNumber === currentStep);
            });
        }
        if (submitButton) submitButton.disabled = currentStep !== steps.length;
        scrollToTop();
    }

    function validateStep(activeStep, stepNumber) {
        if (!activeStep) return true;
        if (typeof options.onStepValidate === "function") {
            return options.onStepValidate(stepNumber, activeStep);
        }
        let valid = true;
        activeStep.querySelectorAll("input, select, textarea").forEach(function (field) {
            if (field.type === "hidden") return;
            if (!field.checkValidity()) {
                field.classList.add("is-invalid");
                valid = false;
            } else {
                field.classList.remove("is-invalid");
            }
        });
        return valid;
    }

    function goToStep(stepNumber) {
        currentStep = Math.min(Math.max(stepNumber, 1), steps.length);
        syncProgress();
    }

    nextButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const activeStep = steps.find(function (step) { return Number(step.getAttribute("data-step")) === currentStep; });
            if (!validateStep(activeStep, currentStep)) return;
            goToStep(currentStep + 1);
        });
    });

    prevButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            goToStep(currentStep - 1);
        });
    });

    form.addEventListener("input", function (event) {
        if (event.target.matches("input, select, textarea")) {
            event.target.classList.remove("is-invalid");
        }
    });

    const api = { goToStep: goToStep, validateStep: validateStep };

    form.addEventListener("submit", function (event) {
        if (typeof options.onBeforeSubmit === "function" && !options.onBeforeSubmit.call(api)) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    syncProgress();
    return api;
};
