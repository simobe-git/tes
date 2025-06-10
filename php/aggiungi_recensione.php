<?php
session_start();

// verifichiamo se l'utente è loggato
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); 
    exit();
}

// riceviamo i dati dalla form
$testo = isset($_POST['testo']) ? trim($_POST['testo']) : '';
$username = $_SESSION['username'];
$data = date('Y-m-d'); // data attuale
$codice_gioco = isset($_POST['codice_gioco']) ? trim($_POST['codice_gioco']) : ''; // Recupera il codice del gioco

if (!empty($testo) && !empty($codice_gioco)) {
    // caricamento file XML esistente
    $xmlFile = '../xml/recensioni.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if (file_exists($xmlFile)) {
        $dom->load($xmlFile);
    } else {
        // se il file non esiste, crea la struttura di base
        $root = $dom->createElement('recensioni');
        $dom->appendChild($root);
    }

    // troviamo il massimo ID esistente
    $maxId = 0;
    foreach ($dom->getElementsByTagName('recensione') as $recensione) {
        $currentId = (int)$recensione->getAttribute('id');
        if ($currentId > $maxId) {
            $maxId = $currentId;
        }
    }

    // impostiamo il nuovo ID come il massimo ID + 1
    $newId = $maxId + 1;

    // creazione nuovo nodo recensione
    $recensione = $dom->createElement('recensione');
    $recensione->setAttribute('id', $newId); // usiamo l'id incrementale

    // aggiungiamo i dettagli della recensione
    $usernameNode = $dom->createElement('username', htmlspecialchars($username));
    $codiceGiocoNode = $dom->createElement('codice_gioco', htmlspecialchars($codice_gioco)); 
    $testoNode = $dom->createElement('testo', htmlspecialchars($testo));
    $dataNode = $dom->createElement('data', $data);

    // aggiunta nodi al nodo recensione
    $recensione->appendChild($usernameNode);
    $recensione->appendChild($codiceGiocoNode);
    $recensione->appendChild($testoNode);
    $recensione->appendChild($dataNode);

    // e aggiunta recensione al nodo radice
    $dom->documentElement->appendChild($recensione);

    // salvataggio file XML
    $dom->save($xmlFile);

    // reindirizza alla pagina di dettaglio del gioco
    header('Location: dettaglio_gioco.php?id=' . $_POST['codice_gioco']);
    exit();
}
?>
