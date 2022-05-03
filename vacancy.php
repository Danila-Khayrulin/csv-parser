<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!($USER->IsAdmin())) exit;
CModule::IncludeModule("iblock");

$IBLOCK_ID = 7;
$el = new CIBlockElement;
$arProps = [];

$rsProp = CIBlockPropertyEnum::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => $IBLOCK_ID]
);
while ($arProp = $rsProp->Fetch()) {
    $key = trim($arProp['VALUE']);
    $arProps[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
}

$flag = true;
if (($handle = fopen("vacancy.csv", "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if ($flag) {
            $flag = false;
            continue;
        }
        $PROP['ACTIVITY'] = $data[9];
        $PROP['FIELD'] = $data[11];
        $PROP['OFFICE'] = $data[1];
        $PROP['LOCATION'] = $data[2];
        $PROP['REQUIRE'] = $data[4];
        $PROP['DUTY'] = $data[5];
        $PROP['CONDITIONS'] = $data[6];
        $PROP['EMAIL'] = $data[12];
        $PROP['DATE'] = date('d.m.Y');
        $PROP['TYPE'] = $data[8];
        $PROP['SALARY_TYPE'] = '';
        $PROP['SALARY_VALUE'] = $data[7];
        $PROP['SCHEDULE'] = $data[10];

        foreach ($PROP as $key => &$value) {
            if (stripos($value, '•') !== false) {
                $value = explode('•', $value);
                array_splice($value, 0, 1);
                foreach ($value as &$str) {
                    $str = trim($str);
                }
            }
            foreach ($arProps[$key] as $propKey => $propVal) {
                if (stripos($propKey, $value) !== false) {
                    $value = $propVal;
                    break;
                }
                if (similar_text($propKey, $value) > 50) {
                    $value = $propVal;
                }
            }
        }

        if ($PROP['SALARY_VALUE'] == 'по договоренности') {
            $PROP['SALARY_VALUE'] = '';
            $PROP['SALARY_TYPE'] = array('VALUE' => 404);
        }
        else {
            $arSalary = explode(' ', $PROP['SALARY_VALUE']);
            if ($arSalary['0'] == 'от') {
                $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['ОТ'];
            }
            elseif ($arSalary['0'] == 'до') {
                $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['ДО']; 
            }
            else {
                $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['='];
            }
        }

        $arLoadProductArray = array(
            "MODIFIED_BY"       => $USER->GetID(),
            "IBLOCK_ID"         => $IBLOCK_ID,
            "PROPERTY_VALUES"   => $PROP,
            "NAME"              => $data[3],
        );

        if ($PRODUCT_ID = $el->Add($arLoadProductArray))
            echo "New element with ID: " . $PRODUCT_ID;
        else {
            echo "Error: " . $el->LAST_ERROR;
        }
    }
}
?>