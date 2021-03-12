<?php 
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

// Load a csv file - this is the data dictionary for a single form
// downloaded from REDCap as a csv file.
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv($spreadsheet);
$reader->setLoadSheetsOnly(["Test"]);
$reader->setReadDataOnly(true);
$inputFileName = 'demographics.csv';
$worksheetData = $reader->listWorksheetInfo($inputFileName);

$spreadsheet = $reader->load($inputFileName);
$worksheetData = $reader->listWorksheetInfo($inputFileName);

$headers = [];

// Read all the values in the first column of the csv file - these are variable names in the form.
// The fourth column has the data type for the field.
// The $variables array has the variable names as keys and
// 
$variables = [];
$var_col = 1; 
$var_type_col = 4;
$i = 0; $variables = [];
// The first row has variable names.
for ($row = 2; $row < $worksheetData[0]['totalRows']; $row++)
{
    $variableName =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow($var_col,$row)->getValue();
    $variableType = $spreadsheet->getActiveSheet()->getCellByColumnAndRow($var_type_col,$row)->getValue();
    $variables[$variableName] = $variableType;
    $i++;
}

$single_record = [];
$i = 0;
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$formData = []; $formDataCount = 0; // The formData array will hold the data to be imported.
// Each instance of formData will be an assoc array.
/* var_dump($formData[0])
 * formData[0] ::
array(1) { [0]=> array(5) { ["record_id"]=> int(1) ["mrn"]=> string(20) "ZOM7WWucjt0r8jsz6JyD" ["first_name"]=> string(20) "aj8Tvc5JAN11rdsD73Dp" ["last_name"]=> string(20) "ZnPvsB5uRd1iFE23tPgf" ["admt_dt"]=> string(20) "zsyPMfrhvgef0cpUZ1Pe" } }
*/



$patkey = 456; $varValue = ""; // The value generated for the variable
$variableNames = array_keys($variables);

// The for($count) ... loop creates 100 records with randomly generated data for all the variables.
$record_id = 1;
for($count = 0; $count < 10; $count++)
{
    // Loop through the variableNames array and read the variable names
    foreach ($variableNames as $varName)
    {
     // If the variable name is patkey, then start with the preset value(above)
     // and increment it for the next record.
     if(strcmp($varName,'patkey') == 0)
     {
         $varValue= $patkey;  
         $patkey++;
     }
     // record_id fields are record identifiers, and they need to be numbers.
     // for easy human-eye identification
     else if(strcmp($varName, 'record_id') == 0)
     {
         $varValue =  $record_id; // ;
         $record_id++;
     }
     else if(strcmp($variables[$varName] ,'text') == 0)
     {
         // Enter random text
         $varValue = generate_string($permitted_chars,20); // ;
     }
     else if(strcmp($variables[$varName],'radio') == 0)
     {
         $varValue  = 1; // ; // Could toggle randomly between 1 and 0.
     }
     $single_record[$varName] = $varValue;
     $i++;
    }

    $formData[$formDataCount][] = $single_record;
 
    $data = array(
     'token' => '02F9958D98B1FA0995C92310E4318089',
     'content' => 'record',
     'format' => 'json',
     'type' => 'flat',
     'overwriteBehavior' => 'normal',
     'forceAutoNumber' => 'false',
     'data' => '',
     'returnContent' => 'count',
     'returnFormat' => 'json'
    );
    $data['data'] = json_encode($formData[$formDataCount]); 
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://redcap.uky.edu/redcap/api/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    $output = curl_exec($ch);
    print $output;
    curl_close($ch);
 
 
    $formDataCount++;
}


// Generate random string to fill in text values
// https://code.tutsplus.com/tutorials/generate-random-alphanumeric-strings-in-php--cms-32132
function generate_string($input, $strength = 16) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
    
    return $random_string;
}


