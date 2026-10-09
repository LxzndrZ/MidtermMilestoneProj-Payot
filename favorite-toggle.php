<?php
// Called by JavaScript (fetch) when a member presses Save / Saved. It answers with JSON, not a page.
require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Use POST.']);
}
if (!Auth::check()) {
    respond(401, ['ok' => false, 'message' => 'Please log in first.']);
}

$recipeId = (int)input_str($_POST, 'recipe_id');
$favorites = new FavoriteRepository(Database::connect());

try {
    $saved = $favorites->toggle(Auth::id(), $recipeId);
} catch (PDOException $ex) {
    respond(500, ['ok' => false, 'message' => 'Could not update your favorites. Please try again.']);
}

if ($saved === null) {
    respond(404, ['ok' => false, 'message' => 'That recipe no longer exists.']);
}

respond(200, ['ok' => true, 'favorited' => $saved, 'saves' => $favorites->countFor($recipeId)]);