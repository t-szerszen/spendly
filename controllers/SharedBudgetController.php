<?php

/**
 * Klasa SharedBudgetController
 *
 * Odpowiada za pełny przepływ wspólnych budżetów: listę budżetów, tworzenie,
 * zaproszenia, członkostwo, udziały procentowe oraz księgowanie spłat.
 * Wszystkie akcje operujące na konkretnym budżecie weryfikują dostęp użytkownika.
 *
 * Kontroler nie wykonuje ciężkiej matematyki rozliczeń samodzielnie. Deleguje ją do
 * modelu Settlement, a tutaj pilnuje głównie:
 * - czy użytkownik jest zalogowany,
 * - czy ma dostęp do budżetu,
 * - czy ma rolę ownera przy akcjach administracyjnych,
 * - czy dane z formularza są poprawne i nie zostały zmanipulowane.
 */
class SharedBudgetController
{
    /**
     * 100% zapisane w punktach bazowych.
     *
     * Jeden punkt procentowy to 100 punktów bazowych, więc:
     * - 1.00% = 100,
     * - 50.00% = 5000,
     * - 100.00% = 10000.
     *
     * Takie liczenie jest stabilniejsze niż sumowanie floatów typu 33.33 + 33.33 + 33.34.
     */
    private const SHARE_TOTAL_BASIS_POINTS = 10000;

    /**
     * Tolerancja 1 punktu bazowego, czyli 0.01 punktu procentowego.
     * Pozwala zaakceptować drobne różnice po zaokrągleniach formularza.
     */
    private const SHARE_TOTAL_TOLERANCE_BASIS_POINTS = 1;

    private $authService;
    private $sharedBudgetModel;
    private $memberModel;
    private $transactionModel;
    private $settlementModel;
    private $invitationModel;
    private $userModel;
    private $mailService;

    /**
     * Tworzy wszystkie zależności potrzebne kontrolerowi.
     *
     * Projekt używa prostego wzorca MVC bez kontenera DI, dlatego kontroler sam
     * instancjuje modele i serwisy, z których korzystają poszczególne akcje.
     */
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

    /**
     * Wyświetla listę wspólnych budżetów dostępnych dla zalogowanego użytkownika.
     *
     * Nie pobiera wszystkich budżetów z systemu, tylko te, w których użytkownik
     * występuje jako członek. Ograniczenie jest wykonywane w modelu findByUser().
     */
    public function index()
    {
        // Lista zawiera wyłącznie budżety, w których bieżący użytkownik jest członkiem.
        $this->requireLogin();

        $userId = $_SESSION['user_id'];
        $shared_budgets = $this->sharedBudgetModel->findByUser($userId);

        $data = [
            'title' => 'Wspólne budżety',
            'shared_budgets' => $shared_budgets,
        ];

        require_once __DIR__ . '/../views/shared_budgets/index.php';
    }

    /**
     * Pokazuje formularz tworzenia nowego wspólnego budżetu.
     *
     * Sama metoda nie zapisuje danych, tylko przygotowuje widok. Zapis odbywa się
     * w store(), ponieważ formularz wysyła request POST.
     */
    public function create()
    {
        // Formularz tworzenia nowego wspólnego budżetu.
        $this->requireLogin();

        $data = [
            'title' => 'Nowy wspólny budżet',
        ];

        require_once __DIR__ . '/../views/shared_budgets/create.php';
    }

    /**
     * Tworzy nowy wspólny budżet.
     *
     * Po utworzeniu budżetu obecny użytkownik zostaje dodany jako owner z udziałem 100%.
     * To daje sensowny stan początkowy: budżet ma administratora i suma udziałów wynosi 100%.
     */
    public function store()
    {
        // Tworzy budżet i przypisuje bieżącego użytkownika jako właściciela z udziałem 100%.
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets/create'));
            exit;
        }

        // Nazwa jest jedynym wymaganym polem przy tworzeniu budżetu.
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

