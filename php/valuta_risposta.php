<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $_SESSION['username']; 
    $id_risposta = $data['id_risposta'];
    $stelle = $data['stelle']; 

    // caricamento file XML
    $xmlFile = '../xml/valuta_discussioni.xml';
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

    // controlliamo se l'ID della risposta è valido
    if (empty($id_risposta) || empty($stelle)) {
        echo json_encode(['success' => false, 'message' => 'ID risposta o stelle non validi']);
        exit();
    }

    // controllo se l'utente ha già valutato questa risposta
    $valutazioni = $dom->getElementsByTagName('valutazione');
    foreach ($valutazioni as $valutazione) {
        $autore = $valutazione->getElementsByTagName('autore')->item(0)->textContent;
        $idValutazione = $valutazione->getElementsByTagName('id_risposta')->item(0)->textContent;

        if ($autore === $username && $idValutazione == $id_risposta) {
            echo json_encode(['success' => false, 'message' => 'Hai già valutato questa risposta, non puoi farlo di nuovo.']);
            exit();
        }
    }

    // aggiungimo la nuova valutazione
    $valutazione = $dom->createElement('valutazione');
    $valutazione->appendChild($dom->createElement('autore', htmlspecialchars($username))); // Aggiungiamo lo username
    $valutazione->appendChild($dom->createElement('id_risposta', htmlspecialchars($id_risposta))); // Aggiungiamo l'ID della risposta
    $valutazione->appendChild($dom->createElement('stelle', htmlspecialchars($stelle))); // Aggiungiamo il numero di stelle

    // aggiunta valutazione al nodo radice
    $dom->documentElement->appendChild($valutazione);

    // salvataggio file XML
    $dom->save($xmlFile);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida']);
}
?>
