<?php
/**
 * Health Tracker - JSON Version (Update: Kein Sport Support)
 */
$jsonFile = 'health_data.json';

// 1. Daten laden
$data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

// 2. Speichern-Logik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $date = $_POST['date'];
    $discipline = $_POST['discipline'];
    
    // Wenn "Kein Sport" gewählt wurde, setzen wir die Intensität auf 0
    $intensity = ($discipline === 'Kein Sport') ? 0 : (int)$_POST['intensity'];

    $data[$date] = [
        'weight' => (float)$_POST['weight'],
        'discipline' => $discipline,
        'intensity' => $intensity
    ];
    
    ksort($data);
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Lösch-Logik
if (isset($_GET['delete'])) {
    $dateToDelete = $_GET['delete'];
    if (isset($data[$dateToDelete])) {
        unset($data[$dateToDelete]);
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Vorbereitung für Diagramme (Letzte 365 Tage)
$chartLabels = [];
$chartWeights = [];
$today = new DateTime();
for ($i = 364; $i >= 0; $i--) {
    $d = new DateTime();
    $d->modify("-$i days");
    $dateStr = $d->format('Y-m-d');
    
    $chartLabels[] = $dateStr;
    $chartWeights[] = isset($data[$dateStr]['weight']) ? $data[$dateStr]['weight'] : null;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health & Fitness Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; background: #f0f2f5; color: #1c1e21; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        h1, h2 { color: #333; }
        
        form { display: flex; gap: 10px; flex-wrap: wrap; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px; align-items: center; }
        label { font-size: 0.9em; font-weight: bold; color: #555; }
        input, select, button { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #2da44e; color: white; border: none; cursor: pointer; font-weight: bold; padding: 8px 15px; }
        button:hover { background: #2c974b; }

        /* Heatmap Styling */
        .heatmap-container { overflow-x: auto; padding: 10px 0; }
        .heatmap-wrapper { display: grid; grid-template-columns: repeat(53, 14px); grid-template-rows: repeat(7, 14px); gap: 3px; width: max-content; }
        .tile { width: 14px; height: 14px; border-radius: 2px; background: #ebedf0; position: relative; border: 1px solid rgba(0,0,0,0.05); }
        
        /* Intensitäts-Stufen */
        .level-0 { background-color: #ffffff; border: 1px solid #eee; } /* Weiß für Kein Sport */
        .level-1 { background-color: #9be9a8; }
        .level-2 { background-color: #40c463; }
        .level-3 { background-color: #30a14e; }
        .level-4 { background-color: #216e39; }
        .level-5 { background-color: #0e4429; }
        
        .tile:hover::after { content: attr(title); position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: #333; color: #fff; padding: 4px 7px; font-size: 11px; border-radius: 3px; z-index: 100; white-space: nowrap; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #fdfdfd; }
        .btn-delete { color: #d73a49; text-decoration: none; font-size: 0.9em; }

        .chart-box { margin: 30px 0; height: 300px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Gesundheits-Tracker</h1>

    <form method="POST" id="healthForm">
        <input type="hidden" name="action" value="save">
        
        <div>
            <label>Datum:</label><br>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div>
            <label>Gewicht (kg):</label><br>
            <input type="number" step="0.1" name="weight" placeholder="0.0" required style="width: 100px;">
        </div>

        <div>
            <label>Disziplin:</label><br>
            <select name="discipline" id="disciplineSelect" onchange="toggleIntensity()">
                <option value="Kein Sport" selected>Kein Sport</option>
                <option value="Spinning Bike">Spinning Bike</option>
                <option value="Laufen">Laufen</option>
                <option value="Klettern">Klettern</option>
                <option value="Wandern">Wandern</option>
                <option value="Schießsport">Schießsport</option>
            </select>
        </div>

        <div id="intensityWrapper" style="display:none;">
            <label>Intensität:</label><br>
            <select name="intensity">
                <option value="1">1 (Hellgrün)</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5 (Dunkelgrün)</option>
            </select>
        </div>

        <div style="align-self: flex-end;">
            <button type="submit">Speichern</button>
        </div>
    </form>

    <script>
        function toggleIntensity() {
            const select = document.getElementById('disciplineSelect');
            const wrapper = document.getElementById('intensityWrapper');
            wrapper.style.display = (select.value === 'Kein Sport') ? 'none' : 'block';
        }
    </script>

    <h2>Bewegungs-Aktivität (365 Tage)</h2>
    <div class="heatmap-container">
        <div class="heatmap-wrapper">
            <?php
            foreach ($chartLabels as $dateKey) {
                $intensity = isset($data[$dateKey]['intensity']) ? $data[$dateKey]['intensity'] : -1; // -1 = kein Eintrag vorhanden
                $discipline = isset($data[$dateKey]['discipline']) ? " (" . $data[$dateKey]['discipline'] . ")" : "";
                
                $class = "";
                if ($intensity === 0) {
                    $class = "level-0"; // Weiß für Sport gemacht, aber Intensität 0
                } elseif ($intensity > 0) {
                    $class = "level-$intensity"; // Grün-Stufen
                }
                // Wenn $intensity === -1 bleibt es grau (Standard .tile)

                echo "<div class='tile $class' title='$dateKey $discipline'></div>";
            }
            ?>
        </div>
    </div>

    <h2>Gewichtsverlauf</h2>
    <div class="chart-box">
        <canvas id="weightChart"></canvas>
    </div>

    <h2>Datensätze</h2>
    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Gewicht</th>
                <th>Disziplin</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($data) as $date => $info): ?>
            <tr>
                <td><?= htmlspecialchars($date) ?></td>
                <td><?= htmlspecialchars($info['weight']) ?> kg</td>
                <td><?= htmlspecialchars($info['discipline']) ?> 
                    <?= ($info['intensity'] > 0) ? "(Stufe {$info['intensity']})" : "" ?>
                </td>
                <td><a href="?delete=<?= $date ?>" class="btn-delete" onclick="return confirm('Löschen?')">Löschen</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    const ctx = document.getElementById('weightChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Gewicht (kg)',
                data: <?= json_encode($chartWeights) ?>,
                borderColor: '#0969da',
                backgroundColor: 'rgba(9, 105, 218, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                spanGaps: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false },
                y: { beginAtZero: false }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

</body>
</html>