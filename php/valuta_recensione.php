<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    
    $username = $_SESSION['username']; 
    $id_recensione = $data['id_risposta']; 
    $stelle = $data['stelle']; 

    // caricamento file XML
    $xmlFile = '../xml/valuta_recensioni.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false; 
    $dom->formatOutput = true; 

    if (file_exists($xmlFile)) {
        $dom->load($xmlFile);
    } else {
        // se il file non esiste, creiamo la struttura di base
        $root = $dom->createElement('valutazioni');
        $dom->appendChild($root);
    }

    // aggiungiamo la nuova valutazione
    $valutazione = $dom->createElement('valutazione');
    $valutazione->appendChild($dom->createElement('username', htmlspecialchars($username))); // Aggiungiamo lo username
    $valutazione->appendChild($dom->createElement('id_recensione', htmlspecialchars($id_recensione))); // Aggiungiamo l'ID della recensione
    $valutazione->appendChild($dom->createElement('stelle', htmlspecialchars($stelle))); // Aggiungiamo il numero di stelle

    // aggiungiamo la valutazione al nodo radice
    $dom->documentElement->appendChild($valutazione);

    // salvataggio file XML
    $dom->save($xmlFile);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
