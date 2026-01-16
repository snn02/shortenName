<?php

declare(strict_types=1);

require __DIR__ . '/src/name_format.php';

$inputText = '';
$resultText = '';
$removePersonal = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputText = isset($_POST['source_text']) ? (string) $_POST['source_text'] : '';
    $removePersonal = isset($_POST['remove_personal']);

    if ($removePersonal) {
        $resultText = anonymizePersonalData($inputText);
    } else {
        $resultText = $inputText;
    }
}

function anonymizePersonalData(string $text): string
{
    $text = preg_replace(
        '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu',
        'email@email.com',
        $text
    ) ?? $text;

    $text = preg_replace(
        '/\+?\d[\d\s().-]{8,}\d/u',
        '999-999-99-99',
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\b[А-ЯЁ][а-яё]+(?:\s+[А-ЯЁ][а-яё]+){0,2}\b/u',
        static function (array $matches): string {
            return formatNameWithSurnameInitial($matches[0]);
        },
        $text
    ) ?? $text;

    return $text;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Обработка текста</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #f7f7f7;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin-top: 0;
        }
        textarea {
            width: 100%;
            min-height: 160px;
            resize: vertical;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .row {
            margin-bottom: 16px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        button {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .primary {
            background: #2f6fed;
            color: #fff;
        }
        .secondary {
            background: #e5e5e5;
        }
        label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Обработка текста</h1>
    <form method="post">
        <div class="row">
            <label for="source_text">Исходный текст</label>
            <textarea id="source_text" name="source_text" placeholder="Введите текст..."><?= htmlspecialchars($inputText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </div>
        <div class="row">
            <label>
                <input type="checkbox" name="remove_personal" <?= $removePersonal ? 'checked' : '' ?>>
                Удалять персональные данные
            </label>
        </div>
        <div class="row actions">
            <button type="submit" class="primary">Обработать</button>
            <button type="button" class="secondary" id="clear-result">Очистить результат</button>
        </div>
        <div class="row">
            <label for="result_text">Результат</label>
            <textarea id="result_text" name="result_text" readonly><?= htmlspecialchars($resultText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </div>
    </form>
</div>
<script>
    const clearButton = document.getElementById('clear-result');
    const resultField = document.getElementById('result_text');

    clearButton.addEventListener('click', () => {
        resultField.value = '';
    });
</script>
</body>
</html>
