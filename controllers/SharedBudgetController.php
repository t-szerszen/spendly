<?php

class SharedBudgetController
{
    private $authService;
    private $sharedBudgetModel;
    private $memberModel;
    private $transactionModel;
    private $settlementModel;
    private $invitationModel;
    private $userModel;
    private $mailService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->sharedBudgetModel = new SharedBudget();
        $this->memberModel = new SharedBudgetMember();
        $this->transactionModel = new Transaction();
        $this->settlementModel = new Settlement();
        $this->invitationModel = new SharedBudgetInvitation();
        $this->userModel = new User();
        $this->mailService = new MailService();
    }

    public function index()
    {
        $this->requireLogin();

        $userId = $_SESSION['user_id'];
        $shared_budgets = $this->sharedBudgetModel->findByUser($userId);

        $data = [
            'title' => 'Wspólne budżety',
            'shared_budgets' => $shared_budgets,
        ];

        require_once __DIR__ . '/../views/shared_budgets/index.php';
    }

    public function create()
    {
        $this->requireLogin();

        $data = [
            'title' => 'Nowy wspólny budżet',
        ];

        require_once __DIR__ . '/../views/shared_budgets/create.php';
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets/create'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Nazwa wspólnego budżetu jest wymagana.';
            $data = ['title' => 'Nowy wspólny budżet'];
            require_once __DIR__ . '/../views/shared_budgets/create.php';
            return;
        }

        $sharedBudgetId = $this->sharedBudgetModel->create([
            'name' => $name,
            'created_by' => $_SESSION['user_id'],
        ]);

        $this->memberModel->addMember([
            'shared_budget_id' => $sharedBudgetId,
            'user_id' => $_SESSION['user_id'],
            'share_percent' => 100,
            'role' => 'owner',
        ]);

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&created=1'));
        exit;
    }

    public function show()
    {
        $this->requireLogin();

        $sharedBudgetId = (int) ($_GET['id'] ?? 0);
        $period = $this->resolvePeriod($_GET['period'] ?? null);
        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $this->renderSharedBudget($sharedBudgetId, $period);
    }

    public function addExpense()
    {
        $this->show();
    }

    public function invite()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&access=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'Podaj poprawny adres email.');
            return;
        }

        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $this->sharedBudgetModel->userHasAccess($sharedBudgetId, $existingUser['id'])) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'Ten użytkownik jest już członkiem wspólnego budżetu.');
            return;
        }

        if ($this->invitationModel->emailAlreadyInvited($sharedBudgetId, $email)) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'To zaproszenie jest już aktywne.');
            return;
        }

        $sharedBudget = $this->sharedBudgetModel->find($sharedBudgetId);
        $inviter = $this->userModel->findByEmail($_SESSION['email'] ?? '');
        $inviterName = $inviter ? trim($inviter['first_name'] . ' ' . $inviter['last_name']) : ($_SESSION['first_name'] ?? 'Użytkownik');
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');

        $invitationId = $this->invitationModel->create([
            'shared_budget_id' => $sharedBudgetId,
            'invited_email' => $email,
            'invite_token' => $token,
            'invited_by' => $_SESSION['user_id'],
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        $inviteUrl = absolute_url('shared_budgets/accept?token=' . urlencode($token));
        $mailSent = $this->mailService->sendSharedBudgetInvitation(
            $email,
            $sharedBudget['name'],
            $inviterName,
            $inviteUrl
        );

        if (!$mailSent) {
            $this->invitationModel->delete($invitationId);
            $this->renderSharedBudget($sharedBudgetId, $period, 'Nie udało się wysłać emaila. Spróbuj ponownie.');
            return;
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=sent' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function acceptInvite()
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->authService->isLoggedIn()) {
            $_SESSION['pending_shared_budget_invite_token'] = $token;
            header('Location: ' . url('login'));
            exit;
        }

        $invite = $this->invitationModel->findByToken($token);
        if (!$invite) {
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets?invite=invalid'));
            exit;
        }

        if ($invite['status'] !== 'pending') {
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets/show?id=' . (int) $invite['shared_budget_id'] . '&invite=inactive'));
            exit;
        }

        $expiresAt = new DateTimeImmutable($invite['expires_at']);
        if ($expiresAt < new DateTimeImmutable('now')) {
            $this->invitationModel->markExpired($invite['id']);
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets?invite=expired'));
            exit;
        }

        $currentUser = !empty($_SESSION['email'])
            ? $this->userModel->findByEmail($_SESSION['email'])
            : $this->userModel->findById($_SESSION['user_id']);
        if (!$currentUser || strcasecmp($currentUser['email'], $invite['invited_email']) !== 0) {
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets?invite=wrong-account'));
            exit;
        }

        if (!$this->sharedBudgetModel->userHasAccess($invite['shared_budget_id'], $currentUser['id'])) {
            $this->memberModel->addMember([
                'shared_budget_id' => $invite['shared_budget_id'],
                'user_id' => $currentUser['id'],
                'share_percent' => 0,
                'role' => 'member',
            ]);
        }

        $this->invitationModel->markAccepted($invite['id']);
        unset($_SESSION['pending_shared_budget_invite_token']);

        header('Location: ' . url('shared_budgets/show?id=' . (int) $invite['shared_budget_id'] . '&invite=accepted'));
        exit;
    }

    public function updateShares()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);
        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $shares = $_POST['shares'] ?? [];
        $members = $this->memberModel->getMembers($sharedBudgetId);

        $totalBasisPoints = 0;
        $validatedShares = [];
        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $rawShare = trim((string) ($shares[$userId] ?? ''));
            $normalizedShare = str_replace(',', '.', $rawShare);

            if ($normalizedShare === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedShare)) {
                header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-number' . $this->buildPeriodQuery($period)));
                exit;
            }

            $share = (float) $normalizedShare;
            if ($share < 0 || $share > 100) {
                header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-range' . $this->buildPeriodQuery($period)));
                exit;
            }

            $shareBasisPoints = (int) round($share * 100);
            $validatedShares[$userId] = $shareBasisPoints / 100;
            $totalBasisPoints += $shareBasisPoints;
        }

        if ($totalBasisPoints !== 10000) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-total' . $this->buildPeriodQuery($period)));
            exit;
        }

        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $this->memberModel->updateShare($sharedBudgetId, $userId, $validatedShares[$userId]);
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=updated' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function settle(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $fromUserId = (int) ($_POST['from_user_id'] ?? 0);
        $toUserId = (int) ($_POST['to_user_id'] ?? 0);
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $period = $this->resolvePeriod($_POST['period'] ?? null);
        $currentUserId = (int) $_SESSION['user_id'];

        if ($period === null || $sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $currentUserId)) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if ($fromUserId !== $currentUserId) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        if (!$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $fromUserId) || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $toUserId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=invalid-member' . $this->buildPeriodQuery($period)));
            exit;
        }

        $selectedDate = DateTimeImmutable::createFromFormat('!Y-m', $period);
        if ($selectedDate === false || $amount <= 0) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=invalid' . $this->buildPeriodQuery($period)));
            exit;
        }

        $suggestedTransfer = $this->settlementModel->findSuggestedTransfer(
            $sharedBudgetId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n'),
            $fromUserId,
            $toUserId
        );

        if (!$suggestedTransfer || $amount > round((float) $suggestedTransfer['amount'], 2)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=too-large' . $this->buildPeriodQuery($period)));
            exit;
        }

        try {
            $this->settlementModel->post([
                'shared_budget_id' => $sharedBudgetId,
                'period_month' => $period,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'created_by' => $currentUserId,
                'transaction_date' => $period . '-01',
                'payer_name' => $suggestedTransfer['from_name'],
                'counterparty_name' => $suggestedTransfer['to_name'],
            ]);
        } catch (Throwable $e) {
            error_log('Nie udało się zaksięgować rozliczenia: ' . $e->getMessage());
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=failed' . $this->buildPeriodQuery($period)));
            exit;
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=posted' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function deleteInvitation(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $invitationId = (int) ($_POST['id'] ?? 0);
        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $invitation = $this->invitationModel->findById($invitationId);
        if (!$invitation || (int) $invitation['shared_budget_id'] !== $sharedBudgetId) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->invitationModel->deleteInvitation($invitationId);
        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=deleted' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function leave(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);
        $userId = (int) $_SESSION['user_id'];

        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $userId)) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $currentMember = $this->memberModel->getMember($sharedBudgetId, $userId);
        if (!$currentMember) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if ($currentMember['role'] === 'owner' && $this->memberModel->countOwners($sharedBudgetId) <= 1) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&leave=blocked-owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($sharedBudgetId, $userId);
        header('Location: ' . url('shared_budgets?leave=success'));
        exit;
    }

    public function removeMember(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $memberUserId = (int) ($_POST['member_user_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        if ($memberUserId === (int) $_SESSION['user_id']) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=self' . $this->buildPeriodQuery($period)));
            exit;
        }

        $targetMember = $this->memberModel->getMember($sharedBudgetId, $memberUserId);
        if (!$targetMember) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        if ($targetMember['role'] === 'owner') {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($sharedBudgetId, $memberUserId);
        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=removed' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function delete(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, (int) $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&delete=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->sharedBudgetModel->delete($sharedBudgetId);
        header('Location: ' . url('shared_budgets?delete=success'));
        exit;
    }

    private function renderSharedBudget($sharedBudgetId, ?string $period = null, ?string $error = null)
    {
        $sharedBudget = $this->sharedBudgetModel->find($sharedBudgetId);
        if (!$sharedBudget) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $selectedDate = $period ? DateTimeImmutable::createFromFormat('Y-m', $period) : new DateTimeImmutable('first day of this month');
        if ($selectedDate === false) {
            $selectedDate = new DateTimeImmutable('first day of this month');
        }

        $year = (int) $selectedDate->format('Y');
        $month = (int) $selectedDate->format('n');
        $selectedPeriod = $selectedDate->format('Y-m');

        $members = $this->memberModel->getMembers($sharedBudgetId);
        $currentMember = $this->memberModel->getMember($sharedBudgetId, (int) $_SESSION['user_id']);
        $ownerCount = $this->memberModel->countOwners($sharedBudgetId);
        $invitedUsers = $this->invitationModel->showInvitedToSharedBudget($sharedBudgetId);
        $expenses = $this->transactionModel->getSharedBudgetExpensesByMonth($sharedBudgetId, $year, $month);
        $monthlyBalance = $this->settlementModel->getMonthlyBalance($sharedBudgetId, $year, $month);
        $totalMonthExpense = $this->transactionModel->getSharedBudgetTotalByMonth($sharedBudgetId, $year, $month);
        $suggestedSettlements = $this->settlementModel->getSuggestedTransfers($sharedBudgetId, $year, $month);
        $settlements = $this->settlementModel->getByMonth($sharedBudgetId, $selectedPeriod);

        $data = [
            'title' => $sharedBudget['name'],
            'sharedBudget' => $sharedBudget,
            'members' => $members,
            'invitedUsers' =>$invitedUsers,
            'currentMember' => $currentMember,
            'canManageSharedBudget' => $currentMember && $currentMember['role'] === 'owner',
            'ownerCount' => $ownerCount,
            'expenses' => $expenses,
            'monthlyBalance' => $monthlyBalance,
            'totalMonthExpense' => $totalMonthExpense,
            'suggestedSettlements' => $suggestedSettlements,
            'settlements' => $settlements,
            'selectedPeriod' => $selectedPeriod,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'error' => $error,
        ];

        require_once __DIR__ . '/../views/shared_budgets/show.php';
    }

    private function resolvePeriod(?string $period): ?string
    {
        if (!$period) {
            return null;
        }

        $selectedDate = DateTimeImmutable::createFromFormat('Y-m', $period);
        if ($selectedDate === false) {
            return null;
        }

        return $selectedDate->format('Y-m');
    }

    private function buildPeriodQuery(?string $period): string
    {
        return $period ? '&period=' . urlencode($period) : '';
    }

    private function currentUserIsOwner($sharedBudgetId): bool
    {
        $member = $this->memberModel->getMember($sharedBudgetId, (int) $_SESSION['user_id']);

        return $member && $member['role'] === 'owner';
    }

    private function requireLogin(): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }
    }
}
