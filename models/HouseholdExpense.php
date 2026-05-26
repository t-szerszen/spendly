<?php

class HouseholdExpense
{
    private $db;
    private $memberModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->memberModel = new HouseholdMember();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO household_expenses
                (household_id, paid_by_user_id, category_id, amount, description, expense_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $data['household_id'],
            $data['paid_by_user_id'],
            $data['category_id'],
            $data['amount'],
            $data['description'],
            $data['expense_date'],
        ]);
    }

    public function getByMonth($householdId, $year, $month)
    {
        $stmt = $this->db->prepare(
            'SELECT he.*,
                    u.first_name AS paid_by_first_name,
                    u.last_name AS paid_by_last_name,
                    c.name AS category_name
             FROM household_expenses he
             JOIN users u ON u.id = he.paid_by_user_id
             JOIN categories c ON c.id = he.category_id
             WHERE he.household_id = ?
               AND YEAR(he.expense_date) = ?
               AND MONTH(he.expense_date) = ?
             ORDER BY he.expense_date DESC, he.id DESC'
        );
        $stmt->execute([$householdId, $year, $month]);

        return $stmt->fetchAll();
    }

    public function getTotalByMonth($householdId, $year, $month)
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM household_expenses
             WHERE household_id = ?
               AND YEAR(expense_date) = ?
               AND MONTH(expense_date) = ?'
        );
        $stmt->execute([$householdId, $year, $month]);

        return (float) $stmt->fetchColumn();
    }

    public function getMonthlyBalance($householdId, $year, $month)
    {
        $total = $this->getTotalByMonth($householdId, $year, $month);
        $members = $this->memberModel->getMembers($householdId);

        $stmt = $this->db->prepare(
            'SELECT paid_by_user_id, COALESCE(SUM(amount), 0) AS paid
             FROM household_expenses
             WHERE household_id = ?
               AND YEAR(expense_date) = ?
               AND MONTH(expense_date) = ?
             GROUP BY paid_by_user_id'
        );
        $stmt->execute([$householdId, $year, $month]);
        $paidRows = $stmt->fetchAll();

        $paidByUserId = [];
        foreach ($paidRows as $row) {
            $paidByUserId[$row['paid_by_user_id']] = (float) $row['paid'];
        }

        $balance = [];
        foreach ($members as $member) {
            $paid = (float) ($paidByUserId[$member['user_id']] ?? 0);
            $shouldPay = $total * ((float) $member['share_percent'] / 100);

            $balance[] = [
                'user_id' => (int) $member['user_id'],
                'name' => trim($member['first_name'] . ' ' . $member['last_name']),
                'share_percent' => (float) $member['share_percent'],
                'paid' => $paid,
                'should_pay' => $shouldPay,
                'balance' => $paid - $shouldPay,
            ];
        }

        return $balance;
    }

    public function getUserMonthlyHouseholdCost($userId, $year, $month)
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(he.amount * (hm.share_percent / 100)), 0)
             FROM household_expenses he
             JOIN household_members hm ON hm.household_id = he.household_id AND hm.user_id = ?
             WHERE YEAR(he.expense_date) = ?
               AND MONTH(he.expense_date) = ?'
        );
        $stmt->execute([$userId, $year, $month]);

        return (float) $stmt->fetchColumn();
    }
}
