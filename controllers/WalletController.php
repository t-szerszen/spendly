<?php

class WalletController
{
    private $transactionModel;
    private $householdExpenseModel;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->householdExpenseModel = new HouseholdExpense();
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
        $householdMonthlyCost = $this->householdExpenseModel->getUserMonthlyHouseholdCost(
            $userId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n')
        );

        $data = [
            'title' => 'Portfel',
            'categories' => $categories,
            'transactions' => $transactions,
            'selectedMonth' => $month,
            'quickAddPath' => 'transaction/add',
            'quickAddRedirect' => 'wallet',
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance,
            ],
            'householdMonthlyCost' => $householdMonthlyCost,
        ];

        require_once __DIR__ . '/../views/wallet.php';
    }
}
