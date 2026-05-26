<!DOCTYPE html>
<?php include comp('head.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<body>
    <?php include comp('navDashboard.php'); ?>
    <main class="auth-section summary-main">
        <div class="container summary-container">
            
            <div class="summary-year-nav">
                <a href="<?= url('summary') ?>?year=<?= $data['currentYear'] - 1 ?>" class="year-arrow">&larr;</a>
                
                <h1 class="summary-header">Podsumowanie roku <?= $data['currentYear'] ?></h1>
                
                <a href="<?= url('summary') ?>?year=<?= $data['currentYear'] + 1 ?>" class="year-arrow">&rarr;</a>
            </div>
            <p class="summary-sub">Statystyki i zestawienie roczne</p>

            <div class="auth-card total-card">
                <h4 class="total-card-title">Suma wszystkich wydatków w tym roku:</h4>
                <h2 class="total-card-amount"><?= number_format($data['totalYearExpense'], 2) ?> zł</h2>
            </div>

            <div class="summary-layout">
                
                <div class="auth-card summary-col">
                    <h3 class="summary-title">Wydatki według kategorii</h3>
                    
                    <?php if (!empty($data['summary'])): ?>
                        <div class="category-list">
                            <?php foreach ($data['summary'] as $row): ?>
                                <div class="category-row">
                                    <div>
                                        <strong class="category-name"><?= htmlspecialchars($row['category_name']) ?></strong>
                                    </div>
                                    <div class="category-value">
                                        <?= number_format($row['total_amount'], 2) ?> zł
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--color-text-mutated);">Brak danych o wydatkach w tym roku.</p>
                    <?php endif; ?>
                </div>

                <div class="auth-card summary-chart-col">
                    <h3>Wykres struktury wydatków</h3>
                    <div class="chart-wrapper">
                        <canvas id="expenseChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </main>
<script>
        window.chartData = {
            categories: [],
            amounts: []
        };
        <?php foreach ($data['summary'] as $row): ?>
            window.chartData.categories.push(<?= json_encode($row['category_name']) ?>);
            window.chartData.amounts.push(<?= floatval($row['total_amount']) ?>);
        <?php endforeach; ?>
    </script>

    <script src="<?= url('scripts/summary.js') ?>"></script>
</body>
</html>
