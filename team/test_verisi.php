<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Gelen Veri Testi</title>
    <style>body { font-family: monospace; padding: 15px; }</style>
</head>
<body>
    <h1>Formdan Gelen Veriler</h1>
    <hr>

    <h2>$_POST Verisi:</h2>
    <pre><?php print_r($_POST); ?></pre>

    <hr>

    <h2>$_FILES Verisi:</h2>
    <pre><?php print_r($_FILES); ?></pre>
</body>
</html>