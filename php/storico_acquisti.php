<?php
session_start();
require_once('connessione.php');

// codice per mostrare il messaggio dell'accredito del bonus
if (isset($_SESSION['bonus_message'])) {
    echo "<script>alert('" . $_SESSION['bonus_message'] . "');</script>";
    unset($_SESSION['bonus_message']); // rimozione messaggio dopo averlo mostrato
}

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'cliente') {
    header("Location: home.php");
    exit();
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// aggiungiamo la funzione formattaData
function formattaData($data) {
    return date('d/m/Y', strtotime($data));
}

// funzione per ottenere i dettagli di un gioco dal file XML
function getDettagliGioco($codice_gioco) {
    $xml_file = '../xml/giochi.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->gioco as $gioco) {
            if ((string)$gioco->codice === (string)$codice_gioco) {
                return [
                    'titolo' => (string)$gioco->titolo,
                    'categoria' => (string)$gioco->categoria,
                    'nome_editore' => (string)$gioco->nome_editore,
                ];
            }
        }
    }
    return null; // se il gioco non viene trovato
}

// all'inizio del file, dopo session_start()
$xml_file = '../xml/acquisti.xml';
$acquisti = [];

if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    foreach ($xml->acquisto as $index => $acquisto) { // usiamo $index per mantenere l'ordine naturale
        if ((string)$acquisto->username === $_SESSION['username']) {
            $dettagli_gioco = getDettagliGioco((int)$acquisto->codice_gioco);
            if ($dettagli_gioco) {
                $acquisti[] = [
                    'id' => (string)$acquisto['id'],
                    'gioco' => $dettagli_gioco['titolo'],
                    'categoria' => $dettagli_gioco['categoria'],
                    'editore' => $dettagli_gioco['nome_editore'],
                    'prezzo_originale' => (float)$acquisto->prezzo_originale,
                    'prezzo_pagato' => (float)$acquisto->prezzo_pagato,
                    'sconto' => isset($acquisto->sconto_applicato) ? (float)$acquisto->sconto_applicato : 0,
                    'bonus' => isset($acquisto->bonus_ottenuti) ? (int)$acquisto->bonus_ottenuti : 0,
                    'data' => (string)$acquisto->data,
                ];
            }
        }
    }
    // ordina gli acquisti per data e ora decrescente
    usort($acquisti, function($a, $b) {
        return strtotime($b['data']) - strtotime($a['data']); // converte la stringa della data in un timestamp e confronta le date -> La differenza posiziona l'elemento più recente prima di quello meno recente
    });
    // questa funzione ordina l'array $acquisti in base a un criterio definito da una funzione anonima, la quale prende due elementi dell'array $a e $b e li confronta
}

// calcolo delle statistiche
$totale_speso = array_sum(array_column($acquisti, 'prezzo_pagato'));
$totale_risparmiato = array_sum(array_map(function($a) {
    return $a['prezzo_originale'] - $a['prezzo_pagato'];
}, $acquisti));
$totale_bonus = array_sum(array_column($acquisti, 'bonus'));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Acquisti - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">

    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 100px;
        }
        .statistiche {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-valore {
            font-size: 1.5em;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        .acquisti-tabella {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .acquisti-tabella th,
        .acquisti-tabella td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .acquisti-tabella th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .acquisti-tabella tr:hover {
            background: #f8f9fa;
        }
        .sconto-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .bonus-badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .no-acquisti {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        .container {
            margin-top: 100px;
        }
        .filtri {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filtri label {
            margin-right: 10px;
            font-weight: bold;
        }
        .filtri input {
            padding: 5px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <h1 style="margin-top: -1ex;">Storico Acquisti</h1>

        <div class="statistiche" style="margin-top: 3ex;">
            <div class="stat-card">
                <h3>Totale Speso</h3>
                <div class="stat-valore">€<?php echo number_format($totale_speso, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Totale Risparmiato</h3>
                <div class="stat-valore">€<?php echo number_format($totale_risparmiato, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Bonus Ottenuti</h3>
                <div class="stat-valore"><?php echo $totale_bonus; ?> crediti</div>
            </div>
            <div class="stat-card">
                <h3>Numero Acquisti</h3>
                <div class="stat-valore"><?php echo count($acquisti); ?></div>
            </div>
        </div>

        <div class="filtri">
            <label for="filtro-gioco">Gioco:</label>
            <input type="text" id="filtro-gioco" placeholder="Cerca gioco...">
            <label for="filtro-categoria">Categoria:</label>
            <input type="text" id="filtro-categoria" placeholder="Cerca categoria...">
            <label for="filtro-editore">Editore:</label>
            <input type="text" id="filtro-editore" placeholder="Cerca editore...">
        </div>

        <?php if (!empty($acquisti)): ?>
            <table class="acquisti-tabella" id="acquisti-tabella">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Gioco</th>
                        <th>Genere</th>
                        <th>Editore</th>
                        <th>Prezzo Originale</th>
                        <th>Prezzo Pagato</th>
                        <th>Sconto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($acquisti as $acquisto): ?>
                        <tr>
                            <td><?php echo formattaData($acquisto['data']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['gioco']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['editore']); ?></td>
                            <td><?php echo number_format($acquisto['prezzo_originale'], 2); ?></td>
                            <td><?php echo number_format($acquisto['prezzo_pagato'], 2); ?></td>
                            <td>
                                <!-- mostriamo se si ha o meno uno sconto -->
                                <?php if ($acquisto['sconto'] > 0): ?>
                                    <span class="sconto-badge">
                                        -<?php echo $acquisto['sconto']; ?>%
                                    </span>
                                <?php else: ?>
                                    <p>Nessuno sconto applicato</p>
                                <?php endif; ?>
                               
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-acquisti">
                <h2>Nessun acquisto effettuato</h2>
                <p>Non hai ancora effettuato acquisti. Visita il nostro catalogo per iniziare!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const navLinks = document.querySelector('.nav-links');

        hamburgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // filtri di ricerca
        const filtroGioco = document.getElementById('filtro-gioco');
        const filtroCategoria = document.getElementById('filtro-categoria');
        const filtroEditore = document.getElementById('filtro-editore');
        const tabellaAcquisti = document.getElementById('acquisti-tabella').getElementsByTagName('tbody')[0];

        function filtraTabella() {
            const gioco = filtroGioco.value.toLowerCase();
            const categoria = filtroCategoria.value.toLowerCase();
            const editore = filtroEditore.value.toLowerCase();

            for (let row of tabellaAcquisti.rows) {
                const giocoCell = row.cells[1].textContent.toLowerCase();
                const categoriaCell = row.cells[2].textContent.toLowerCase();
                const editoreCell = row.cells[3].textContent.toLowerCase();

                if (giocoCell.includes(gioco) && categoriaCell.includes(categoria) && editoreCell.includes(editore)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        filtroGioco.addEventListener('input', filtraTabella);
        filtroCategoria.addEventListener('input', filtraTabella);
        filtroEditore.addEventListener('input', filtraTabella);
    </script>

</body>
</html>