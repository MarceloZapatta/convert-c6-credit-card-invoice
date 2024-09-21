<?php

require __DIR__ .  DIRECTORY_SEPARATOR . '..'  . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';


$lines = file(__DIR__ . DIRECTORY_SEPARATOR . 'dados-tabela.txt');

$table = [];
$row = [];

foreach ($lines as $line) {
    $lineSanitize = trim($line);

    if ($lineSanitize === '') {
        continue;
    }

    $date = stringContainsDate($lineSanitize);

    if ($date) {
        if (!empty($row)) {
            $table[] = $row;
            $row = [];
        }

        $row['date'] = $date;
        continue;
    }

    if (stringContainsMoney($lineSanitize)) {
        $value = getValue($lineSanitize);
        $row['value'] = $value;
        continue;
    }

    if (stringContainsCategory($lineSanitize)) {
        $row['categories'] = $lineSanitize;
        continue;
    }

    if (stringContainsExternalCurrencyValue($lineSanitize) || stringContainsInstallments($lineSanitize)) {
        continue;
    }

    $row['description'] = $lineSanitize;
}

$table[] = $row;

$data = generateCSVData($table);

file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dados-tabela.csv', $data);

echo 'File exported succesfully!!';

/**
 * Check if the string contain a string
 *
 * @param string $string
 * @return string|null
 */
function stringContainsDate(string $string): ?string
{
    $pattern = '/\b([0-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{2}\b/';
    $matches = [];
    preg_match($pattern, $string, $matches);

    return $matches[0] ?? null;
}

/**
 * Check if the string contains USD
 *
 * @param string $string
 * @return boolean
 */
function stringContainsExternalCurrencyValue(string $string): bool
{
    return strpos($string, 'USD ') !== false;
}

/**
 * Check if the string contains "R$" symbols
 *
 * @param string $string
 * @return boolean
 */
function stringContainsMoney(string $string): bool
{
    return substr($string, 0, 2) === 'R$' || substr($string, 0, 3) === '-R$';
}

/**
 * Check if the string contains any of the categories
 *
 * @param string $string
 * @return boolean
 */
function stringContainsCategory(string $string): bool
{
    $categories = [
        "Supermercados",
        "Mercearia",
        "Padarias",
        "Lojas de Conveniência",
        "Restaurante",
        "Lanchonete",
        "Bar",
        "Serviços de telecomunicações",
        "Inclusão de Pagamento",
        "Seguro",
        "Serviços Profissionais",
        "Relacionados a Automotivo",
        "Elétrico",
        "Educacional",
        "Assistência médica e odontológica",
        "Materiais de construção para casa",
        "Refund",
        "Casa",
        "Escritório Mobiliário",
        "Serviços pessoais",
        "Departamento",
        "Desconto",
        "Associação",
        "Empresa para empresa",
        "Arte",
        "Artesanato",
        "Passatempo",
        "Recreativo",
        "Entretenimento",
        "Vestuário",
        "Roupas",
        "Especialidade varejo"
    ];

    foreach ($categories as $category) {
        if (strpos($string, $category) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Check if the string contains installments
 *
 * @param string $string
 * @return boolean
 */
function stringContainsInstallments(string $string): bool
{

    $pattern = '/EM \d+X/';
    $matches = [];
    return preg_match_all($pattern, $string, $matches);
}

/**
 * Check if the string contains "R$" symbols
 *
 * @param string $string
 * @return boolean
 */
function getValue(string $string): float
{
    $money = substr($string, 2);
    $money = str_replace('.', '', $money);
    $money = str_replace(',', '.', $money);
    return (float) $money;
}

/**
 * Generate a csv string comma separeted
 *
 * @param array $table
 * @return string
 */
function generateCSVData(array $table): string
{
    $csvData = "Data,Descricao,Categoria,Valor\n";

    foreach ($table as $row) {
        $csvData .= $row['date'] . ',' . $row['description'] . ',' . $row['categories'] . ',' . $row['value'] . "\n";
    }

    return $csvData;
}
