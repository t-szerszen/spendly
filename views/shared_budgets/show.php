<?php
$sharedBudget = $data['sharedBudget'];
$members = $data['members'];
$invitedUsers = $data['invitedUsers'];
$expenses = $data['expenses'];
$monthlyBalance = $data['monthlyBalance'];
$suggestedSettlements = $data['suggestedSettlements'] ?? [];
$settlements = $data['settlements'] ?? [];
$selectedPeriod = $data['selectedPeriod'];
$error = $data['error'] ?? null;
$currentMember = $data['currentMember'];
$canManageSharedBudget = (bool) ($data['canManageSharedBudget'] ?? false);
$ownerCount = (int) ($data['ownerCount'] ?? 0);
$memberCount = count($members);
$positiveBalances = array_filter($monthlyBalance, static function ($row) {
    return $row['balance'] > 0;
});
?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
<?php include comp('navDashboard.php'); ?>

<main class="auth-section shared_budgets-section">
    <div class="container shared_budgets-container">
        <div class="shared_budgets-header">
            <div>
                <h1 class="dashboard-title"><?= htmlspecialchars($sharedBudget['name']) ?></h1>
                <p class="shared_budgets-subtitle">Rozliczenie udziałów, sald i spłat za wybrany miesiąc.</p>
            </div>
            <a href="<?= url('shared_budgets') ?>" class="btn-secondary">Wróć do listy</a>
        </div>

        <?php if (!empty($_GET['created'])): ?>
            <div class="form-success">Wspólny budżet został utworzony.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'sent'): ?>
            <div class="form-success">Zaproszenie zostało wysłane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'accepted'): ?>
            <div class="form-success">Zaproszenie zostało zaakceptowane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'deleted'): ?>
            <div class="form-success">Zaproszenie zostało anulowane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'updated'): ?>
            <div class="form-success">Udziały zostały zapisane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['settlement']) && $_GET['settlement'] === 'posted'): ?>
            <div class="form-success">Rozliczenie zostało zaksięgowane w portfelach obu osób.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['expense']) && $_GET['expense'] === 'wallet-only'): ?>
            <div class="form-error">Wspólne wydatki dodawaj teraz z portfela, wybierając odpowiedni wspólny budżet.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'removed'): ?>
            <div class="form-success">Członek został usunięty ze wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="form-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'invalid-number'): ?>
            <div class="form-error">Każdy udział musi być poprawną liczbą.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'invalid-range'): ?>
            <div class="form-error">Każdy udział musi być w zakresie od 0 do 100.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'invalid-total'): ?>
            <div class="form-error">Suma udziałów wszystkich członków musi wynosić dokładnie 100%.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może edytować udziały.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['access']) && $_GET['access'] === 'forbidden'): ?>
            <div class="form-error">Nie masz uprawnień do tej akcji administracyjnej.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może usuwać zaproszenia.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'not-found'): ?>
            <div class="form-error">Nie znaleziono takiego zaproszenia.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['leave']) && $_GET['leave'] === 'blocked-owner'): ?>
            <div class="form-error">Nie możesz opuścić wspólnego budżetu jako jedyny owner.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może usuwać członków wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'self'): ?>
            <div class="form-error">Nie możesz usunąć samego siebie tym przyciskiem. Użyj opcji opuszczenia wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'owner'): ?>
            <div class="form-error">Nie możesz usunąć innego ownera ze wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'not-found'): ?>
            <div class="form-error">Nie znaleziono wskazanego członka wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['delete']) && $_GET['delete'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może usunąć cały wspólny budżet.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['settlement']) && $_GET['settlement'] === 'forbidden'): ?>
            <div class="form-error">Możesz zaksięgować tylko swoją spłatę.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['settlement']) && $_GET['settlement'] === 'invalid-member'): ?>
            <div class="form-error">Obie osoby muszą należeć do tego wspólnego budżetu.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['settlement']) && $_GET['settlement'] === 'too-large'): ?>
            <div class="form-error">Kwota spłaty jest większa niż aktualne zadłużenie.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['settlement']) && in_array($_GET['settlement'], ['invalid', 'failed'], true)): ?>
            <div class="form-error">Nie udało się zaksięgować rozliczenia. Spróbuj ponownie.</div>
        <?php endif; ?>

        <section class="auth-card shared_budgets-hero-card">
            <div class="sharedBudget-hero-copy">
                <p class="sharedBudget-eyebrow">Miesięczne rozliczenie</p>
                <h2><?= htmlspecialchars($selectedPeriod) ?></h2>
                <p>Ten ekran nie dodaje wydatków. Pokazuje, jak wspólne koszty z portfela rozkładają się między członków.</p>
            </div>
            <form action="<?= url('shared_budgets/show') ?>" method="GET" class="sharedBudget-period-form sharedBudget-period-card">
                <input type="hidden" name="id" value="<?= (int) $sharedBudget['id'] ?>">
                <label for="sharedBudget-period" class="sharedBudget-field-label">Wybrany miesiąc</label>
                <input id="sharedBudget-period" type="month" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>" class="auth-input">
                <button type="submit" class="btn-primary">Załaduj</button>
            </form>
        </section>

        <section class="sharedBudget-kpi-grid">
            <div class="auth-card sharedBudget-kpi-card">
                <span class="sharedBudget-kpi-label">Suma wydatków</span>
                <strong><?= number_format($data['totalMonthExpense'], 2) ?> zł</strong>
                <p>Łączna kwota wspólnych kosztów w tym miesiącu.</p>
            </div>
            <div class="auth-card sharedBudget-kpi-card">
                <span class="sharedBudget-kpi-label">Członkowie</span>
                <strong><?= $memberCount ?></strong>
                <p>Osoby aktualnie rozliczane w tym budżecie.</p>
            </div>
            <div class="auth-card sharedBudget-kpi-card">
                <span class="sharedBudget-kpi-label">Do zwrotu</span>
                <strong><?= count($positiveBalances) ?></strong>
                <p>Tyle osób wyłożyło więcej niż wynika z ich udziału.</p>
            </div>
            <div class="auth-card sharedBudget-kpi-card">
                <span class="sharedBudget-kpi-label">Twoja rola</span>
                <strong><?= htmlspecialchars($currentMember['role'] ?? 'member') ?></strong>
                <p><?= $canManageSharedBudget ? 'Możesz zarządzać udziałami, zaproszeniami i członkami.' : 'Masz dostęp do podglądu, rozliczeń i wyjścia ze wspólnego budżetu.' ?></p>
            </div>
        </section>

        <div class="shared_budgets-show-layout">
            <section class="shared_budgets-main-column">
                <div class="auth-card shared_budgets-summary-card sharedBudget-full-span">
                    <div class="sharedBudget-summary-row">
                        <div>
                            <h3>Bilans członków</h3>
                            <p>Każda karta pokazuje udział, wpłaty i końcowe saldo za wybrany miesiąc.</p>
                        </div>
                    </div>

                    <div class="sharedBudget-balance-grid">
                        <?php foreach ($monthlyBalance as $row): ?>
                            <div class="sharedBudget-balance-card">
                                <div class="sharedBudget-balance-head">
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <span class="sharedBudget-balance-share"><?= number_format($row['share_percent'], 2) ?>%</span>
                                </div>
                                <div class="sharedBudget-balance-metrics">
                                    <div>
                                        <span>Zapłacono</span>
                                        <strong><?= number_format($row['paid'], 2) ?> zł</strong>
                                    </div>
                                    <div>
                                        <span>Powinno pokryć</span>
                                        <strong><?= number_format($row['should_pay'], 2) ?> zł</strong>
                                    </div>
                                </div>
                                <p class="sharedBudget-balance-result <?= $row['balance'] >= 0 ? 'text-positive' : 'text-negative' ?>">
                                    Saldo netto: <?= number_format($row['balance'], 2) ?> zł
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-card shared_budgets-panel sharedBudget-full-span">
                    <div class="sharedBudget-section-heading">
                        <div>
                            <h3>Proponowane spłaty</h3>
                            <p>Minimalna lista przelewów potrzebna do wyrównania udziałów za wybrany miesiąc.</p>
                        </div>
                    </div>
                    <?php if (!empty($suggestedSettlements)): ?>
                        <div class="sharedBudget-settlement-list">
                            <?php foreach ($suggestedSettlements as $transfer): ?>
                                <div class="sharedBudget-settlement-row">
                                    <div>
                                        <strong><?= htmlspecialchars($transfer['from_name']) ?></strong>
                                        <span>powinien przelać do</span>
                                        <strong><?= htmlspecialchars($transfer['to_name']) ?></strong>
                                    </div>
                                    <div class="sharedBudget-settlement-action">
                                        <strong><?= number_format($transfer['amount'], 2) ?> zł</strong>
                                        <?php if ((int) $transfer['from_user_id'] === (int) $_SESSION['user_id']): ?>
                                            <form action="<?= url('shared_budgets/settle') ?>" method="POST">
                                                <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                                <input type="hidden" name="from_user_id" value="<?= (int) $transfer['from_user_id'] ?>">
                                                <input type="hidden" name="to_user_id" value="<?= (int) $transfer['to_user_id'] ?>">
                                                <input type="hidden" name="amount" value="<?= htmlspecialchars(number_format($transfer['amount'], 2, '.', '')) ?>">
                                                <button type="submit" class="btn-primary btn-small">Przelej</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="sharedBudget-empty-state">
                            <strong>Brak spłat do wykonania.</strong>
                            <p>Udziały są wyrównane albo w tym miesiącu nie ma jeszcze wspólnych wydatków.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card shared_budgets-panel sharedBudget-expenses-panel sharedBudget-full-span">
                    <div class="sharedBudget-section-heading">
                        <div>
                            <h3>Wspólne wydatki w tym miesiącu</h3>
                            <p>Historia płatności przypisanych z portfela do tego wspólnego budżetu.</p>
                        </div>
                    </div>

                    <?php if (!empty($expenses)): ?>
                        <table class="recent-transactions-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Data</th>
                                    <th class="text-left">Kto zapłacił</th>
                                    <th class="text-left">Kategoria</th>
                                    <th class="text-left">Opis</th>
                                    <th class="text-right">Kwota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                        <td><?= htmlspecialchars($expense['paid_by_first_name'] . ' ' . $expense['paid_by_last_name']) ?></td>
                                        <td><?= htmlspecialchars($expense['category_name']) ?></td>
                                        <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                                        <td class="text-right"><?= number_format($expense['amount'], 2) ?> zł</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="sharedBudget-empty-state">
                            <strong>Brak wydatków w tym okresie.</strong>
                            <p>Dodaj pierwszy wspólny koszt w portfelu i wybierz ten wspólny budżet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card shared_budgets-panel sharedBudget-full-span">
                    <div class="sharedBudget-section-heading">
                        <div>
                            <h3>Wykonane rozliczenia</h3>
                            <p>Lista zaksięgowanych spłat dla wybranego miesiąca.</p>
                        </div>
                    </div>
                    <?php if (!empty($settlements)): ?>
                        <div class="sharedBudget-settlement-list">
                            <?php foreach ($settlements as $settlement): ?>
                                <div class="sharedBudget-settlement-row">
                                    <div>
                                        <strong><?= htmlspecialchars($settlement['from_first_name'] . ' ' . $settlement['from_last_name']) ?></strong>
                                        <span>przelał do</span>
                                        <strong><?= htmlspecialchars($settlement['to_first_name'] . ' ' . $settlement['to_last_name']) ?></strong>
                                        <small><?= htmlspecialchars($settlement['status']) ?> • <?= htmlspecialchars($settlement['created_at']) ?></small>
                                    </div>
                                    <strong><?= number_format($settlement['amount'], 2) ?> zł</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="sharedBudget-empty-state">
                            <strong>Brak wykonanych rozliczeń.</strong>
                            <p>Po zaksięgowaniu spłaty pojawi się tutaj historia przelewów za ten miesiąc.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card shared_budgets-panel sharedBudget-full-span">
                    <div class="sharedBudget-section-heading">
                        <div>
                            <h3>Udziały członków</h3>
                            <p>Suma wszystkich udziałów musi wynosić dokładnie 100%.</p>
                        </div>
                    </div>
                    <?php if ($canManageSharedBudget): ?>
                        <form action="<?= url('shared_budgets/update-shares') ?>" method="POST" class="shared_budgets-shares-form">
                            <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                            <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                            <div class="shared_budgets-members-list">
                                <?php foreach ($members as $member): ?>
                                    <label class="sharedBudget-share-row">
                                        <span class="sharedBudget-share-person">
                                            <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                            <small><?= htmlspecialchars($member['role']) ?></small>
                                        </span>
                                        <div class="sharedBudget-share-input-wrap">
                                            <input
                                                type="number"
                                                step="1"
                                                min="0"
                                                max="100"
                                                name="shares[<?= (int) $member['user_id'] ?>]"
                                                value="<?= htmlspecialchars($member['share_percent']) ?>"
                                                class="auth-input"
                                            >
                                            <span>%</span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn-primary sharedBudget-shares-submit">Zapisz udziały</button>
                        </form>
                    <?php else: ?>
                        <div class="shared_budgets-members-list">
                            <?php foreach ($members as $member): ?>
                                <div class="sharedBudget-member-row sharedBudget-member-static">
                                    <span class="sharedBudget-share-person">
                                        <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                        <small><?= htmlspecialchars($member['role']) ?></small>
                                    </span>
                                    <strong><?= number_format($member['share_percent'], 2) ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card shared_budgets-panel sharedBudget-full-span">
                    <div class="sharedBudget-section-heading">
                        <div>
                            <h3>Zarządzanie członkami</h3>
                            <p>Lista osób z dostępem do wspólnych rozliczeń oraz aktywne zaproszenia.</p>
                        </div>
                    </div>
                    <?php if ($canManageSharedBudget): ?>
                        <div class="sharedBudget-manage-block">
                            <h4>Nowe zaproszenie</h4>
                            <form action="<?= url('shared_budgets/invite') ?>" method="POST" class="auth-form shared_budgets-form">
                                <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                <input type="email" name="email" placeholder="Adres email" required class="auth-input">
                                <button type="submit" class="btn-primary">Wyślij zaproszenie</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageSharedBudget): ?>
                        <div class="sharedBudget-invitations sharedBudget-manage-block">
                            <h4>Aktywne zaproszenia</h4>
                            <?php if (!empty($invitedUsers)): ?>
                                <div class="sharedBudget-invitation-list">
                                    <?php foreach ($invitedUsers as $invitedUser): ?>
                                        <div class="sharedBudget-invitation-row">
                                            <div>
                                                <strong><?= htmlspecialchars($invitedUser['invited_email']) ?></strong>
                                                <p>Wygasa: <?= htmlspecialchars($invitedUser['expires_at']) ?></p>
                                            </div>
                                            <form action="<?= url('shared_budgets/delete-invitation') ?>" method="POST">
                                                <input type="hidden" name="id" value="<?= (int) $invitedUser['id'] ?>">
                                                <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                                <button type="submit" class="btn-secondary btn-small">Anuluj</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Brak aktywnych zaproszeń.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="sharedBudget-manage-block">
                        <h4>Członkowie wspólnego budżetu</h4>
                        <div class="shared_budgets-members-list">
                            <?php foreach ($members as $member): ?>
                                <div class="sharedBudget-member-actions">
                                    <div>
                                        <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                        <p><?= htmlspecialchars($member['email']) ?> • <?= htmlspecialchars($member['role']) ?></p>
                                    </div>
                                    <?php if ($canManageSharedBudget && (int) $member['user_id'] !== (int) $_SESSION['user_id'] && $member['role'] !== 'owner'): ?>
                                        <form action="<?= url('shared_budgets/remove-member') ?>" method="POST">
                                            <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                            <input type="hidden" name="member_user_id" value="<?= (int) $member['user_id'] ?>">
                                            <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                            <button type="submit" class="btn-secondary btn-small">Usuń</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($currentMember): ?>
                        <div class="sharedBudget-leave-box sharedBudget-manage-block">
                            <h4>Opuszczenie wspólnego budżetu</h4>
                            <?php if ($currentMember['role'] === 'owner' && $ownerCount <= 1): ?>
                                <p>Jesteś jedynym ownerem. Nie możesz opuścić wspólnego budżetu, dopóki nie pojawi się inny owner.</p>
                            <?php else: ?>
                                <form action="<?= url('shared_budgets/leave') ?>" method="POST" class="sharedBudget-leave-form">
                                    <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                    <button type="submit" class="btn-secondary">Opuść wspólny budżet</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageSharedBudget): ?>
                        <div class="sharedBudget-danger-box sharedBudget-manage-block">
                            <h4>Usunięcie wspólnego budżetu</h4>
                            <p>Ta akcja usuwa cały wspólny budżet wraz z członkami, zaproszeniami i przypisanymi wydatkami.</p>
                            <form action="<?= url('shared_budgets/delete') ?>" method="POST" class="sharedBudget-leave-form">
                                <input type="hidden" name="shared_budget_id" value="<?= (int) $sharedBudget['id'] ?>">
                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                <button
                                    type="submit"
                                    class="btn-danger"
                                    data-confirm="Na pewno usunąć cały wspólny budżet?"
                                >Usuń wspólny budżet</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<?php include comp('footer.php'); ?>
<script src="<?= url('scripts/confirmActions.js') ?>"></script>
</body>
</html>