        // Owner dostaje początkowo 100%, bo na starcie jest jedynym członkiem budżetu.
        $this->memberModel->addMember([
            'shared_budget_id' => $sharedBudgetId,
            'user_id' => $_SESSION['user_id'],
            'share_percent' => 100,
            'role' => 'owner',
        ]);

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&created=1'));
        exit;
    }

    /**
     * Wyświetla szczegóły jednego wspólnego budżetu.
     *
     * Widok zawiera członków, wydatki z wybranego miesiąca, salda, sugerowane przelewy,
     * historię spłat, zaproszenia oraz akcje administracyjne.
     */
    public function show()
    {
        // Widok szczegółowy obejmuje saldo członków, wydatki, spłaty i zarządzanie dostępem.
        $this->requireLogin();

        $sharedBudgetId = (int) ($_GET['id'] ?? 0);
        $period = $this->resolvePeriod($_GET['period'] ?? null);

        // Każda akcja na konkretnym budżecie musi potwierdzić członkostwo użytkownika.
        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $this->renderSharedBudget($sharedBudgetId, $period);
    }

    /**
     * Alias do show().
     *
     * Pozwala routerowi obsłużyć ścieżkę związaną z dodawaniem wydatku,
     * ale sam widok i dane są takie same jak w szczegółach budżetu.
     */
    public function addExpense()
    {
        $this->show();
    }

    /**
     * Wysyła zaproszenie do wspólnego budżetu.
     *
     * Akcja jest dostępna tylko dla ownera. Zaproszenie jest powiązane z adresem email,
     * ma jednorazowy token i termin ważności. Jeśli wysyłka maila się nie powiedzie,
     * rekord zaproszenia jest usuwany, żeby nie zostawić martwego tokenu.
     */
    public function invite()
    {
        // Zaproszenia może wysyłać wyłącznie właściciel budżetu.
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        $sharedBudgetId = (int) ($_POST['shared_budget_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $period = $this->resolvePeriod($_POST['period'] ?? null);

        // Najpierw sprawdzamy dostęp do budżetu, potem rolę administracyjną.
        if ($sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $_SESSION['user_id'])) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->currentUserIsOwner($sharedBudgetId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&access=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        // Email musi być syntaktycznie poprawny, bo później trafi do MailService.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'Podaj poprawny adres email.');
            return;
        }

        // Jeśli konto z tym mailem już należy do budżetu, zaproszenie byłoby zbędne.
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $this->sharedBudgetModel->userHasAccess($sharedBudgetId, $existingUser['id'])) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'Ten użytkownik jest już członkiem wspólnego budżetu.');
            return;
        }

        // Chroni przed wieloma aktywnymi zaproszeniami na ten sam adres.
        if ($this->invitationModel->emailAlreadyInvited($sharedBudgetId, $email)) {
            $this->renderSharedBudget($sharedBudgetId, $period, 'To zaproszenie jest już aktywne.');
            return;
        }

        // Token zaproszenia jest jednorazowym identyfikatorem ważnym przez siedem dni.
        $sharedBudget = $this->sharedBudgetModel->find($sharedBudgetId);
        $inviter = $this->userModel->findByEmail($_SESSION['email'] ?? '');
        $inviterName = $inviter ? trim($inviter['first_name'] . ' ' . $inviter['last_name']) : ($_SESSION['first_name'] ?? 'Użytkownik');

        // random_bytes(32) daje kryptograficznie losowe bajty, a bin2hex zamienia je
        // na bezpieczny tekstowy token do umieszczenia w linku.
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
            // Jeśli mail nie wyszedł, użytkownik nie ma jak użyć tokenu, więc czyścimy zaproszenie.
            $this->invitationModel->delete($invitationId);
            $this->renderSharedBudget($sharedBudgetId, $period, 'Nie udało się wysłać emaila. Spróbuj ponownie.');
            return;
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=sent' . $this->buildPeriodQuery($period)));
        exit;
    }

    /**
     * Obsługuje kliknięcie w link zaproszenia.
     *
     * Użytkownik musi być zalogowany na konto z takim samym adresem email jak adres
     * zaproszenia. Dzięki temu nie da się przyjąć cudzego zaproszenia po przechwyceniu linku.
     */
    public function acceptInvite()
    {
        // Akceptacja zaproszenia wymaga zalogowania na konto z adresem wskazanym w zaproszeniu.
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        if (!$this->authService->isLoggedIn()) {
            // Token jest zapamiętywany w sesji, żeby po logowaniu można było wrócić do akceptacji.
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

        // Zaproszenie musi być aktywne i nierozwiązane.
        if ($invite['status'] !== 'pending') {
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets/show?id=' . (int) $invite['shared_budget_id'] . '&invite=inactive'));
            exit;
        }

        $expiresAt = new DateTimeImmutable($invite['expires_at']);
        if ($expiresAt < new DateTimeImmutable('now')) {
            // Przeterminowane zaproszenie oznaczamy w bazie, żeby nie wisiało jako pending.
            $this->invitationModel->markExpired($invite['id']);
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets?invite=expired'));
            exit;
        }

        $currentUser = !empty($_SESSION['email'])
            ? $this->userModel->findByEmail($_SESSION['email'])
            : $this->userModel->findById($_SESSION['user_id']);

        // strcasecmp porównuje adresy bez rozróżniania wielkości liter.
        if (!$currentUser || strcasecmp($currentUser['email'], $invite['invited_email']) !== 0) {
            unset($_SESSION['pending_shared_budget_invite_token']);
            header('Location: ' . url('shared_budgets?invite=wrong-account'));
            exit;
        }

        if (!$this->sharedBudgetModel->userHasAccess($invite['shared_budget_id'], $currentUser['id'])) {
            // Nowy członek dostaje 0%, żeby owner musiał świadomie ustawić nowy podział udziałów.
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

    /**
     * Aktualizuje procentowe udziały członków w kosztach wspólnego budżetu.
     *
     * Metoda jest dostępna tylko dla ownera. Udziały muszą:
     * - być liczbami z maksymalnie dwoma miejscami po przecinku,
     * - mieścić się w zakresie 0-100,
     * - sumować się do 100% z tolerancją 0.01 punktu procentowego.
     *
     * Ważne: najpierw walidujemy wszystkie wartości, a dopiero potem zapisujemy.
     * Dzięki temu nie powstaje stan częściowo zaktualizowanych udziałów.
     */
    public function updateShares()
    {
        // Aktualizacja udziałów jest ograniczona do właściciela i wymaga sumy dokładnie 100%.
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

            // Formularz wysyła shares[user_id]. Jeśli dla członka brakuje wartości,
            // traktujemy to jak błąd zamiast zostawiać stary udział.
            $rawShare = trim((string) ($shares[$userId] ?? ''));

            // Użytkownik może wpisać 33,33 albo 33.33. W bazie i obliczeniach używamy kropki.
            $normalizedShare = str_replace(',', '.', $rawShare);

            // Regex dopuszcza tylko liczby nieujemne z maksymalnie dwoma miejscami po przecinku.
            if ($normalizedShare === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedShare)) {
                header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-number' . $this->buildPeriodQuery($period)));
                exit;
            }

            $share = (float) $normalizedShare;

            // Pojedynczy udział nie może być ujemny ani większy niż cały budżet.
            if ($share < 0 || $share > 100) {
                header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-range' . $this->buildPeriodQuery($period)));
                exit;
            }

            // Zamiana na punkty bazowe eliminuje typowe problemy floatów przy sumowaniu.
            // Przykład: 33.33% -> 3333, 100.00% -> 10000.
            $shareBasisPoints = (int) round($share * 100);
            $validatedShares[$userId] = $shareBasisPoints / 100;
            $totalBasisPoints += $shareBasisPoints;
        }

        // Suma udziałów musi dawać pełne 100%. Tolerancja 1 oznacza 0.01 punktu procentowego.
        if (abs($totalBasisPoints - self::SHARE_TOTAL_BASIS_POINTS) > self::SHARE_TOTAL_TOLERANCE_BASIS_POINTS) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=invalid-total' . $this->buildPeriodQuery($period)));
            exit;
        }

        // Zapis następuje dopiero po pełnej walidacji wszystkich członków.
        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $this->memberModel->updateShare($sharedBudgetId, $userId, $validatedShares[$userId]);
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&shares=updated' . $this->buildPeriodQuery($period)));
        exit;
    }

    /**
     * Księguje wykonaną spłatę między członkami budżetu.
     *
     * Formularz spłaty nie jest traktowany jako źródło prawdy. Kontroler ponownie
     * sprawdza aktualną sugestię w Settlement::findSuggestedTransfer() i pozwala
     * zaksięgować tylko przelew zgodny z bieżącymi saldami.
     */
    public function settle(): void
    {
        // Księguje spłatę tylko wtedy, gdy odpowiada aktualnie sugerowanemu przelewowi.
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

        // Bez poprawnego okresu nie wiadomo, dla którego miesiąca sprawdzić sugestię.
        if ($period === null || $sharedBudgetId <= 0 || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $currentUserId)) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        // Użytkownik może zaksięgować tylko spłatę wychodzącą od siebie.
        // To chroni przed podmienieniem hidden inputa from_user_id.
        if ($fromUserId !== $currentUserId) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=forbidden' . $this->buildPeriodQuery($period)));
            exit;
        }

        // Obie strony przelewu muszą nadal należeć do danego budżetu.
        if (!$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $fromUserId) || !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, $toUserId)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=invalid-member' . $this->buildPeriodQuery($period)));
            exit;
        }

        $selectedDate = DateTimeImmutable::createFromFormat('!Y-m', $period);

        // Kwota musi być dodatnia, a miesiąc poprawnie sparsowany.
        if ($selectedDate === false || $amount <= 0) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=invalid' . $this->buildPeriodQuery($period)));
            exit;
        }

        // Pobieramy aktualną sugestię dla dokładnie tej pary osób.
        // Jeśli w międzyczasie ktoś zaksięgował spłatę, sugestia mogła się zmienić.
        $suggestedTransfer = $this->settlementModel->findSuggestedTransfer(
            $sharedBudgetId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n'),
            $fromUserId,
            $toUserId
        );

        // Nie wolno zaksięgować kwoty większej niż aktualnie pozostała do zapłaty.
        // Mniejsza kwota jest dozwolona, bo użytkownik może wykonać częściową spłatę.
        if (!$suggestedTransfer || $amount > round((float) $suggestedTransfer['amount'], 2)) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=too-large' . $this->buildPeriodQuery($period)));
            exit;
        }

        try {
            // Model zapisuje settlement oraz dwie techniczne transakcje portfelowe.
            $this->settlementModel->post([
                'shared_budget_id' => $sharedBudgetId,
                'period_month' => $period,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'created_by' => $currentUserId,
                'transaction_date' => date('Y-m-d'),
                'payer_name' => $suggestedTransfer['from_name'],
                'counterparty_name' => $suggestedTransfer['to_name'],
            ]);
        } catch (Throwable $e) {
            // Błąd logujemy technicznie, a użytkownik dostaje ogólny komunikat przez redirect.
            error_log('Nie udało się zaksięgować rozliczenia: ' . $e->getMessage());
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=failed' . $this->buildPeriodQuery($period)));
            exit;
        }

        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&settlement=posted' . $this->buildPeriodQuery($period)));
        exit;
    }

    /**
     * Usuwa aktywne zaproszenie do budżetu.
     *
     * Dostępne tylko dla ownera. Dodatkowo sprawdzamy, czy zaproszenie faktycznie
     * należy do tego samego budżetu, który przyszedł w formularzu.
     */
    public function deleteInvitation(): void
    {
        // Anulowanie aktywnego zaproszenia jest akcją administracyjną właściciela.
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

        // Chroni przed usunięciem zaproszenia z innego budżetu przez podmianę id.
        if (!$invitation || (int) $invitation['shared_budget_id'] !== $sharedBudgetId) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->invitationModel->deleteInvitation($invitationId);
        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&invite=deleted' . $this->buildPeriodQuery($period)));
        exit;
    }

    /**
     * Pozwala obecnemu użytkownikowi opuścić wspólny budżet.
     *
     * Owner nie może wyjść, jeśli jest ostatnim ownerem, bo budżet zostałby bez osoby
     * mogącej zarządzać udziałami, zaproszeniami i członkami.
     */
    public function leave(): void
    {
        // Członek może opuścić budżet, z wyjątkiem ostatniego właściciela.
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

        // Zabezpieczenie przed pozostawieniem budżetu bez administratora.
        if ($currentMember['role'] === 'owner' && $this->memberModel->countOwners($sharedBudgetId) <= 1) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&leave=blocked-owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($sharedBudgetId, $userId);
        header('Location: ' . url('shared_budgets?leave=success'));
        exit;
    }

    /**
     * Usuwa innego członka ze wspólnego budżetu.
     *
     * Akcja administracyjna ownera. Nie pozwala usunąć siebie ani innego ownera,
     * żeby nie obejść zabezpieczeń z leave().
     */
    public function removeMember(): void
    {
        // Właściciel może usuwać zwykłych członków, ale nie siebie ani innych właścicieli.
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

        // Owner ma opuścić budżet przez leave(), gdzie działa kontrola ostatniego ownera.
        if ($memberUserId === (int) $_SESSION['user_id']) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=self' . $this->buildPeriodQuery($period)));
            exit;
        }

        $targetMember = $this->memberModel->getMember($sharedBudgetId, $memberUserId);
        if (!$targetMember) {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=not-found' . $this->buildPeriodQuery($period)));
            exit;
        }

        // Innego ownera nie usuwamy tą akcją, żeby nie zabrać komuś administracji przypadkiem.
        if ($targetMember['role'] === 'owner') {
            header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=owner' . $this->buildPeriodQuery($period)));
            exit;
        }

        $this->memberModel->deleteMember($sharedBudgetId, $memberUserId);
        header('Location: ' . url('shared_budgets/show?id=' . $sharedBudgetId . '&member=removed' . $this->buildPeriodQuery($period)));
        exit;
    }

    /**
     * Usuwa cały wspólny budżet.
     *
     * Dostępne wyłącznie dla ownera. Usunięcie samego budżetu powinno pociągnąć
     * powiązane rekordy zgodnie z relacjami w bazie danych/modelu.
     */
    public function delete(): void
    {
        // Usunięcie całego wspólnego budżetu jest dostępne tylko dla właściciela.
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

    /**
     * Składa wszystkie dane potrzebne do widoku szczegółów budżetu.
     *
     * To centralna metoda przygotowująca ekran show:
     * - normalizuje wybrany miesiąc,
     * - pobiera członków i zaproszenia,
     * - pobiera wydatki,
     * - liczy salda,
     * - pobiera sugerowane przelewy,
     * - pobiera historię zaksięgowanych spłat.
     *
     * @param int $sharedBudgetId ID wspólnego budżetu.
     * @param string|null $period Miesiąc w formacie YYYY-MM albo null dla bieżącego miesiąca.
     * @param string|null $error Opcjonalny komunikat błędu wyświetlany w widoku.
     */
    private function renderSharedBudget($sharedBudgetId, ?string $period = null, ?string $error = null)
    {
        // Centralne przygotowanie danych dla widoku szczegółów wspólnego budżetu.
        $sharedBudget = $this->sharedBudgetModel->find($sharedBudgetId);
        if (!$sharedBudget) {
            header('Location: ' . url('shared_budgets'));
            exit;
        }

        // Jeśli period nie został podany, widok pokazuje bieżący miesiąc.
        // Jeśli został podany, ale nie da się go sparsować, też wracamy do bieżącego miesiąca.
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

        // Sugestie są liczone na podstawie monthlyBalance, włącznie z już wykonanymi spłatami.
        $suggestedSettlements = $this->settlementModel->getSuggestedTransfers($sharedBudgetId, $year, $month);
        $settlements = $this->settlementModel->getByMonth($sharedBudgetId, $selectedPeriod);

        $data = [
            'title' => $sharedBudget['name'],
            'sharedBudget' => $sharedBudget,
            'members' => $members,
            'invitedUsers' => $invitedUsers,
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

    /**
     * Normalizuje miesiąc z formularza lub query stringa.
     *
     * Poprawny wynik to zawsze format YYYY-MM. Niepoprawna albo pusta wartość daje null,
     * a wywołująca metoda decyduje, czy przekierować użytkownika, czy użyć bieżącego miesiąca.
     */
    private function resolvePeriod(?string $period): ?string
    {
        // Normalizuje okres rozliczeniowy do formatu YYYY-MM.
        if (!$period) {
            return null;
        }

        $selectedDate = DateTimeImmutable::createFromFormat('Y-m', $period);
        if ($selectedDate === false) {
            return null;
        }

        return $selectedDate->format('Y-m');
    }

    /**
     * Buduje fragment query stringa zachowujący wybrany miesiąc po redirectach.
     *
     * Przykład:
     * - period = "2026-06" zwróci "&period=2026-06",
     * - period = null zwróci pusty string.
     */
    private function buildPeriodQuery(?string $period): string
    {
        // Zachowuje wybrany miesiąc podczas przekierowań po akcjach formularzy.
        return $period ? '&period=' . urlencode($period) : '';
    }

    /**
     * Sprawdza, czy obecny użytkownik jest ownerem danego budżetu.
     *
     * Dostęp do budżetu i rola ownera to dwie różne rzeczy:
     * - członek może oglądać budżet i księgować własne spłaty,
     * - owner może zarządzać udziałami, zaproszeniami i członkami.
     */
    private function currentUserIsOwner($sharedBudgetId): bool
    {
        // Sprawdza uprawnienia administracyjne bieżącego użytkownika w danym budżecie.
        $member = $this->memberModel->getMember($sharedBudgetId, (int) $_SESSION['user_id']);

        return $member && $member['role'] === 'owner';
    }

    /**
     * Wymaga zalogowanego użytkownika.
     *
     * Każda akcja wspólnych budżetów opiera się na $_SESSION['user_id'], więc bez sesji
     * nie kontynuujemy działania kontrolera i przekierowujemy na logowanie.
     */
    private function requireLogin(): void
    {
        // Wspólne budżety są dostępne wyłącznie po zalogowaniu.
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }
    }
}
