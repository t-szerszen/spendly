<?php

class WalletController
{
    private $transactionModel;
    private $recurringTransactionModel;
    private $sharedBudgetModel;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        require_once __DIR__ . '/../models/RecurringTransaction.php';
        $this->transactionModel = new Transaction();
        $this->recurringTransactionModel = new RecurringTransaction();
        $this->sharedBudgetModel = new SharedBudget();
        $this->authService = new AuthService();
        $this->categoryModel = new Category();
    }

    public function show()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $this->recurringTransactionModel->generateDueForUser($userId, $this->transactionModel);
        $selectedDate = !empty($_GET['month'])
            ? DateTimeImmutable::createFromFormat('!Y-m', $_GET['month'])
            : false;

        if ($selectedDate === false) {
            $selectedDate = new DateTimeImmutable('first day of this month');
        }

        $month = $selectedDate->format('Y-m');
        $categories = $this->categoryModel->getCategories();
        $transactions = $this->transactionModel->getByMonth($userId, $month);
        $totalExpense = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'expense');
        $totalIncome = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'income');
        $balance = $totalIncome - $totalExpense;
        $sharedBudgetMonthlyCost = $this->transactionModel->getUserSharedBudgetCostByMonth(
            $userId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n')
        );
        $sharedBudgets = $this->sharedBudgetModel->findByUser($userId);

        $data = [
            'title' => 'Portfel',
            'categories' => $categories,
            'sharedBudgets' => $sharedBudgets,
            'transactions' => $transactions,
            'selectedMonth' => $month,
            'quickAddPath' => 'transaction/add',
            'quickAddRedirect' => 'wallet',
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance,
            ],
            'sharedBudgetMonthlyCost' => $sharedBudgetMonthlyCost,
        ];

        require_once __DIR__ . '/../views/wallet.php';
    }
}
