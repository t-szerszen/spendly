<?php

/**
 * Model Settlement
 *
 * Odpowiada za wyliczanie sald członków wspólnego budżetu,
 * proponowanie minimalnych spłat oraz księgowanie wykonanych rozliczeń w portfelach.
 *
 * Najważniejsza zasada modelu:
 * - zwykłe wydatki wspólnego budżetu mają entry_kind = "standard",
 * - techniczne wpisy spłat mają entry_kind = "settlement_out" albo "settlement_in",
 * - dlatego spłaty nie zwiększają ponownie sumy kosztów wspólnego budżetu.
 */
class Settlement
{
    /**
     * Nazwa kategorii używanej dla technicznych transakcji tworzonych przy spłacie.
     * Kategoria jest tworzona automatycznie, jeśli nie istnieje jeszcze w tabeli categories.
     */
    private const CATEGORY_NAME = 'Rozliczenie wspolnego budzetu';

    private $db;
    private $memberModel;

    public function __construct()
    {
        // Model pracuje bezpośrednio na PDO oraz korzysta z modelu członków,
        // bo do rozliczeń potrzebne są udziały procentowe wszystkich uczestników.
        $this->db = Database::getInstance()->getConnection();
        $this->memberModel = new SharedBudgetMember();
    }

    /**
     * Zwraca historię zaksięgowanych spłat dla jednego budżetu i jednego miesiąca.
     *
     * $periodMonth ma format YYYY-MM, np. "2026-06".
     * JOIN na tabelę users występuje dwa razy, bo settlement ma dwie strony:
     * - from_user_id: osoba oddająca pieniądze,
     * - to_user_id: osoba otrzymująca pieniądze.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param string $periodMonth Miesiąc rozliczeniowy w formacie YYYY-MM.
     * @return array Lista settlementów z danymi obu użytkowników.
     */
    public function getByMonth($sharedBudgetId, $periodMonth): array
    {
        // Pobiera historię zaksięgowanych spłat dla wskazanego miesiąca.
        $stmt = $this->db->prepare(
            'SELECT s.*,
                    from_user.first_name AS from_first_name,
                    from_user.last_name AS from_last_name,
                    to_user.first_name AS to_first_name,
                    to_user.last_name AS to_last_name
             FROM settlements s
             JOIN users from_user ON from_user.id = s.from_user_id
             JOIN users to_user ON to_user.id = s.to_user_id
             WHERE s.shared_budget_id = ?
               AND s.period_month = ?
             ORDER BY s.created_at DESC, s.id DESC'
        );
        $stmt->execute([$sharedBudgetId, $periodMonth]);

        return $stmt->fetchAll();
    }

    /**
     * Liczy miesięczne saldo netto każdego członka budżetu.
     *
     * Saldo jest podstawą do późniejszego wygenerowania proponowanych przelewów.
     * Dla każdego członka liczymy:
     *
     * balance = paid - should_pay + settlementAdjustment
     *
     * gdzie:
     * - paid: ile ta osoba faktycznie zapłaciła zwykłymi wydatkami,
     * - should_pay: ile powinna ponieść według swojego udziału procentowego,
     * - settlementAdjustment: korekta za już zaksięgowane spłaty.
     *
     * Interpretacja wyniku:
     * - balance > 0: osoba wyłożyła za dużo i powinna dostać zwrot,
     * - balance < 0: osoba zapłaciła za mało i powinna komuś oddać,
     * - balance około 0: osoba jest rozliczona.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param int $year Rok, np. 2026.
     * @param int $month Miesiąc 1-12.
     * @return array Lista członków z kwotą zapłaconą, należną i saldem.
     */
    public function getMonthlyBalance($sharedBudgetId, $year, $month): array
    {
        // Łączy wydatki, udziały członków i wykonane spłaty w saldo netto każdej osoby.
        $total = $this->getSharedExpenseTotal($sharedBudgetId, $year, $month);
        $paidByUserId = $this->getPaidByUser($sharedBudgetId, $year, $month);
        $settlementTotals = $this->getPostedSettlementTotals($sharedBudgetId, sprintf('%04d-%02d', $year, $month));
        $settlementAdjustments = $this->getPostedSettlementAdjustments($sharedBudgetId, sprintf('%04d-%02d', $year, $month));
        $members = $this->memberModel->getMembers($sharedBudgetId);

        $balance = [];
        foreach ($members as $member) {
            $userId = (int) $member['user_id'];

            // Jeśli użytkownik nie ma żadnego wydatku w danym miesiącu, nie pojawi się
            // w wyniku GROUP BY z getPaidByUser(), więc używamy 0 jako wartości domyślnej.
            $paid = (float) ($paidByUserId[$userId] ?? 0);

            // Udział jest zapisany jako procent, np. 30.00, więc dzielimy przez 100.
            // Zaokrąglenie do 2 miejsc odpowiada kwotom pieniężnym w złotówkach i groszach.
            $shouldPay = round($total * ((float) $member['share_percent'] / 100), 2);

            // Te dwie wartości są głównie do pokazania w widoku:
            // ile użytkownik już przelał oraz ile otrzymał w ramach settlementów.
            $transferredOut = (float) ($settlementTotals[$userId]['transferred_out'] ?? 0);
            $receivedIn = (float) ($settlementTotals[$userId]['received_in'] ?? 0);

            // Korekta wpływa na realne pozostałe saldo. Dłużnik po zapłacie zbliża się
            // do zera, a wierzyciel po otrzymaniu pieniędzy też zbliża się do zera.
            $settlementAdjustment = (float) ($settlementAdjustments[$userId] ?? 0);

            // Zaksięgowane spłaty zmniejszają pozostały dług bez zmiany pierwotnych kosztów.
            $balance[] = [
                'user_id' => $userId,
                'name' => trim($member['first_name'] . ' ' . $member['last_name']),
                'share_percent' => (float) $member['share_percent'],
                'paid' => $paid,
                'should_pay' => $shouldPay,
                'transferred_out' => $transferredOut,
                'received_in' => $receivedIn,
                'balance' => round($paid - $shouldPay + $settlementAdjustment, 2),
            ];
        }

        return $balance;
    }

