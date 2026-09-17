<?php
/**
 * Traitement du formulaire de demande de devis.
 * Nécessite un hébergement PHP avec mail() actif (hébergement mutualisé OVH).
 */

header("Content-Type: application/json; charset=utf-8");

$destinataire = "tdevgreen@gmail.com";

function clean_field(string $value): string
{
    // Empêche l'injection d'en-têtes email via des retours à la ligne.
    return trim(str_replace(["\r", "\n"], "", $value));
}

function fail(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(["ok" => false, "message" => $message]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    fail(405, "Méthode non autorisée.");
}

$nom = clean_field($_POST["nom"] ?? "");
$societe = clean_field($_POST["societe"] ?? "");
$email = clean_field($_POST["email"] ?? "");
$telephone = clean_field($_POST["telephone"] ?? "");
$type_site = clean_field($_POST["type_site"] ?? "");
$delai = clean_field($_POST["delai"] ?? "");
$adresse = clean_field($_POST["adresse"] ?? "");
$ville = clean_field($_POST["ville"] ?? "");
$nb_bornes = clean_field($_POST["nb_bornes"] ?? "");
$puissance = clean_field($_POST["puissance"] ?? "");
$message = trim($_POST["message"] ?? "");

if (
    $nom === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $telephone === "" ||
    $type_site === "" ||
    $adresse === "" ||
    $ville === "" ||
    !ctype_digit($nb_bornes) ||
    (int) $nb_bornes < 1
) {
    fail(422, "Merci de vérifier les champs obligatoires du formulaire de devis.");
}

$sujet = "Nouvelle demande de devis IRVE — TDEVGREEN";

$corps = "Vos coordonnées\n";
$corps .= "----------------\n";
$corps .= "Nom : {$nom}\n";
$corps .= $societe !== "" ? "Société : {$societe}\n" : "";
$corps .= "Email : {$email}\n";
$corps .= "Téléphone : {$telephone}\n";

$corps .= "\nVotre projet\n";
$corps .= "------------\n";
$corps .= "Type de site : {$type_site}\n";
$corps .= "Adresse du site : {$adresse}\n";
$corps .= "Code postal / Ville : {$ville}\n";
$corps .= "Nombre de bornes souhaitées : {$nb_bornes}\n";
$corps .= "Puissance souhaitée : " . ($puissance !== "" ? $puissance : "non précisée") . "\n";
$corps .= "Délai souhaité : " . ($delai !== "" ? $delai : "non précisé") . "\n";

if ($message !== "") {
    $corps .= "\nPrécisions complémentaires :\n{$message}\n";
}

$entetes = [
    "From: TDEVGREEN Site <no-reply@tdevgreen.fr>",
    "Reply-To: {$email}",
    "Content-Type: text/plain; charset=UTF-8",
];

$envoye = mail($destinataire, $sujet, $corps, implode("\r\n", $entetes));

if (!$envoye) {
    fail(500, "L'envoi a échoué. Merci de nous contacter directement par email.");
}

echo json_encode(["ok" => true, "message" => "Demande de devis envoyée."]);
