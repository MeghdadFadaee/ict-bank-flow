const loanForm = document.querySelector('#loan-form');

if (loanForm) {
    const lookupForm = document.querySelector('#lookup-form');
    const resultPanel = document.querySelector('#result-panel');
    const processButton = document.querySelector('#process-loan');
    const submitButton = document.querySelector('#submit-loan');
    const formMessage = document.querySelector('#form-message');
    const creditScore = document.querySelector('#credit-score');
    const creditScoreOutput = document.querySelector('#credit-score-output');
    let activeLoanId = null;

    const terminalStatuses = ['APPROVED', 'REJECTED', 'MANUAL_REVIEW'];
    const statusLabels = {
        SUBMITTED: 'Submitted',
        IN_PROGRESS: 'In progress',
        MANUAL_REVIEW: 'Manual review',
        APPROVED: 'Approved',
        REJECTED: 'Rejected',
    };
    const stageLabels = {
        VALIDATION: 'Validation',
        FRAUD_CHECK: 'Fraud check',
        GUARANTOR_CHECK: 'Guarantor check',
        CREDIT_CHECK: 'Credit check',
        MANAGER_APPROVAL: 'Manager approval',
    };

    const icons = {
        success: '<svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        pending: '<svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        stopped: '<svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3v.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    };

    creditScore.addEventListener('input', () => {
        creditScoreOutput.value = creditScore.value;
        creditScoreOutput.textContent = creditScore.value;
    });

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                ...(options.body ? {'Content-Type': 'application/json'} : {}),
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.error || 'The request could not be completed.');
        }

        return payload;
    };

    const showMessage = (message, type = 'error') => {
        formMessage.textContent = message;
        formMessage.className = `mt-5 rounded-xl px-3.5 py-3 text-sm ${type === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-teal-50 text-teal-800'}`;
    };

    const clearMessage = () => {
        formMessage.className = 'mt-5 hidden rounded-xl px-3.5 py-3 text-sm';
        formMessage.textContent = '';
    };

    const formatAmount = (value) => new Intl.NumberFormat('en-US').format(value);
    const formatTime = (value) => new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));

    const createTextElement = (tag, className, text) => {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;

        return element;
    };

    const renderSummary = (loan) => {
        const summary = document.querySelector('#loan-summary');
        const entries = [
            ['Customer', loan.customerId],
            ['Type', statusLabels[loan.loanType] || loan.loanType],
            ['Amount', loan.amount === undefined ? '—' : `${formatAmount(loan.amount)} IRR`],
            ['Credit score', loan.creditScore ?? '—'],
        ];
        summary.replaceChildren();

        entries.forEach(([label, value]) => {
            const card = document.createElement('div');
            card.className = 'rounded-xl bg-white/5 p-3';
            card.append(
                createTextElement('p', 'text-[10px] font-semibold uppercase tracking-wider text-slate-500', label),
                createTextElement('p', 'mt-1 truncate text-sm font-medium text-slate-100', value ?? '—'),
            );
            summary.append(card);
        });
    };

    const renderHistory = (history) => {
        const historyList = document.querySelector('#history-list');
        document.querySelector('#history-count').textContent = history.length ? `${history.length} stages` : 'Not processed yet';
        historyList.replaceChildren();

        if (!history.length) {
            const empty = createTextElement('li', 'rounded-xl border border-dashed border-white/15 px-4 py-5 text-center text-sm text-slate-500', 'Run the workflow to see each decision here.');
            historyList.append(empty);
            return;
        }

        history.forEach((entry) => {
            const item = document.createElement('li');
            item.className = 'grid grid-cols-[auto_1fr_auto] items-start gap-3 rounded-xl bg-white/5 px-3.5 py-3';
            const marker = createTextElement('span', `mt-1 size-2 rounded-full ${entry.result === 'PASS' ? 'bg-emerald-400' : entry.result === 'FAIL' ? 'bg-rose-400' : 'bg-amber-300'}`, '');
            const content = document.createElement('div');
            content.className = 'min-w-0';
            content.append(
                createTextElement('p', 'text-sm font-medium text-slate-100', stageLabels[entry.stage] || entry.stage),
                createTextElement('p', 'mt-0.5 truncate text-xs text-slate-500', entry.reason || 'Stage completed'),
            );
            item.append(marker, content, createTextElement('time', 'text-[10px] text-slate-500', formatTime(entry.timestamp)));
            historyList.append(item);
        });
    };

    const renderResult = (loan, history = []) => {
        const isApproved = loan.status === 'APPROVED';
        const isStopped = ['REJECTED', 'MANUAL_REVIEW'].includes(loan.status);
        activeLoanId = loan.loanId;

        document.querySelector('#result-eyebrow').textContent = terminalStatuses.includes(loan.status) ? 'Decision complete' : 'Application ready';
        document.querySelector('#result-title').textContent = statusLabels[loan.status] || loan.status;
        document.querySelector('#result-id').textContent = loan.loanId;
        document.querySelector('#result-icon').innerHTML = icons[isApproved ? 'success' : isStopped ? 'stopped' : 'pending'];
        processButton.disabled = terminalStatuses.includes(loan.status);
        processButton.textContent = terminalStatuses.includes(loan.status) ? 'Workflow complete' : 'Run decision workflow';
        renderSummary(loan);
        renderHistory(history);
        resultPanel.classList.remove('hidden');
        resultPanel.scrollIntoView({behavior: 'smooth', block: 'start'});
    };

    const loadLoan = async (loanId) => {
        const [loan, history] = await Promise.all([
            request(`/api/v1/loans/${encodeURIComponent(loanId)}`),
            request(`/api/v1/loans/${encodeURIComponent(loanId)}/history`),
        ]);
        renderResult(loan, history);
    };

    loanForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage();

        if (!loanForm.reportValidity()) {
            return;
        }

        const formData = new FormData(loanForm);
        const payload = {
            customerId: formData.get('customerId'),
            amount: Number(formData.get('amount')),
            phone: formData.get('phone'),
            loanType: formData.get('loanType'),
            monthlyIncome: Number(formData.get('monthlyIncome')),
            creditScore: Number(formData.get('creditScore')),
            hasGuarantor: formData.has('hasGuarantor'),
        };

        submitButton.disabled = true;
        submitButton.querySelector('span').textContent = 'Submitting…';

        try {
            const createdLoan = await request('/api/v1/loans', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            document.querySelector('#lookup-loan-id').value = createdLoan.loanId;
            await loadLoan(createdLoan.loanId);
        } catch (error) {
            showMessage(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.querySelector('span').textContent = 'Submit application';
        }
    });

    lookupForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const loanId = new FormData(lookupForm).get('loanId').trim();

        if (!loanId) {
            document.querySelector('#lookup-loan-id').focus();
            return;
        }

        try {
            await loadLoan(loanId);
        } catch (error) {
            showMessage(error.message);
        }
    });

    processButton.addEventListener('click', async () => {
        if (!activeLoanId) {
            return;
        }

        processButton.disabled = true;
        processButton.textContent = 'Processing…';

        try {
            await request(`/api/v1/loans/${encodeURIComponent(activeLoanId)}/process`, {method: 'POST'});
            await loadLoan(activeLoanId);
        } catch (error) {
            showMessage(error.message);
            processButton.disabled = false;
            processButton.textContent = 'Run decision workflow';
        }
    });
}
