<?php
/**
 * BACKEND LOGIK
 */
$jsonFile = 'data.json';

// 1. Daten laden
$data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

// 2. Speichern-Logik (überschreibt existierende Keys automatisch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $date = $_POST['date'];
    $discipline = $_POST['discipline'];
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

// 4. Vorbereitung für Diagramme (Letzte 365 Tage)
$chartLabels = [];
$chartWeights = [];
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
    <title>Gesundheitstracker</title>
    
    <!-- Ressourcen -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --pkv-blue: #007bff; 
            --bg-gray: #f0f2f5; 
            --dark-gray: #343a40;
            --text-main: #1c1e21;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg-gray); 
            margin: 0; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            color: var(--text-main);
        }

        .container { 
            max-width: 1100px; 
            margin: 0 auto 30px auto; 
            padding: 0 20px; 
            flex: 1; 
            width: 100%; 
            box-sizing: border-box; 
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 30px;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header-logo img { height: 50px; width: auto; display: block; }
        .header-title-center h1 { margin: 0; font-size: 1.5rem; color: #333; }
        .btn-dashboard {
            text-decoration: none;
            background-color: var(--pkv-blue);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-dashboard:hover { background-color: #0056b3; }

        .content-box { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.05); 
            margin-bottom: 25px; 
        }
        
        h2 { color: #333; margin-top: 0; }
        
        form { 
            display: flex; 
            gap: 15px; 
            flex-wrap: wrap; 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
            align-items: flex-end; 
            border: 1px solid #eee;
        }

        .input-group { display: flex; flex-direction: column; gap: 4px; }
        .input-group label { font-size: 0.75rem; font-weight: bold; color: #666; text-transform: uppercase; }

        input, select, button { padding: 10px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 0.9rem; }
        button { background: var(--pkv-blue); color: white; border: none; cursor: pointer; font-weight: bold; padding: 10px 20px; }
        button:hover { background: #0056b3; }

        .heatmap-container { overflow-x: auto; padding: 10px 0; }
        .heatmap-wrapper { display: grid; grid-template-columns: repeat(53, 14px); grid-template-rows: repeat(7, 14px); gap: 3px; width: max-content; }
        .tile { width: 14px; height: 14px; border-radius: 2px; background: #ebedf0; position: relative; border: 1px solid rgba(0,0,0,0.05); }
        
        .level-0 { background-color: #ffffff; border: 1px solid #eee; }
        .level-1 { background-color: #9be9a8; }
        .level-2 { background-color: #40c463; }
        .level-3 { background-color: #30a14e; }
        .level-4 { background-color: #216e39; }
        .level-5 { background-color: #0e4429; }
        
        .tile:hover::after { 
            content: attr(title); 
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); 
            background: #333; color: #fff; padding: 4px 7px; font-size: 11px; 
            border-radius: 3px; z-index: 100; white-space: nowrap; 
        }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 0.75rem; color: #666; border-bottom: 2px solid #eee; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f1f1f1; font-size: 0.9rem; }
        
        .actions { display: flex; gap: 15px; align-items: center; }
        .btn-delete { color: #dc3545; text-decoration: none; }
        .btn-edit { color: var(--pkv-blue); text-decoration: none; cursor: pointer; }

        .chart-box { margin: 20px 0; height: 300px; }

        footer { 
            background: var(--dark-gray); 
            color: #bbb; 
            padding: 30px; 
            text-align: center; 
            margin-top: 40px; 
            font-size: 0.85rem; 
        }
        footer a { color: white; text-decoration: none; border-bottom: 1px solid #555; }
    </style>
</head>
<body>

<header class="main-header">
    <div class="header-logo">
        <img src="logo.png" alt="Logo">
    </div>
    <div class="header-title-center">
        <h1>Gesundheitstracker</h1>
    </div>
    <div class="header-nav-right">
        <a href="../index.php" class="btn-dashboard"><i class="fa-solid fa-house"></i> Dashboard</a>
    </div>
</header>

<div class="container">
    <!-- Erfassung -->
    <div class="content-box">
        <h2>Aktivität & Gewicht erfassen</h2>
        <form method="POST" id="healthForm">
            <input type="hidden" name="action" value="save">
            
            <div class="input-group">
                <label>Datum</label>
                <input type="date" name="date" id="formDate" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="input-group">
                <label>Gewicht (kg)</label>
                <input type="number" step="0.1" name="weight" id="formWeight" placeholder="0.0" required style="width: 110px;">
            </div>

            <div class="input-group">
                <label>Disziplin</label>
                <select name="discipline" id="disciplineSelect" onchange="toggleIntensity()">
                    <option value="Kein Sport" selected>Kein Sport</option>
                    <option value="Spinning Bike">Spinning Bike</option>
                    <option value="Laufen">Laufen</option>
                    <option value="Klettern">Klettern</option>
                    <option value="Wandern">Wandern</option>
                    <option value="Schießsport">Schießsport</option>
                </select>
            </div>

            <div id="intensityWrapper" class="input-group" style="display:none;">
                <label>Intensität</label>
                <select name="intensity" id="intensitySelect">
                    <option value="1">1 (Hellgrün)</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5 (Dunkelgrün)</option>
                </select>
            </div>

            <button type="submit">Speichern</button>
        </form>
    </div>

    <!-- Heatmap -->
    <div class="content-box">
        <h2>Bewegungs-Aktivität (365 Tage)</h2>
        <div class="heatmap-container">
            <div class="heatmap-wrapper">
                <?php
                foreach ($chartLabels as $dateKey) {
                    $intensity = isset($data[$dateKey]['intensity']) ? $data[$dateKey]['intensity'] : -1;
                    $discipline = isset($data[$dateKey]['discipline']) ? " (" . $data[$dateKey]['discipline'] . ")" : "";
                    
                    $class = "";
                    if ($intensity === 0) {
                        $class = "level-0";
                    } elseif ($intensity > 0) {
                        $class = "level-$intensity";
                    }

                    echo "<div class='tile $class' title='$dateKey $discipline'></div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="content-box">
        <h2>Gewichtsverlauf</h2>
        <div class="chart-box">
            <canvas id="weightChart"></canvas>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="content-box">
        <h2>Datensätze</h2>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Gewicht</th>
                    <th>Disziplin</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($data) as $date => $info): ?>
                <tr>
                    <td><?= date("d.m.Y", strtotime($date)) ?></td>
                    <td><strong><?= htmlspecialchars($info['weight']) ?> kg</strong></td>
                    <td>
                        <?= htmlspecialchars($info['discipline']) ?> 
                        <span style="color: #888; font-size: 0.8rem;">
                            <?= ($info['intensity'] > 0) ? "(Stufe {$info['intensity']})" : "" ?>
                        </span>
                    </td>
                    <td class="actions">
                        <!-- Bearbeiten Icon -->
                        <a class="btn-edit" onclick="editEntry('<?= $date ?>', <?= $info['weight'] ?>, '<?= $info['discipline'] ?>', <?= $info['intensity'] ?>)" title="Bearbeiten">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <!-- Löschen Icon -->
                        <a href="?delete=<?= $date ?>" class="btn-delete" onclick="return confirm('Löschen?')" title="Löschen">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>
    <p><strong>Gesundheitstracker</strong> | Lizenziert unter <a href="https://www.gnu.org/licenses/agpl-3.0.de.html" target="_blank">AGPL-3.0</a> | Source von Herr-NM: <a href="https://github.com/herr-nm/Privat_Gesundheit" target="_blank">GitHub</a></p>
</footer>

<script>
    function toggleIntensity() {
        const select = document.getElementById('disciplineSelect');
        const wrapper = document.getElementById('intensityWrapper');
        wrapper.style.display = (select.value === 'Kein Sport') ? 'none' : 'flex';
    }

    // Funktion zum Laden der Daten in das Formular
    function editEntry(date, weight, discipline, intensity) {
        document.getElementById('formDate').value = date;
        document.getElementById('formWeight').value = weight;
        document.getElementById('disciplineSelect').value = discipline;
        document.getElementById('intensitySelect').value = intensity;
        toggleIntensity();
        
        // Scrollt zum Formular
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    const ctx = document.getElementById('weightChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Gewicht (kg)',
                data: <?= json_encode($chartWeights) ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
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
                y: { beginAtZero: false, grid: { color: '#eee' } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

</body>
</html>