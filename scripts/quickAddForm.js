document.querySelectorAll('.quick-add-form').forEach((form) => {
    const typeSelect = form.querySelector('.quick-add-type');
    const categorySelect = form.querySelector('.quick-add-category');
    const budgetSelect = form.querySelector('.quick-add-budget');
    const budgetWrap = form.querySelector('.quick-add-budget-wrap');
    const scopeFieldset = form.querySelector('.quick-add-scope');
    const scopeInputs = form.querySelectorAll('input[name="scope"]');
    const sharedInput = form.querySelector('input[name="scope"][value="shared"]');
    const sharedCopy = form.querySelector('.quick-add-shared-copy');
    const recurringToggle = form.querySelector('input[name="transaction_mode"][value="recurring"]');
    const recurringFields = form.querySelectorAll('.quick-add-recurring-field');
    const frequencySelect = form.querySelector('.quick-add-frequency');
    const endDateInput = form.querySelector('.quick-add-end-date');
    const dateLabel = form.querySelector('.quick-add-date-label');

    if (!typeSelect || !categorySelect || !budgetSelect || !budgetWrap) {
        return;
    }

    const syncCategoryState = () => {
        const selectedType = typeSelect.value;
        const categoryOptions = categorySelect.querySelectorAll('option[data-type]');

        categoryOptions.forEach((option) => {
            const matchesType = option.dataset.type === selectedType;
            option.hidden = !matchesType;
            option.disabled = !matchesType;
        });

        const selectedOption = categorySelect.selectedOptions[0];
        if (
            selectedOption &&
            selectedOption.dataset.type &&
            selectedOption.dataset.type !== selectedType
        ) {
            categorySelect.value = '';
        }
    };

    const syncBudgetState = () => {
        const isExpense = typeSelect.value === 'expense';
        const checkedScope = form.querySelector('input[name="scope"]:checked');
        const selectedScope = checkedScope ? checkedScope.value : 'private';
        const sharedAvailable = sharedInput && sharedInput.dataset.empty !== '1';
        const canUseShared = isExpense && sharedAvailable;
        const isShared = selectedScope === 'shared' && canUseShared;

        budgetWrap.hidden = !isShared;
        budgetSelect.disabled = !isShared;
        budgetSelect.required = isShared;

        if (sharedInput) {
            sharedInput.disabled = !isExpense || sharedInput.dataset.empty === '1';
        }

        if (scopeFieldset) {
            scopeFieldset.classList.toggle('is-income', !isExpense);
        }

        if (!isShared) {
            budgetSelect.value = '';
        }

        if (!isExpense && sharedInput) {
            form.querySelector('input[name="scope"][value="private"]').checked = true;
        }

        if (sharedCopy) {
            sharedCopy.hidden = !isShared;
        }
    };

    const syncRecurringState = () => {
        const isRecurring = recurringToggle && recurringToggle.checked === true;

        form.classList.toggle('is-recurring', isRecurring);

        recurringFields.forEach((field) => {
            field.style.display = isRecurring ? '' : 'none';
        });

        if (recurringToggle) {
            const repeatToggle = recurringToggle.closest('.quick-add-repeat-toggle');
            if (repeatToggle) {
                repeatToggle.classList.toggle('is-recurring', isRecurring);
            }
        }

        if (frequencySelect) {
            frequencySelect.disabled = !isRecurring;
            frequencySelect.required = isRecurring;
        }

        if (endDateInput) {
            endDateInput.disabled = !isRecurring;
            if (!isRecurring) {
                endDateInput.value = '';
            }
        }

        if (dateLabel) {
            dateLabel.textContent = isRecurring ? 'Data początkowa' : 'Data';
        }
    };

    if (sharedInput && sharedInput.disabled) {
        sharedInput.dataset.empty = '1';
    }

    typeSelect.addEventListener('change', syncBudgetState);
    typeSelect.addEventListener('change', syncCategoryState);
    scopeInputs.forEach((input) => input.addEventListener('change', syncBudgetState));
    if (recurringToggle) {
        recurringToggle.addEventListener('change', syncRecurringState);
    }
    syncBudgetState();
    syncCategoryState();
    syncRecurringState();
});
