<?php

$artistName = "System of a Down";
$albumName = "Hypnotize";

function GetApiResult(?string $artistName, ?string $albumName): array
{
    $data = [];

    $queryParts = [];
    if (!empty($artistName)) {
        $queryParts[] = urlencode($artistName);
    }
    if (!empty($albumName)) {
        $queryParts[] = urlencode($albumName);
    }
    $queryString = implode("+", $queryParts);

    if (empty($queryString)) {
        return $data;
    }

    $deezerApiUrl = "https://api.deezer.com/search?q=" . $queryString;
    $response = file_get_contents($deezerApiUrl);

    if ($response !== FALSE) {
        $data = json_decode($response, true);
    }

    return $data;
}

function GetAlbumsFromArtist(string $artistName): array
{
    $data = GetApiResult($artistName, null)["data"];
    $albums = [];

    foreach ($data as $item) {
        $albumTitle = $item["album"]["title"];
        if (!in_array($albumTitle, array_column($albums, "title"))) {
            $albums[] = [
                "cover" => $item["album"]["cover_xl"],
                "title" => $item["album"]["title"],
                "artist" => $data[1]["artist"]["name"]
            ];
        }
    }

    return $albums;
}

function GetAlbumId(string $artistName, string $albumName): ?int
{
    $id = null;

    $query = urlencode("$artistName $albumName");
    $response = file_get_contents("https://api.deezer.com/search?q=$query");

    $data = json_decode($response, true);

    foreach ($data['data'] as $item) {
        if (
            isset($item['album']) &&
            strtolower($item['album']['title']) === strtolower($albumName) &&
            strtolower($item['artist']['name']) === strtolower($artistName)
        ) {
            $id = $item['album']['id']; // Retourner l'ID de l'album
        }
    }

    return $id;
}


function GetSongsFromAlbum(string $artistName, string $albumName): array
{
    $albumId = GetAlbumId($artistName, $albumName);

    $response = file_get_contents("https://api.deezer.com/album/$albumId/tracks");

    if ($response === FALSE) {
        die('Erreur lors de la récupération des morceaux de l\'album');
    }

    $data = json_decode($response, true);
    $songs = [];

    foreach ($data['data'] as $song) {
        $songs[] = [
            'title' => $song['title'],
            'duration' => $song['duration'],
            'track_position' => $song['track_position'],
            'preview' => $song['preview'],
            'id' => $song['id'],
            'artist' => $song['artist']['name'],
        ];
    }

    return $songs;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Deezer API test</title>
</head>

<body>
    <h1>Albums</h1>
    <div class="card-container">
        <?php foreach (GetAlbumsFromArtist($artistName) as $album) { ?>
            <div class="card">
                <img src="<?= $album["cover"] ?>" alt="Album Cover">
                <div class="card-info">
                    <h3><?= $album["title"] ?> - <?= $album["artist"] ?></h3>
                </div>
            </div>
        <?php } ?>
    </div>
    <h1>Songs from <?= $artistName ?> - <?= $albumName ?></h1>
    <div class="songs">
        <table border="1">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Duration (Seconds)</th>
                    <th>Preview</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (GetSongsFromAlbum($artistName, $albumName) as $song) { ?>
                    <tr>
                        <td><?= $song['title'] ?></td>
                        <td><?= $song['duration'] ?> seconds</td>
                        <td>
                            <audio controls>
                                <source src="<?= $song['preview'] ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>

</html>