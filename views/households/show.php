<?php
$household = $data['household'];
$members = $data['members'];
$invitedUsers = $data['invitedUsers'];
$expenses = $data['expenses'];
$monthlyBalance = $data['monthlyBalance'];
$categories = $data['categories'];
$selectedPeriod = $data['selectedPeriod'];
$error = $data['error'] ?? null;
$currentMember = $data['currentMember'];
$canManageHousehold = (bool) ($data['canManageHousehold'] ?? false);
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

<main class="auth-section households-section">
    <div class="container households-container">
        <div class="households-header">
            <div>
                <h1 class="dashboard-title"><?= htmlspecialchars($household['name']) ?></h1>
                <p class="households-subtitle">Wspólne wydatki, udziały i rozliczenie za wybrany miesiąc.</p>
            </div>
            <a href="<?= url('households') ?>" class="btn-secondary">Wróć do listy</a>
        </div>

        <?php if (!empty($_GET['created'])): ?>
            <div class="form-success">Gospodarstwo zostało utworzone.</div>
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
        <?php if (!empty($_GET['expense']) && $_GET['expense'] === 'added'): ?>
            <div class="form-success">Wydatek został zapisany.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'removed'): ?>
            <div class="form-success">Członek został usunięty z gospodarstwa.</div>
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
            <div class="form-error">Nie możesz opuścić gospodarstwa jako jedyny owner.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może usuwać członków gospodarstwa.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'self'): ?>
            <div class="form-error">Nie możesz usunąć samego siebie tym przyciskiem. Użyj opcji opuszczenia gospodarstwa.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'owner'): ?>
            <div class="form-error">Nie możesz usunąć innego ownera z gospodarstwa.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['member']) && $_GET['member'] === 'not-found'): ?>
            <div class="form-error">Nie znaleziono wskazanego członka gospodarstwa.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['delete']) && $_GET['delete'] === 'forbidden'): ?>
            <div class="form-error">Tylko owner może usunąć całe gospodarstwo.</div>
        <?php endif; ?>

        <section class="auth-card households-hero-card">
            <div class="household-hero-copy">
                <p class="household-eyebrow">Miesięczne rozliczenie</p>
                <h2><?= htmlspecialchars($selectedPeriod) ?></h2>
                <p>Sprawdź, ile każda osoba zapłaciła, jaki jest jej udział i kto finalnie powinien odzyskać środki.</p>
            </div>
            <form action="<?= url('households/show') ?>" method="GET" class="household-period-form household-period-card">
                <input type="hidden" name="id" value="<?= (int) $household['id'] ?>">
                <label for="household-period" class="household-field-label">Wybrany miesiąc</label>
                <input id="household-period" type="month" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>" class="auth-input">
                <button type="submit" class="btn-primary">Załaduj</button>
            </form>
        </section>

        <section class="household-kpi-grid">
            <div class="auth-card household-kpi-card">
                <span class="household-kpi-label">Suma wydatków</span>
                <strong><?= number_format($data['totalMonthExpense'], 2) ?> zł</strong>
                <p>Łączna kwota wspólnych kosztów w tym miesiącu.</p>
            </div>
            <div class="auth-card household-kpi-card">
                <span class="household-kpi-label">Członkowie</span>
                <strong><?= $memberCount ?></strong>
                <p>Osoby aktualnie rozliczane w gospodarstwie.</p>
            </div>
            <div class="auth-card household-kpi-card">
                <span class="household-kpi-label">Do zwrotu</span>
                <strong><?= count($positiveBalances) ?></strong>
                <p>Tyle osób wyłożyło więcej niż wynika z ich udziału.</p>
            </div>
            <div class="auth-card household-kpi-card">
                <span class="household-kpi-label">Twoja rola</span>
                <strong><?= htmlspecialchars($currentMember['role'] ?? 'member') ?></strong>
                <p><?= $canManageHousehold ? 'Możesz zarządzać udziałami, zaproszeniami i członkami.' : 'Masz dostęp do podglądu, rozliczeń i wyjścia z gospodarstwa.' ?></p>
            </div>
        </section>

        <div class="households-show-layout">
            <section class="households-main-column">
                <div class="auth-card households-summary-card household-full-span">
                    <div class="household-summary-row">
                        <div>
                            <h3>Bilans członków</h3>
                            <p>Każda karta pokazuje udział, wpłaty i końcowe saldo za wybrany miesiąc.</p>
                        </div>
                    </div>

                    <div class="household-balance-grid">
                        <?php foreach ($monthlyBalance as $row): ?>
                            <div class="household-balance-card">
                                <div class="household-balance-head">
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <span class="household-balance-share"><?= number_format($row['share_percent'], 2) ?>%</span>
                                </div>
                                <div class="household-balance-metrics">
                                    <div>
                                        <span>Zapłacono</span>
                                        <strong><?= number_format($row['paid'], 2) ?> zł</strong>
                                    </div>
                                    <div>
                                        <span>Powinno wyjść</span>
                                        <strong><?= number_format($row['should_pay'], 2) ?> zł</strong>
                                    </div>
                                </div>
                                <p class="household-balance-result <?= $row['balance'] >= 0 ? 'text-positive' : 'text-negative' ?>">
                                    Saldo: <?= number_format($row['balance'], 2) ?> zł
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-card households-panel household-action-panel household-full-span">
                    <div class="household-section-heading">
                        <div>
                            <h3>Dodaj wydatek</h3>
                            <p>Zapisany koszt od razu trafi do rozliczenia za jego miesiąc.</p>
                        </div>
                    </div>
                    <form action="<?= url('households/store-expense') ?>" method="POST" class="auth-form households-form household-expense-form">
                        <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                        <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Kwota" required class="auth-input">
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required class="auth-input">

                        <select name="paid_by_user_id" class="auth-input" required>
                            <option value="" disabled selected>Kto zapłacił?</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int) $member['user_id'] ?>">
                                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="category_id" class="auth-input" required>
                            <option value="" disabled selected>Wybierz kategorię</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" name="description" placeholder="Opis (opcjonalnie)" class="auth-input household-expense-description">
                        <button type="submit" class="btn-primary">Zapisz wydatek</button>
                    </form>
                </div>

                <div class="auth-card households-panel household-expenses-panel household-full-span">
                    <div class="household-section-heading">
                        <div>
                            <h3>Wydatki w tym miesiącu</h3>
                            <p>Historia wspólnych płatności dodanych do gospodarstwa.</p>
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
                        <div class="household-empty-state">
                            <strong>Brak wydatków w tym okresie.</strong>
                            <p>Dodaj pierwszy wspólny koszt w formularzu powyżej.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card households-panel household-full-span">
                    <div class="household-section-heading">
                        <div>
                            <h3>Udziały członków</h3>
                            <p>Suma wszystkich udziałów musi wynosić dokładnie 100%.</p>
                        </div>
                    </div>
                    <?php if ($canManageHousehold): ?>
                        <form action="<?= url('households/update-shares') ?>" method="POST" class="households-shares-form">
                            <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                            <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                            <div class="households-members-list">
                                <?php foreach ($members as $member): ?>
                                    <label class="household-share-row">
                                        <span class="household-share-person">
                                            <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                            <small><?= htmlspecialchars($member['role']) ?></small>
                                        </span>
                                        <div class="household-share-input-wrap">
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
                            <button type="submit" class="btn-primary household-shares-submit">Zapisz udziały</button>
                        </form>
                    <?php else: ?>
                        <div class="households-members-list">
                            <?php foreach ($members as $member): ?>
                                <div class="household-member-row household-member-static">
                                    <span class="household-share-person">
                                        <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                        <small><?= htmlspecialchars($member['role']) ?></small>
                                    </span>
                                    <strong><?= number_format($member['share_percent'], 2) ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-card households-panel household-full-span">
                    <div class="household-section-heading">
                        <div>
                            <h3>Zarządzanie członkami</h3>
                            <p>Lista osób z dostępem do wspólnych rozliczeń oraz aktywne zaproszenia.</p>
                        </div>
                    </div>
                    <?php if ($canManageHousehold): ?>
                        <div class="household-manage-block">
                            <h4>Nowe zaproszenie</h4>
                            <form action="<?= url('households/invite') ?>" method="POST" class="auth-form households-form">
                                <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                <input type="email" name="email" placeholder="Adres email" required class="auth-input">
                                <button type="submit" class="btn-primary">Wyślij zaproszenie</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageHousehold): ?>
                        <div class="household-invitations household-manage-block">
                            <h4>Aktywne zaproszenia</h4>
                            <?php if (!empty($invitedUsers)): ?>
                                <div class="household-invitation-list">
                                    <?php foreach ($invitedUsers as $invitedUser): ?>
                                        <div class="household-invitation-row">
                                            <div>
                                                <strong><?= htmlspecialchars($invitedUser['invited_email']) ?></strong>
                                                <p>Wygasa: <?= htmlspecialchars($invitedUser['expires_at']) ?></p>
                                            </div>
                                            <form action="<?= url('household/deleteInvitation') ?>" method="POST">
                                                <input type="hidden" name="id" value="<?= (int) $invitedUser['id'] ?>">
                                                <input type="hidden" name="household" value="<?= (int) $household['id'] ?>">
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

                    <div class="household-manage-block">
                        <h4>Członkowie gospodarstwa</h4>
                        <div class="households-members-list">
                            <?php foreach ($members as $member): ?>
                                <div class="household-member-actions">
                                    <div>
                                        <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                        <p><?= htmlspecialchars($member['email']) ?> • <?= htmlspecialchars($member['role']) ?></p>
                                    </div>
                                    <?php if ($canManageHousehold && (int) $member['user_id'] !== (int) $_SESSION['user_id'] && $member['role'] !== 'owner'): ?>
                                        <form action="<?= url('households/remove-member') ?>" method="POST">
                                            <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
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
                        <div class="household-leave-box household-manage-block">
                            <h4>Opuszczenie gospodarstwa</h4>
                            <?php if ($currentMember['role'] === 'owner' && $ownerCount <= 1): ?>
                                <p>Jesteś jedynym ownerem. Nie możesz opuścić gospodarstwa, dopóki nie pojawi się inny owner.</p>
                            <?php else: ?>
                                <form action="<?= url('households/leave') ?>" method="POST" class="household-leave-form">
                                    <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                    <button type="submit" class="btn-secondary">Opuść gospodarstwo</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageHousehold): ?>
                        <div class="household-danger-box household-manage-block">
                            <h4>Usunięcie gospodarstwa</h4>
                            <p>Ta akcja usuwa całe gospodarstwo wraz z członkami, zaproszeniami i wydatkami.</p>
                            <form action="<?= url('households/delete') ?>" method="POST" class="household-leave-form">
                                <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Na pewno usunąć całe gospodarstwo domowe?');">Usuń gospodarstwo</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>
</body>
</html>