    /**
     * Buduje listę sugerowanych przelewów między członkami budżetu.
     *
     * Algorytm jest zachłanny:
     * 1. Pobiera salda z getMonthlyBalance().
     * 2. Osoby z saldem ujemnym trafiają do dłużników.
     * 3. Osoby z saldem dodatnim trafiają do wierzycieli.
     * 4. Bierze pierwszego dłużnika i pierwszego wierzyciela.
     * 5. Tworzy przelew na mniejszą z dwóch kwot:
     *    - ile dłużnik ma jeszcze oddać,
     *    - ile wierzyciel ma jeszcze dostać.
     * 6. Zmniejsza pozostałe kwoty i przechodzi dalej, gdy któraś strona dojdzie do zera.
     *
     * Próg 0.01 chroni przed przelewami na groszowe resztki po zaokrągleniach.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param int $year Rok, np. 2026.
     * @param int $month Miesiąc 1-12.
     * @return array Lista sugerowanych transferów: from_user_id, to_user_id, amount.
     */
    public function getSuggestedTransfers($sharedBudgetId, $year, $month): array
    {
        // Buduje minimalną listę przelewów między dłużnikami i wierzycielami.
        $balances = $this->getMonthlyBalance($sharedBudgetId, $year, $month);
        $debtors = [];
        $creditors = [];

        foreach ($balances as $row) {
            $balance = round((float) $row['balance'], 2);

            // Saldo ujemne oznacza, że użytkownik zapłacił mniej niż powinien.
            if ($balance < -0.01) {
                $debtors[] = [
                    'user_id' => (int) $row['user_id'],
                    'name' => $row['name'],
                    'amount' => abs($balance),
                ];
            // Saldo dodatnie oznacza, że użytkownik wyłożył za dużo i powinien dostać zwrot.
            } elseif ($balance > 0.01) {
                $creditors[] = [
                    'user_id' => (int) $row['user_id'],
                    'name' => $row['name'],
                    'amount' => $balance,
                ];
            }
        }

        $transfers = [];
        $debtorIndex = 0;
        $creditorIndex = 0;

        while (isset($debtors[$debtorIndex], $creditors[$creditorIndex])) {
            // Kwota przelewu nie może przekroczyć ani pozostałego długu dłużnika,
            // ani pozostałej należności wierzyciela.
            $amount = round(min($debtors[$debtorIndex]['amount'], $creditors[$creditorIndex]['amount']), 2);

            if ($amount > 0.01) {
                $transfers[] = [
                    'from_user_id' => $debtors[$debtorIndex]['user_id'],
                    'from_name' => $debtors[$debtorIndex]['name'],
                    'to_user_id' => $creditors[$creditorIndex]['user_id'],
                    'to_name' => $creditors[$creditorIndex]['name'],
                    'amount' => $amount,
                ];
            }

            // Po utworzeniu przelewu zmniejszamy obie strony o tę samą kwotę.
            $debtors[$debtorIndex]['amount'] = round($debtors[$debtorIndex]['amount'] - $amount, 2);
            $creditors[$creditorIndex]['amount'] = round($creditors[$creditorIndex]['amount'] - $amount, 2);

            // Jeśli dłużnik jest już rozliczony, przechodzimy do kolejnego dłużnika.
            if ($debtors[$debtorIndex]['amount'] <= 0.01) {
                $debtorIndex++;
            }

            // Jeśli wierzyciel dostał już pełną należność, przechodzimy do kolejnego wierzyciela.
            if ($creditors[$creditorIndex]['amount'] <= 0.01) {
                $creditorIndex++;
            }
        }

        return $transfers;
    }

