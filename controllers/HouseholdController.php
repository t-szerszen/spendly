<?php

class HouseholdController
{
    private $authService;
    private $householdModel;
    private $memberModel;
    private $expenseModel;
    private $invitationModel;
    private $categoryModel;
    private $userModel;
    private $mailService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->householdModel = new Household();
        $this->memberModel = new HouseholdMember();
        $this->expenseModel = new HouseholdExpense();
        $this->invitationModel = new HouseholdInvitation();
        $this->categoryModel = new Category();
        $this->userModel = new User();
        $this->mailService = new MailService();
    }

    public function index()
    {
        $this->requireLogin();

        $userId = $_SESSION['user_id'];
        $households = $this->householdModel->findByUser($userId);

        $data = [
            'title' => 'Gospodarstwa domowe',
            'households' => $households,
        ];

        require_once __DIR__ . '/../views/households/index.php';
    }

    public function create()
    {
        $this->requireLogin();

        $data = [
            'title' => 'Nowe gospodarstwo domowe',
        ];

        require_once __DIR__ . '/../views/households/create.php';
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households/create'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Nazwa gospodarstwa jest wymagana.';
            $data = ['title' => 'Nowe gospodarstwo domowe'];
            require_once __DIR__ . '/../views/households/create.php';
            return;
        }

        $householdId = $this->householdModel->create([
            'name' => $name,
            'created_by' => $_SESSION['user_id'],
        ]);

        $this->memberModel->addMember([
            'household_id' => $householdId,
            'user_id' => $_SESSION['user_id'],
            'share_percent' => 100,
            'role' => 'owner',
        ]);

        header('Location: ' . url('households/show?id=' . $householdId . '&created=1'));
        exit;
    }

    public function show()
    {
        $this->requireLogin();

        $householdId = (int) ($_GET['id'] ?? 0);
        $period = $this->resolvePeriod($_GET['period'] ?? null);
        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        $this->renderHousehold($householdId, $period);
    }

    public function addExpense()
    {
        $this->show();
    }

    public function invite()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->currentUserIsOwner($householdId)) {
            header('Location: ' . url('households/show?id=' . $householdId . '&access=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderHousehold($householdId, $period, 'Podaj poprawny adres email.');
            return;
        }

        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $this->householdModel->userHasAccess($householdId, $existingUser['id'])) {
            $this->renderHousehold($householdId, $period, 'Ten użytkownik jest już członkiem gospodarstwa.');
            return;
        }

        if ($this->invitationModel->emailAlreadyInvited($householdId, $email)) {
            $this->renderHousehold($householdId, $period, 'To zaproszenie jest już aktywne.');
            return;
        }

        $household = $this->householdModel->find($householdId);
        $inviter = $this->userModel->findByEmail($_SESSION['email'] ?? '');
        $inviterName = $inviter ? trim($inviter['first_name'] . ' ' . $inviter['last_name']) : ($_SESSION['first_name'] ?? 'Użytkownik');
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');

        $invitationId = $this->invitationModel->create([
            'household_id' => $householdId,
            'invited_email' => $email,
            'invite_token' => $token,
            'invited_by' => $_SESSION['user_id'],
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        $inviteUrl = absolute_url('households/accept?token=' . urlencode($token));
        $mailSent = $this->mailService->sendHouseholdInvitation(
            $email,
            $household['name'],
            $inviterName,
            $inviteUrl
        );

        if (!$mailSent) {
            $this->invitationModel->delete($invitationId);
            $this->renderHousehold($householdId, $period, 'Nie udało się wysłać emaila. Spróbuj ponownie.');
            return;
        }

        header('Location: ' . url('households/show?id=' . $householdId . '&invite=sent' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function acceptInvite()
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->authService->isLoggedIn()) {
            $_SESSION['pending_household_invite_token'] = $token;
            header('Location: ' . url('login'));
            exit;
        }

        $invite = $this->invitationModel->findByToken($token);
        if (!$invite) {
            unset($_SESSION['pending_household_invite_token']);
            header('Location: ' . url('households?invite=invalid'));
            exit;
        }

        if ($invite['status'] !== 'pending') {
            unset($_SESSION['pending_household_invite_token']);
            header('Location: ' . url('households/show?id=' . (int) $invite['household_id'] . '&invite=inactive'));
            exit;
        }

        $expiresAt = new DateTimeImmutable($invite['expires_at']);
        if ($expiresAt < new DateTimeImmutable('now')) {
            $this->invitationModel->markExpired($invite['id']);
            unset($_SESSION['pending_household_invite_token']);
            header('Location: ' . url('households?invite=expired'));
            exit;
        }

        $currentUser = !empty($_SESSION['email'])
            ? $this->userModel->findByEmail($_SESSION['email'])
            : $this->userModel->findById($_SESSION['user_id']);
        if (!$currentUser || strcasecmp($currentUser['email'], $invite['invited_email']) !== 0) {
            unset($_SESSION['pending_household_invite_token']);
            header('Location: ' . url('households?invite=wrong-account'));
            exit;
        }

        if (!$this->householdModel->userHasAccess($invite['household_id'], $currentUser['id'])) {
            $this->memberModel->addMember([
                'household_id' => $invite['household_id'],
                'user_id' => $currentUser['id'],
                'share_percent' => 0,
                'role' => 'member',
            ]);
        }

        $this->invitationModel->markAccepted($invite['id']);
        unset($_SESSION['pending_household_invite_token']);

        header('Location: ' . url('households/show?id=' . (int) $invite['household_id'] . '&invite=accepted'));
        exit;
    }

    public function updateShares()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);
        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->currentUserIsOwner($householdId)) {
            header('Location: ' . url('households/show?id=' . $householdId . '&shares=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $shares = $_POST['shares'] ?? [];
        $members = $this->memberModel->getMembers($householdId);

        $totalBasisPoints = 0;
        $validatedShares = [];
        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $rawShare = trim((string) ($shares[$userId] ?? ''));
            $normalizedShare = str_replace(',', '.', $rawShare);

            if ($normalizedShare === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedShare)) {
                header('Location: ' . url('households/show?id=' . $householdId . '&shares=invalid-number' . $this->buildPeriodQuery($period)));
                exit;
            }

            $share = (float) $normalizedShare;
            if ($share < 0 || $share > 100) {
                header('Location: ' . url('households/show?id=' . $householdId . '&shares=invalid-range' . $this->buildPeriodQuery($period)));
                exit;
            }

            $shareBasisPoints = (int) round($share * 100);
            $validatedShares[$userId] = $shareBasisPoints / 100;
            $totalBasisPoints += $shareBasisPoints;
        }

        if ($totalBasisPoints !== 10000) {
            header('Location: ' . url('households/show?id=' . $householdId . '&shares=invalid-total' . $this->buildPeriodQuery($period)));
            exit;
        }

        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $this->memberModel->updateShare($householdId, $userId, $validatedShares[$userId]);
        }

        header('Location: ' . url('households/show?id=' . $householdId . '&shares=updated' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function storeExpense()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $paidByUserId = (int) ($_POST['paid_by_user_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $expenseDate = $_POST['expense_date'] ?? '';
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        $members = $this->memberModel->getMembers($householdId);
        $memberIds = array_map('intval', array_column($members, 'user_id'));
        if ($paidByUserId <= 0 || !in_array($paidByUserId, $memberIds, true)) {
            $this->renderHousehold($householdId, $period, 'Wybierz poprawną osobę, która zapłaciła.');
            return;
        }

        if ($categoryId <= 0 || $amount <= 0 || $expenseDate === '') {
            $this->renderHousehold($householdId, $period, 'Uzupełnij poprawnie wszystkie pola wydatku.');
            return;
        }

        $parsedDate = DateTimeImmutable::createFromFormat('Y-m-d', $expenseDate);
        if ($parsedDate === false) {
            $this->renderHousehold($householdId, $period, 'Podaj poprawną datę wydatku.');
            return;
        }

        $this->expenseModel->create([
            'household_id' => $householdId,
            'paid_by_user_id' => $paidByUserId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'expense_date' => $expenseDate,
        ]);

        header('Location: ' . url('households/show?id=' . $householdId . '&period=' . $parsedDate->format('Y-m') . '&expense=added'));
        exit;
    }

    public function deleteInvitation(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $invitationId = (int) ($_POST['id'] ?? 0);
        $householdId = (int) ($_POST['household'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->currentUserIsOwner($householdId)) {
            header('Location: ' . url('households/show?id=' . $householdId . '&invite=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $invitation = $this->invitationModel->findById($invitationId);
        if (!$invitation || (int) $invitation['household_id'] !== $householdId) {
            header('Location: ' . url('households/show?id=' . $householdId . '&invite=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->invitationModel->deleteInvitation($invitationId);
        header('Location: ' . url('households/show?id=' . $householdId . '&invite=deleted' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function leave(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);
        $userId = (int) $_SESSION['user_id'];

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $userId)) {
            header('Location: ' . url('households'));
            exit;
        }

        $currentMember = $this->memberModel->getMember($householdId, $userId);
        if (!$currentMember) {
            header('Location: ' . url('households'));
            exit;
        }

        if ($currentMember['role'] === 'owner' && $this->memberModel->countOwners($householdId) <= 1) {
            header('Location: ' . url('households/show?id=' . $householdId . '&leave=blocked-owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($householdId, $userId);
        header('Location: ' . url('households?leave=success'));
        exit;
    }

    public function removeMember(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $memberUserId = (int) ($_POST['member_user_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->currentUserIsOwner($householdId)) {
            header('Location: ' . url('households/show?id=' . $householdId . '&member=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        if ($memberUserId === (int) $_SESSION['user_id']) {
            header('Location: ' . url('households/show?id=' . $householdId . '&member=self' . $this->buildPeriodQuery($period)));
            exit;
        }

        $targetMember = $this->memberModel->getMember($householdId, $memberUserId);
        if (!$targetMember) {
            header('Location: ' . url('households/show?id=' . $householdId . '&member=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        if ($targetMember['role'] === 'owner') {
            header('Location: ' . url('households/show?id=' . $householdId . '&member=owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($householdId, $memberUserId);
        header('Location: ' . url('households/show?id=' . $householdId . '&member=removed' . $this->buildPeriodQuery($period)));
        exit;
    }

    public function delete(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('households'));
            exit;
        }

        $householdId = (int) ($_POST['household_id'] ?? 0);
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        if ($householdId <= 0 || !$this->householdModel->userHasAccess($householdId, (int) $_SESSION['user_id'])) {
            header('Location: ' . url('households'));
            exit;
        }

        if (!$this->currentUserIsOwner($householdId)) {
            header('Location: ' . url('households/show?id=' . $householdId . '&delete=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->householdModel->delete($householdId);
        header('Location: ' . url('households?delete=success'));
        exit;
    }

    private function renderHousehold($householdId, ?string $period = null, ?string $error = null)
    {
        $household = $this->householdModel->find($householdId);
        if (!$household) {
            header('Location: ' . url('households'));
            exit;
        }

        $selectedDate = $period ? DateTimeImmutable::createFromFormat('Y-m', $period) : new DateTimeImmutable('first day of this month');
        if ($selectedDate === false) {
            $selectedDate = new DateTimeImmutable('first day of this month');
        }

        $year = (int) $selectedDate->format('Y');
        $month = (int) $selectedDate->format('n');
        $selectedPeriod = $selectedDate->format('Y-m');

        $members = $this->memberModel->getMembers($householdId);
        $currentMember = $this->memberModel->getMember($householdId, (int) $_SESSION['user_id']);
        $ownerCount = $this->memberModel->countOwners($householdId);
        $invitedUsers = $this->invitationModel->showInvitedToHousehold($householdId);
        $expenses = $this->expenseModel->getByMonth($householdId, $year, $month);
        $monthlyBalance = $this->expenseModel->getMonthlyBalance($householdId, $year, $month);
        $totalMonthExpense = $this->expenseModel->getTotalByMonth($householdId, $year, $month);
        $categories = $this->categoryModel->getCategories();

        $data = [
            'title' => $household['name'],
            'household' => $household,
            'members' => $members,
            'invitedUsers' =>$invitedUsers,
            'currentMember' => $currentMember,
            'canManageHousehold' => $currentMember && $currentMember['role'] === 'owner',
            'ownerCount' => $ownerCount,
            'expenses' => $expenses,
            'monthlyBalance' => $monthlyBalance,
            'totalMonthExpense' => $totalMonthExpense,
            'categories' => $categories,
            'selectedPeriod' => $selectedPeriod,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'error' => $error,
        ];

        require_once __DIR__ . '/../views/households/show.php';
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

    private function currentUserIsOwner($householdId): bool
    {
        $member = $this->memberModel->getMember($householdId, (int) $_SESSION['user_id']);

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
