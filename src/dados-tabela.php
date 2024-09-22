<?php

require __DIR__ .  DIRECTORY_SEPARATOR . '..'  . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$lines = file(__DIR__ . DIRECTORY_SEPARATOR . 'dados-tabela.txt');

$table = [];
$row = [];

/** Used to skip next line because money is always duplicated */
$skipLines = 0;

foreach ($lines as $line) {
    if ($skipLines > 0) {
        $skipLines--;
        continue;
    }

    if (isRowFilled($row)) {
        var_dump('fillRow', $row);
        $table[] = $row;
        $row = [
            'date' => $row['date']
        ];
    }

    $lineSanitize = trim($line);

    if ($lineSanitize === '') {
        var_dump('linha em branco');
        continue;
    }

    if (stringContainsPaymentIncludeString($lineSanitize)) {
        $skipLines = 3;
        continue;
    }

    if (stringContainsExternalCurrencyValue($lineSanitize)) {
        continue;
    }
    
    if (stringContainsInstallments($lineSanitize)) {
        var_dump('externalCurrency, installments', $lineSanitize);
        continue;
    }

    $date = stringContainsDate($lineSanitize);

    if ($date) {
        var_dump('date', $date);
        $row['date'] = $date;
        continue;
    }

    if (stringContainsMoney($lineSanitize)) {
        var_dump('money', $lineSanitize);
        $value = getValue($lineSanitize);
        $row['value'] = $value;
        $skipLines = 1;
        continue;
    }

    if (stringContainsCategory($lineSanitize)) {
        var_dump('category', $lineSanitize);
        $row['categories'] = $lineSanitize;
        continue;
    }

    var_dump('passou tudo description', $lineSanitize);
    
    $row['description'] = $lineSanitize;
}

var_dump($row, 'oq esta vazio??');
if (isRowFilled($row)) {
    var_dump('fillRow', $row);
    $table[] = $row;
    $row = [];
}

$data = generateCSVData($table);

file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dados-tabela.csv', $data);

generateXlsxFile($table);

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
 * Check if the stirng is "Inclusao de pagemento"
 *
 * @param string $string
 * @return boolean
 */
function stringContainsPaymentIncludeString(string $string): bool
{
    return $string === 'Inclusao de Pagamento';  
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
    $stringCategories = explode(' / ', $string);
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
        "Especialidade varejo",
        "IOF Transacoes Exterior R$",
        "T&E"
    ];

    foreach ($categories as $category) {
        if (in_array($category, $stringCategories)) {
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
    $signal = (substr($string, 0, 2) === 'R$') ? 1 : -1;

    $money = $signal === 1 ? substr($string, 2) : substr($string, 3);
    
    $money = str_replace('.', '', $money);
    $money = str_replace(',', '.', $money);
    return (float) $money * $signal;
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

/**
 * Generate XLSX file
 *
 * @param array $table
 * @return void
 */
function generateXlsxFile(array $table): void {
    $spreadsheet = new Spreadsheet();
    $activeWorksheet = $spreadsheet->getActiveSheet();
    
    $activeWorksheet->setCellValue('A1', 'Data')
        ->setCellValue('B1', 'Descrição')
        ->setCellValue('C1', 'Valor')
        ->setCellValue('D1', 'Realizado')
        ->setCellValue('E1', 'Categoria');

    $i = 1;
    foreach ($table as $row) {
        $i++;

        $activeWorksheet->setCellValue("A{$i}", $row['date']);
        $activeWorksheet->setCellValue("B{$i}", $row['description']);
        $activeWorksheet->setCellValue("C{$i}", $row['value']);
        $activeWorksheet->setCellValue("D{$i}", 'Não');
        $activeWorksheet->setCellValue("E{$i}", $row['categories']);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save(__DIR__ . DIRECTORY_SEPARATOR . 'dados-tabela.xlsx');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
}

/**
 * Check if all fields in the row are filled
 *
 * @param array $row
 * @return boolean
 */
function isRowFilled(array $row): bool {
    return !empty($row['date']) && !empty($row['description']) && !empty($row['value']) && !empty($row['categories']);
}