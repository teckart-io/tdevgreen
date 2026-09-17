<?php
/**
 * Traitement de la fiche de pré-visite technique IRVE (demande de devis).
 * Nécessite un hébergement PHP avec mail() actif (hébergement mutualisé OVH).
 *
 * Les photos envoyées dépendent aussi des réglages PHP de l'hébergement
 * (upload_max_filesize, post_max_size) : à relever dans le panneau OVH si
 * les valeurs par défaut sont inférieures aux limites ci-dessous (MAX_FILE_SIZE,
 * MAX_TOTAL_SIZE) pour ne pas rejeter des photos avant même qu'elles n'atteignent ce script.
 */

header("Content-Type: application/json; charset=utf-8");

$destinataire = "tdevgreen@gmail.com";

const MAX_FILE_SIZE = 5 * 1024 * 1024;       // 5 Mo par photo
const MAX_TOTAL_SIZE = 12 * 1024 * 1024;     // 12 Mo au total (marge sous la limite Gmail une fois encodé)
const ALLOWED_MIME_TYPES = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
];

function clean_field(string $value): string
{
    // Empêche l'injection d'en-têtes email via des retours à la ligne.
    return trim(str_replace(["\r", "\n"], "", $value));
}

function display(string $value, string $fallback = "non renseigné"): string
{
    return $value !== "" ? $value : $fallback;
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

// --- Champs texte ---

$nom = clean_field($_POST["nom"] ?? "");
$telephone = clean_field($_POST["telephone"] ?? "");
$email = clean_field($_POST["email"] ?? "");
$date_souhaitee = clean_field($_POST["date_souhaitee"] ?? "");
$adresse = clean_field($_POST["adresse"] ?? "");
$code_postal = clean_field($_POST["code_postal"] ?? "");
$ville = clean_field($_POST["ville"] ?? "");

$type_pose = clean_field($_POST["type_pose"] ?? "");
$lieu = clean_field($_POST["lieu"] ?? "");
$nature_support = clean_field($_POST["nature_support"] ?? "");
$distance_borne_vehicule = clean_field($_POST["distance_borne_vehicule"] ?? "");

$tableau_normes = ($_POST["tableau_normes"] ?? "") === "oui";
$distance_tableau_borne = clean_field($_POST["distance_tableau_borne"] ?? "");
$passage_cables = clean_field($_POST["passage_cables"] ?? "");
$precisions_cables = trim($_POST["precisions_cables"] ?? "");

$puissance_souscrite = clean_field($_POST["puissance_souscrite"] ?? "");
$pmax_affichee = clean_field($_POST["pmax_affichee"] ?? "");
$numero_pdl = clean_field($_POST["numero_pdl"] ?? "");

$calibre_disjoncteur = clean_field($_POST["calibre_disjoncteur"] ?? "");
$type_alimentation = clean_field($_POST["type_alimentation"] ?? "");
$marge_disponible = clean_field($_POST["marge_disponible"] ?? "");

$terre_conforme = ($_POST["terre_conforme"] ?? "") === "oui";
$parafoudre_present = ($_POST["parafoudre_present"] ?? "") === "oui";
$ddr_a_ajouter = ($_POST["ddr_a_ajouter"] ?? "") === "oui";

$vehicule_marque = clean_field($_POST["vehicule_marque"] ?? "");
$vehicule_modele = clean_field($_POST["vehicule_modele"] ?? "");
$puissance_charge = clean_field($_POST["puissance_charge"] ?? "");

$observations = trim($_POST["observations"] ?? "");

if (
    $nom === "" ||
    $telephone === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $adresse === "" ||
    $code_postal === "" ||
    $ville === ""
) {
    fail(422, "Merci de vérifier les champs obligatoires (coordonnées et adresse).");
}

// --- Photos jointes ---

function collect_uploaded_files(string $fieldName): array
{
    if (!isset($_FILES[$fieldName])) {
        return [];
    }
    $f = $_FILES[$fieldName];
    // Normalise un champ simple ou un champ tableau ([]) en une liste uniforme.
    if (is_array($f["name"])) {
        $files = [];
        foreach ($f["name"] as $i => $name) {
            if ($name === "") continue;
            $files[] = [
                "name" => $name,
                "tmp_name" => $f["tmp_name"][$i],
                "size" => $f["size"][$i],
                "error" => $f["error"][$i],
            ];
        }
        return $files;
    }
    if ($f["name"] === "" || $f["error"] === UPLOAD_ERR_NO_FILE) {
        return [];
    }
    return [$f];
}

$fileFields = [
    "photo_emplacement[]" => "Emplacement",
    "photo_tableau" => "Tableau électrique",
    "photo_compteur" => "Compteur Linky",
    "photo_disjoncteur" => "Disjoncteur",
];

$attachments = [];
$totalSize = 0;
$finfo = finfo_open(FILEINFO_MIME_TYPE);

foreach ($fileFields as $fieldName => $label) {
    foreach (collect_uploaded_files($fieldName) as $index => $file) {
        if ($file["error"] === UPLOAD_ERR_INI_SIZE || $file["error"] === UPLOAD_ERR_FORM_SIZE) {
            fail(422, "La photo \"{$label}\" est trop volumineuse pour ce serveur. Réduisez sa taille ou contactez-nous directement.");
        }
        if ($file["error"] !== UPLOAD_ERR_OK) {
            continue; // fichier absent ou erreur d'upload mineure : ignoré silencieusement
        }
        if ($file["size"] > MAX_FILE_SIZE) {
            fail(422, "La photo \"{$label}\" dépasse la taille maximale autorisée (5 Mo).");
        }
        $mime = finfo_file($finfo, $file["tmp_name"]);
        if (!isset(ALLOWED_MIME_TYPES[$mime])) {
            fail(422, "Le fichier envoyé pour \"{$label}\" n'est pas une image valide (JPG, PNG ou WebP).");
        }
        $totalSize += $file["size"];
        if ($totalSize > MAX_TOTAL_SIZE) {
            fail(422, "Le total des photos jointes dépasse la taille maximale autorisée (12 Mo).");
        }
        $ext = ALLOWED_MIME_TYPES[$mime];
        $suffix = count($attachments) > 0 && str_ends_with($fieldName, "[]") ? "-" . ($index + 1) : "";
        $attachments[] = [
            "tmp_path" => $file["tmp_name"],
            "mime" => $mime,
            "name" => preg_replace('/[^a-z0-9]+/i', '-', $label) . $suffix . "." . $ext,
        ];
    }
}
finfo_close($finfo);

// --- Corps de l'email ---

$corps = "Fiche de pré-visite technique IRVE — demande de devis\n";
$corps .= "========================================================\n\n";

$corps .= "1. VOS COORDONNÉES\n";
$corps .= "Nom / Société : {$nom}\n";
$corps .= "Téléphone : {$telephone}\n";
$corps .= "Email : {$email}\n";
$corps .= "Date souhaitée : " . display($date_souhaitee) . "\n";
$corps .= "Adresse d'installation : {$adresse}\n";
$corps .= "Code postal / Ville : {$code_postal} {$ville}\n\n";

$corps .= "2. EMPLACEMENT DE LA BORNE\n";
$corps .= "Type de pose : " . display($type_pose) . "\n";
$corps .= "Lieu : " . display($lieu) . "\n";
$corps .= "Nature du support : " . display($nature_support) . "\n";
$corps .= "Distance borne <-> véhicule stationné : " . display($distance_borne_vehicule) . "\n\n";

$corps .= "3. TABLEAU ÉLECTRIQUE\n";
$corps .= "Tableau aux normes (différentiel 30 mA visible) : " . ($tableau_normes ? "oui" : "non précisé") . "\n";
$corps .= "Distance tableau -> borne (estimée) : " . display($distance_tableau_borne, "non renseignée") . ($distance_tableau_borne !== "" ? " m" : "") . "\n";
$corps .= "Passage des câbles : " . display($passage_cables) . "\n";
if ($precisions_cables !== "") {
    $corps .= "Précisions : {$precisions_cables}\n";
}
$corps .= "\n";

$corps .= "4. COMPTEUR LINKY\n";
$corps .= "Puissance souscrite : " . display($puissance_souscrite, "non renseignée") . ($puissance_souscrite !== "" ? " kVA" : "") . "\n";
$corps .= "PMAX affichée : " . display($pmax_affichee, "non renseignée") . ($pmax_affichee !== "" ? " kW" : "") . "\n";
$corps .= "Numéro de compteur (PDL) : " . display($numero_pdl) . "\n\n";

$corps .= "5. DISJONCTEUR DE BRANCHEMENT\n";
$corps .= "Calibre : " . display($calibre_disjoncteur, "non renseigné") . ($calibre_disjoncteur !== "" ? " A" : "") . "\n";
$corps .= "Type : " . display($type_alimentation) . "\n";
$corps .= "Marge disponible estimée après borne : " . display($marge_disponible) . "\n\n";

$corps .= "6. TERRE ET PROTECTIONS\n";
$corps .= "Prise de terre conforme : " . ($terre_conforme ? "oui" : "non précisé") . "\n";
$corps .= "Parafoudre présent : " . ($parafoudre_present ? "oui" : "non précisé") . "\n";
$corps .= "DDR 30 mA type A/B à ajouter : " . ($ddr_a_ajouter ? "oui" : "non précisé") . "\n\n";

$corps .= "7. VÉHICULE\n";
$corps .= "Marque : " . display($vehicule_marque) . "\n";
$corps .= "Modèle : " . display($vehicule_modele) . "\n";
$corps .= "Puissance de charge souhaitée : " . display($puissance_charge) . "\n";

if ($observations !== "") {
    $corps .= "\nOBSERVATIONS COMPLÉMENTAIRES\n{$observations}\n";
}

if (count($attachments) > 0) {
    $corps .= "\n" . count($attachments) . " photo(s) jointe(s) à cet email.\n";
} else {
    $corps .= "\nAucune photo jointe.\n";
}

$sujet = "Nouvelle fiche de pré-visite IRVE — {$nom}";

// --- Envoi (multipart si des photos sont jointes) ---

function send_plain_email(string $to, string $subject, string $body, string $replyTo): bool
{
    $headers = [
        "From: TDEVGREEN Site <no-reply@tdevgreen.fr>",
        "Reply-To: {$replyTo}",
        "Content-Type: text/plain; charset=UTF-8",
    ];
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

function send_email_with_attachments(string $to, string $subject, string $body, string $replyTo, array $attachments): bool
{
    $boundary = md5(uniqid((string) mt_rand(), true));

    $headers = "From: TDEVGREEN Site <no-reply@tdevgreen.fr>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $body . "\r\n";

    foreach ($attachments as $att) {
        $content = @file_get_contents($att["tmp_path"]);
        if ($content === false) continue;
        $encoded = chunk_split(base64_encode($content));
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: {$att['mime']}; name=\"{$att['name']}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$att['name']}\"\r\n\r\n";
        $message .= $encoded . "\r\n";
    }
    $message .= "--{$boundary}--";

    return mail($to, $subject, $message, $headers);
}

$envoye = count($attachments) > 0
    ? send_email_with_attachments($destinataire, $sujet, $corps, $email, $attachments)
    : send_plain_email($destinataire, $sujet, $corps, $email);

if (!$envoye) {
    fail(500, "L'envoi a échoué. Merci de nous contacter directement par email.");
}

echo json_encode(["ok" => true, "message" => "Fiche de pré-visite envoyée."]);