    /**
     * Szuka jednej konkretnej sugestii przelewu.
     *
     * Kontroler używa tej metody przed zaksięgowaniem spłaty. Dzięki temu użytkownik
     * nie może wysłać formularza z dowolną parą osób ani kwotą większą niż aktualnie
     * wynika z sald. Najpierw generujemy aktualne sugestie, potem szukamy przelewu
     * fromUserId -> toUserId.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param int $year Rok, np. 2026.
     * @param int $month Miesiąc 1-12.
     * @param int $fromUserId Osoba oddająca pieniądze.
     * @param int $toUserId Osoba otrzymująca pieniądze.
     * @return array|null Znaleziona sugestia albo null, jeśli taki przelew nie jest aktualnie sugerowany.
     */
    public function findSuggestedTransfer($sharedBudgetId, $year, $month, $fromUserId, $toUserId): ?array
    {
        // Weryfikuje pojedynczą spłatę względem aktualnych sugestii rozliczenia.
        foreach ($this->getSuggestedTransfers($sharedBudgetId, $year, $month) as $transfer) {
            if ((int) $transfer['from_user_id'] === $fromUserId && (int) $transfer['to_user_id'] === $toUserId) {
                return $transfer;
            }
        }

        return null;
    }

    /**
     * Księguje wykonaną spłatę.
     *
     * Jedno rozliczenie zapisuje trzy rzeczy:
     * 1. Rekord w tabeli settlements jako historia spłat.
     * 2. Wydatek w portfelu osoby płacącej (settlement_out).
     * 3. Przychód w portfelu osoby otrzymującej (settlement_in).
     *
     * Wszystko jest objęte transakcją DB. Jeśli nie uda się utworzyć któregoś wpisu,
     * rollback cofa całość, żeby nie powstało pół rozliczenia.
     *
     * @param array $data Dane spłaty przygotowane w kontrolerze.
     * @return int ID utworzonego rekordu settlements.
     * @throws Throwable Gdy zapis do bazy się nie powiedzie.
     */
    public function post(array $data): int
    {
        // Księgowanie spłaty tworzy rekord settlement oraz dwie odpowiadające mu transakcje portfelowe.
        $this->db->beginTransaction();

        try {
            // Techniczne transakcje settlementowe muszą mieć kategorię.
            // Metoda sama ją znajdzie albo utworzy.
            $categoryId = $this->getSettlementCategoryId();

            $stmt = $this->db->prepare(
                'INSERT INTO settlements
                    (shared_budget_id, period_month, from_user_id, to_user_id, amount, status, created_by)
                 VALUES (?, ?, ?, ?, ?, "posted", ?)'
            );
            $stmt->execute([
                $data['shared_budget_id'],
                $data['period_month'],
                $data['from_user_id'],
                $data['to_user_id'],
                $data['amount'],
                $data['created_by'],
            ]);

            $settlementId = (int) $this->db->lastInsertId();
            $description = 'Rozliczenie ' . $data['period_month'] . ' ' . $data['counterparty_name'];

            // Rozliczenie jest kompletne dopiero po utworzeniu wpisu wychodzącego i przychodzącego.
            // Wpis wychodzący jest wydatkiem osoby, która oddaje pieniądze.
            $this->insertSettlementTransaction([
                'user_id' => $data['from_user_id'],
                'shared_budget_id' => $data['shared_budget_id'],
                'related_user_id' => $data['to_user_id'],
                'settlement_id' => $settlementId,
                'category_id' => $categoryId,
                'amount' => $data['amount'],
                'type' => 'expense',
                'entry_kind' => 'settlement_out',
                'description' => $description,
                'date' => $data['transaction_date'],
            ]);

            // Wpis przychodzący jest przychodem osoby, która otrzymuje zwrot.
            $this->insertSettlementTransaction([
                'user_id' => $data['to_user_id'],
                'shared_budget_id' => $data['shared_budget_id'],
                'related_user_id' => $data['from_user_id'],
                'settlement_id' => $settlementId,
                'category_id' => $categoryId,
                'amount' => $data['amount'],
                'type' => 'income',
                'entry_kind' => 'settlement_in',
                'description' => 'Rozliczenie ' . $data['period_month'] . ' ' . $data['payer_name'],
                'date' => $data['transaction_date'],
            ]);

            $this->db->commit();

            return $settlementId;
        } catch (Throwable $e) {
            // Rollback jest potrzebny, bo metoda wykonuje kilka INSERTów.
            // Bez tego mogłoby zostać settlement bez odpowiadających transakcji portfelowych.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Sumuje zwykłe wydatki wspólnego budżetu w danym miesiącu.
     *
     * Liczymy tylko:
     * - type = "expense", czyli wydatki,
     * - entry_kind = "standard", czyli prawdziwe koszty wspólnego budżetu.
     *
     * Nie liczymy settlement_out ani settlement_in, bo są to techniczne wpisy spłat.
     * Gdyby je doliczyć, spłata sztucznie zwiększałaby sumę kosztów.
     *
     * COALESCE(SUM(amount), 0) zwraca 0, jeśli w danym miesiącu nie ma żadnych wydatków.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param int $year Rok.
     * @param int $month Miesiąc.
     * @return float Suma standardowych wydatków.
     */
    private function getSharedExpenseTotal($sharedBudgetId, $year, $month): float
    {
        // Suma obejmuje wyłącznie standardowe wydatki wspólnego budżetu.
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM transactions
             WHERE shared_budget_id = ?
               AND type = "expense"
               AND entry_kind = "standard"
               AND YEAR(date) = ?
               AND MONTH(date) = ?'
        );
        $stmt->execute([$sharedBudgetId, $year, $month]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Zwraca sumę standardowych wydatków zapłaconych przez każdego użytkownika.
     *
     * Wynik jest mapą:
     * [
     *     user_id => suma_zaplacona_przez_tego_uzytkownika
     * ]
     *
     * Użytkownik, który nic nie zapłacił w danym miesiącu, nie pojawi się w wyniku SQL.
     * Dlatego getMonthlyBalance() używa później wartości domyślnej 0.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param int $year Rok.
     * @param int $month Miesiąc.
     * @return array Mapa user_id => paid.
     */
    private function getPaidByUser($sharedBudgetId, $year, $month): array
    {
        // Agreguje kwoty faktycznie opłacone przez poszczególnych członków.
        $stmt = $this->db->prepare(
            'SELECT user_id, COALESCE(SUM(amount), 0) AS paid
             FROM transactions
             WHERE shared_budget_id = ?
               AND type = "expense"
               AND entry_kind = "standard"
               AND YEAR(date) = ?
               AND MONTH(date) = ?
             GROUP BY user_id'
        );
        $stmt->execute([$sharedBudgetId, $year, $month]);

        $paidByUserId = [];
        foreach ($stmt->fetchAll() as $row) {
            // Kluczem tablicy jest ID użytkownika, żeby później szybko odczytać sumę po user_id.
            $paidByUserId[(int) $row['user_id']] = (float) $row['paid'];
        }

        return $paidByUserId;
    }

    /**
     * Liczy korekty salda wynikające z już zaksięgowanych spłat.
     *
     * Przykład:
     * - Bartek ma saldo -300,
     * - Anna ma saldo +300,
     * - Bartek płaci Annie 100.
     *
     * Korekta powinna dać:
     * - Bartek: +100, czyli -300 + 100 = -200,
     * - Anna: -100, czyli +300 - 100 = +200.
     *
     * Dlatego from_user_id dostaje korektę dodatnią, a to_user_id korektę ujemną.
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param string $periodMonth Miesiąc w formacie YYYY-MM.
     * @return array Mapa user_id => korekta salda.
     */
    private function getPostedSettlementAdjustments($sharedBudgetId, $periodMonth): array
    {
        // Korekty salda wynikają z już zaksięgowanych spłat w danym miesiącu.
        $adjustments = [];

        foreach ($this->getByMonth($sharedBudgetId, $periodMonth) as $settlement) {
            if ($settlement['status'] !== 'posted') {
                continue;
            }

            $fromUserId = (int) $settlement['from_user_id'];
            $toUserId = (int) $settlement['to_user_id'];
            $amount = (float) $settlement['amount'];

            // Osoba płacąca zmniejsza swój dług, więc jej saldo idzie w górę.
            $adjustments[$fromUserId] = ($adjustments[$fromUserId] ?? 0) + $amount;

            // Osoba otrzymująca pieniądze zmniejsza swoją należność, więc jej saldo idzie w dół.
            $adjustments[$toUserId] = ($adjustments[$toUserId] ?? 0) - $amount;
        }

        return $adjustments;
    }

    /**
     * Sumuje już wykonane spłaty osobno jako "wysłane" i "otrzymane".
     *
     * Te dane są prezentacyjne. getMonthlyBalance() używa ich do pokazania w widoku,
     * ile użytkownik już przelał i ile otrzymał, ale właściwa korekta salda jest
     * liczona w getPostedSettlementAdjustments().
     *
     * @param int|string $sharedBudgetId ID wspólnego budżetu.
     * @param string $periodMonth Miesiąc w formacie YYYY-MM.
     * @return array Mapa user_id => ['transferred_out' => float, 'received_in' => float].
     */
    private function getPostedSettlementTotals($sharedBudgetId, $periodMonth): array
    {
        // Sumy rozliczeń są prezentowane osobno jako przelewy wychodzące i otrzymane.
        $totals = [];

        foreach ($this->getByMonth($sharedBudgetId, $periodMonth) as $settlement) {
            if ($settlement['status'] !== 'posted') {
                continue;
            }

            $fromUserId = (int) $settlement['from_user_id'];
            $toUserId = (int) $settlement['to_user_id'];
            $amount = (float) $settlement['amount'];

            // Inicjalizacja obu stron przelewu pozwala bezpiecznie dodawać kwoty.
            if (!isset($totals[$fromUserId])) {
                $totals[$fromUserId] = [
                    'transferred_out' => 0.0,
                    'received_in' => 0.0,
                ];
            }

            if (!isset($totals[$toUserId])) {
                $totals[$toUserId] = [
                    'transferred_out' => 0.0,
                    'received_in' => 0.0,
                ];
            }

            // from_user_id to osoba, która wysłała spłatę.
            $totals[$fromUserId]['transferred_out'] += $amount;

            // to_user_id to osoba, która otrzymała spłatę.
            $totals[$toUserId]['received_in'] += $amount;
        }

        return $totals;
    }

    /**
     * Pobiera lub tworzy kategorię dla technicznych transakcji rozliczeniowych.
     *
     * Dzięki temu metoda post() nie musi zakładać, że taka kategoria istnieje po instalacji.
     * Przy pierwszym settlementcie kategoria zostanie utworzona automatycznie.
     *
     * @return int ID kategorii "Rozliczenie wspolnego budzetu".
     */
    private function getSettlementCategoryId(): int
    {
        // Systemowa kategoria rozliczeń jest tworzona automatycznie przy pierwszym użyciu.
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $stmt->execute([self::CATEGORY_NAME]);
        $categoryId = $stmt->fetchColumn();

        if ($categoryId !== false) {
            return (int) $categoryId;
        }

        // Typ kategorii jest "expense", bo kategoria musi istnieć dla wpisu wychodzącego.
        // Ten sam category_id jest też używany dla technicznego wpisu przychodzącego.
        $insertStmt = $this->db->prepare('INSERT INTO categories (name, type) VALUES (?, "expense")');
        $insertStmt->execute([self::CATEGORY_NAME]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Dodaje pojedynczą techniczną transakcję związaną z rozliczeniem.
     *
     * Metoda jest prywatna, bo pojedynczy wpis nie powinien powstawać samodzielnie.
     * Publiczna metoda post() zawsze tworzy parę:
     * - settlement_out dla płacącego,
     * - settlement_in dla odbiorcy.
     *
     * @param array $data Dane technicznej transakcji portfelowej.
     */
    private function insertSettlementTransaction(array $data): void
    {
        // Techniczny wpis portfelowy powiązany z zaksięgowaną spłatą.
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
                (user_id, shared_budget_id, related_user_id, settlement_id, category_id, amount, type, entry_kind, description, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['user_id'],
            $data['shared_budget_id'],
            $data['related_user_id'],
            $data['settlement_id'],
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['entry_kind'],
            $data['description'],
            $data['date'],
        ]);
    }
}
