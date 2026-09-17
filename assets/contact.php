<?php
/**
 * Traitement du formulaire de contact.
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
$message = trim($_POST["message"] ?? "");

if ($nom === "" || $message === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail(422, "Merci de renseigner votre nom, un email valide et votre message.");
}

$sujet = "Nouvelle demande de devis IRVE — TDEVGREEN";

$corps = "Nom : {$nom}\n";
$corps .= $societe !== "" ? "Société : {$societe}\n" : "";
$corps .= "Email : {$email}\n";
$corps .= $telephone !== "" ? "Téléphone : {$telephone}\n" : "";
$corps .= "\nMessage :\n{$message}\n";

$entetes = [
    "From: TDEVGREEN Site <no-reply@tdevgreen.fr>",
    "Reply-To: {$email}",
    "Content-Type: text/plain; charset=UTF-8",
];

$envoye = mail($destinataire, $sujet, $corps, implode("\r\n", $entetes));

if (!$envoye) {
    fail(500, "L'envoi a échoué. Merci de nous contacter directement par email.");
}

echo json_encode(["ok" => true, "message" => "Message envoyé."]);
