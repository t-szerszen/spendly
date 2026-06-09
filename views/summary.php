<?php $pageStyles = ['styles/pages/summary.css']; ?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
    <?php include comp('navDashboard.php'); ?>

    <main class="summary-main">
        <div class="summary-container">
            <div class="summary-header-zone">
                <div class="summary-title-section">
                    <h1 class="summary-title">Raporty i Statystyki</h1>
                    <p class="summary-subtitle">Zaawansowana analiza Twoich finansów</p>
                </div>

                <form method="GET" action="<?= url('summary') ?>" class="summary-filter-form" id="filterForm">
                    <input type="hidden" name="month" id="hiddenMonth" value="<?= $data['currentMonth'] ?>">
                    <input type="hidden" name="year" id="hiddenYear" value="<?= $data['currentYear'] ?>">

                    <button type="button" class="summary-nav-button" id="prevMonthBtn">&larr;</button>

                    <div class="summary-date-inputs">
                        <div class="summary-input-group">
                            <label>Od:</label>
                            <input type="date" name="start_date" id="startDateInput" value="<?= $data['startDate'] ?>">
                        </div>
                        <div class="summary-input-group">
                            <label>Do:</label>
                            <input type="date" name="end_date" id="endDateInput" value="<?= $data['endDate'] ?>">
                        </div>
                    </div>

                    <button type="button" class="summary-nav-button" id="nextMonthBtn">&rarr;</button>
                </form>
            </div>

            <!-- Główne wskaźniki finansowe przygotowane przez SummaryController. -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <h3>Przychody (Incomes)</h3>
                    <div class="kpi-value positive">+<?= number_format($data['stats']['totalIncome'], 2) ?> zł</div>
                    <div class="comparison-badges <?= $data['comparisons']['totalIncome']['class'] ?>">
                        <span><?= htmlspecialchars($data['comparisons']['totalIncome']['amount']) ?></span>
                        <span><?= htmlspecialchars($data['comparisons']['totalIncome']['percent']) ?></span>
                    </div>
                </div>
                <div class="kpi-card">
                    <h3>Wydatki (Expenses)</h3>
                    <div class="kpi-value negative">-<?= number_format($data['stats']['totalExpenses'], 2) ?> zł</div>
                    <div class="comparison-badges <?= $data['comparisons']['totalExpenses']['class'] ?>">
                        <span><?= htmlspecialchars($data['comparisons']['totalExpenses']['amount']) ?></span>
                        <span><?= htmlspecialchars($data['comparisons']['totalExpenses']['percent']) ?></span>
                    </div>
                </div>
                <div class="kpi-card">
                    <h3>Bilans (Cash Flow)</h3>
                    <div class="kpi-value <?= $data['stats']['cashFlow'] >= 0 ? 'positive' : 'negative' ?>">
                        <?= ($data['stats']['cashFlow'] >= 0 ? '+' : '') . number_format($data['stats']['cashFlow'], 2) ?> zł
                    </div>
                    <div class="comparison-badges <?= $data['comparisons']['cashFlow']['class'] ?>">
                        <span><?= htmlspecialchars($data['comparisons']['cashFlow']['amount']) ?></span>
                        <span><?= htmlspecialchars($data['comparisons']['cashFlow']['percent']) ?></span>
                    </div>
                </div>
                <div class="kpi-card">
                    <h3>Śr. na transakcję</h3>
                    <div class="kpi-value neutral"><?= number_format($data['stats']['avgTransaction'], 2) ?> zł</div>
                    <div class="comparison-badges <?= $data['comparisons']['avgTransaction']['class'] ?>">
                        <span><?= htmlspecialchars($data['comparisons']['avgTransaction']['amount']) ?></span>
                        <span><?= htmlspecialchars($data['comparisons']['avgTransaction']['percent']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Wskaźniki uzupełniające: tempo wydatków, prognoza oraz bilans dzienny. -->
            <div class="insight-grid">
                <div class="kpi-card insight-card">
                    <h3>Tempo wydawania</h3>
                    <div class="kpi-value negative"><?= number_format($data['stats']['dailySpendingPace'], 2) ?> zł</div>
                    <span class="kpi-sub">Wydatki na dzień w wybranym okresie</span>
                    <div class="pace-meter">
                        <div class="pace-meter-fill <?= $data['stats']['paceTrend']['class'] ?>" style="width: <?= $data['stats']['paceProgress'] ?>%"></div>
                    </div>
                    <span class="pace-note <?= $data['stats']['paceTrend']['class'] ?>"><?= htmlspecialchars($data['stats']['paceTrend']['text']) ?></span>
                </div>
                <div class="kpi-card insight-card">
                    <h3>Prognoza wydatków 30 dni</h3>
                    <div class="kpi-value neutral"><?= number_format($data['stats']['monthlyAverageExpense'], 2) ?> zł</div>
                    <span class="kpi-sub">Ile wydasz przy obecnym tempie z <?= $data['stats']['periodDays'] ?> dni</span>
                </div>
                <div class="kpi-card balance-line-card">
                    <h3>Bilans dzienny</h3>
                    <span class="kpi-sub">Dzienna różnica przychodów i wydatków</span>
                    <div class="mini-chart-box">
                        <canvas id="dailyBalanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="summary-dashboard-grid">
                <div class="summary-grid-column summary-flex-column">
                    <div class="summary-report-card chart-card">
                        <h2>Struktura wydatków</h2>
                        <div class="chart-box">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>

                    <div class="summary-report-card top-categories-card">
                        <h2>TOP Kategorie wydatków</h2>
                        <div class="top-list">
                            <?php if (!empty($data['summary'])): ?>
                                <?php foreach (array_slice($data['summary'], 0, 5) as $row):
                                    $percentage = $data['stats']['totalExpenses'] > 0 ? ($row['total_amount'] / $data['stats']['totalExpenses']) * 180 : 0;
                                ?>
                                    <div class="top-item">
                                        <div class="top-item-info">
                                            <span><?= htmlspecialchars($row['category_name']) ?></span>
                                            <strong><?= number_format($row['total_amount'], 2) ?> zł</strong>
                                        </div>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-msg">Brak zarejestrowanych wydatków.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="summary-grid-column">
                    <div class="summary-report-card calendar-card" id="calendarCard">
                        <div class="calendar-card-header">
                            <h2>Kalendarz Transakcji</h2>
                            <div class="calendar-month-picker">
                                <button type="button" class="summary-nav-button" id="prevCalendarMonthBtn">&larr;</button>
                                <span id="calendarMonthLabel"></span>
                                <button type="button" class="summary-nav-button" id="nextCalendarMonthBtn">&rarr;</button>
                            </div>
                        </div>
                        <p class="summary-card-subtitle">Dzienne podsumowanie przychodów i wydatków</p>
                        <div class="calendar-wrapper">
                            <div class="calendar-weekdays">
                                <div>Pon</div><div>Wt</div><div>Śr</div><div>Czw</div><div>Pt</div><div>Sob</div><div>Nd</div>
                            </div>
                            <div class="calendar-days" id="calendarDaysGrid"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-report-card monthly-balance-card">
                <div class="monthly-balance-header">
                    <div>
                        <h2>Bilans miesięczny</h2>
                        <p class="summary-card-subtitle">Przychody minus wydatki w kolejnych miesiącach</p>
                    </div>
                    <div class="monthly-balance-controls">
                        <button type="button" class="summary-nav-button" id="prevMonthlyBalanceBtn">&larr;</button>
                        <span id="monthlyBalanceRange"></span>
                        <button type="button" class="summary-nav-button" id="nextMonthlyBalanceBtn">&rarr;</button>
                    </div>
                </div>
                <div class="monthly-balance-chart-box">
                    <canvas id="monthlyBalanceChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Formularz szybkiego dodawania transakcji inicjowany z poziomu kalendarza. -->
    <div class="calendar-quick-add" id="calendarQuickAdd" hidden>
        <div class="summary-quick-add-header">
            <strong>Dodaj transakcję</strong>
            <button type="button" id="closeQuickAdd" aria-label="Zamknij">&times;</button>
        </div>
        <form action="<?= url('transaction/add') ?>" method="POST" class="summary-quick-add-form">
            <input type="hidden" name="redirect_to" value="summary">
            <input type="hidden" name="summary_start_date" value="<?= htmlspecialchars($data['startDate']) ?>">
            <input type="hidden" name="summary_end_date" value="<?= htmlspecialchars($data['endDate']) ?>">
            <input type="hidden" name="summary_calendar_month" id="quickAddCalendarMonth" value="<?= sprintf('%04d-%02d', $data['calendarYear'], $data['calendarMonth']) ?>">
            <input type="hidden" name="date" id="quickAddDate" value="<?= htmlspecialchars($data['today']) ?>">

            <label>
                Kwota
                <input type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" required>
            </label>
            <label>
                Typ
                <select name="type">
                    <option value="expense">Wydatek</option>
                    <option value="income">Przychód</option>
                </select>
            </label>
            <label>
                Kategoria
                <select name="category_id" required>
                    <option value="" disabled selected>Wybierz kategorię</option>
                    <?php foreach ($data['categories'] as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Opis
                <input type="text" name="description" placeholder="Opcjonalnie">
            </label>

            <button type="submit" class="summary-quick-add-submit">Dodaj</button>
        </form>
    </div>

    <!-- Kontener danych JSON przekazywanych z PHP do warstwy JavaScript. -->
    <div
        id="summary-data"
        hidden
        data-dashboard="<?= htmlspecialchars(
            json_encode($data['jsData'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) ?>"
    ></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= url('scripts/summary.js') ?>"></script>

    <?php include comp('footer.php'); ?>
</body>
</html>
