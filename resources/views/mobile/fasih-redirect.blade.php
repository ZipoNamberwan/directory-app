<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $url }}">
    <title>Mengalihkan...</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
            text-align: center;
        }
    </style>
</head>

<body>
    <div>
        <p>Mengalihkan ke FASIH...</p>
        <p><a href="{{ $url }}">Klik di sini jika tidak dialihkan otomatis</a></p>
    </div>
    <script>
        window.location.replace(@json($url));
    </script>
</body>

</html>
