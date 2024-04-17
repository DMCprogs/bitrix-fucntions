<?

$currentUrl = $APPLICATION-> GetCurPage(false);
$urlWithoutProtocol = str_replace("https://", "", $currentUrl);
$segments = explode('/', $urlWithoutProtocol);
$currentDomain = $_SERVER['SERVER_NAME'];
// Извлечь ID текущего подраздела из URL
$sub_section = $segments[2]; 

$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById(6)->fetch();
$entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
$entity_data_class = $entity->getDataClass();

$fullUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$title="Каталог техники";
$desk="";
$GeParams="";
$getParUrl=$_SERVER['QUERY_STRING'];
$rsData = $entity_data_class::getList(array(
    'select' => array('*')
));
    $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url = explode('?', $url);
    $url = $url[0];
while ($arRes = $rsData->fetch()) {
	if(!empty($getParUrl)){
		if ($getParUrl == $arRes['UF_URL_CHECK']) {
			$check = true;
			if (!empty($arRes['UF_SHORT_URL'])) {
			
					$ShorUrl=$arRes['UF_SHORT_URL'];
				$FullShortUrl=$url.$ShorUrl;
					header("Location: $FullShortUrl");
					exit;
			}  else {
				$ShorUrl=$arRes['UF_SHORT_URL'];
		$GeParams=$arRes['UF_GET_PARAMS'];
		$title=$arRes['UF_H1'];
		$desk=$arRes['UF_OPISANIE'];
		if(!empty($arRes['UF_TITLE'])){
			$APPLICATION->SetPageProperty("title", $arRes['UF_TITLE']);
		}
		if(!empty($arRes['UF_DESK'])){
			$APPLICATION->SetPageProperty("description", $arRes['UF_DESK']);
		}
		if(!empty($arRes['UF_KEYWORDS'])){
			$APPLICATION->SetPageProperty("keywords", $arRes['UF_KEYWORDS']);
		}
			}
		}
	}
	
   
    if ($arRes['UF_URL']==$fullUrl) {
        // echo"все прекрасно работает";
		$ShorUrl=$arRes['UF_SHORT_URL'];
		$GeParams=$arRes['UF_GET_PARAMS'];
		$title=$arRes['UF_H1'];
		$desk=$arRes['UF_OPISANIE'];
		if(!empty($arRes['UF_TITLE'])){
			$APPLICATION->SetPageProperty("title", $arRes['UF_TITLE']);
		}
		if(!empty($arRes['UF_DESK'])){
			$APPLICATION->SetPageProperty("description", $arRes['UF_DESK']);
		}
		if(!empty($arRes['UF_KEYWORDS'])){
			$APPLICATION->SetPageProperty("keywords", $arRes['UF_KEYWORDS']);
		}
        
       
       
    } 
	
	if (!isset($ShorUrl)) {
		// echo"все прекрасно работает 2";
		$cleanedUrl = rtrim($arRes['UF_SHORT_URL'], '/');
		if($fullUrl!="https://port-vostok.ru/catalog/"&&$sub_section == $arRes['UF_SHORT_URL']&&empty($getParUrl)){
			// echo $sub_section;
			// echo"все прекрасно работает 3";
		$ShorUrl=$arRes['UF_SHORT_URL'];
		$GeParams=$arRes['UF_GET_PARAMS'];
		$title=$arRes['UF_H1'];
		$desk=$arRes['UF_OPISANIE'];
		if(!empty($arRes['UF_TITLE'])){
			$APPLICATION->SetPageProperty("title", $arRes['UF_TITLE']);
		}
		if(!empty($arRes['UF_DESK'])){
			$APPLICATION->SetPageProperty("description", $arRes['UF_DESK']);
		}
		if(!empty($arRes['UF_KEYWORDS'])){
			$APPLICATION->SetPageProperty("keywords", $arRes['UF_KEYWORDS']);
		}
		
		}
		else if ($sub_section == $cleanedUrl) {
			echo" должно сработать";
			$ShorUrl=$arRes['UF_SHORT_URL'];
			$GeParams=$arRes['UF_GET_PARAMS'];
			$title=$arRes['UF_H1'];
			$desk=$arRes['UF_OPISANIE'];
			if(!empty($arRes['UF_TITLE'])){
				$APPLICATION->SetPageProperty("title", $arRes['UF_TITLE']);
			}
			if(!empty($arRes['UF_DESK'])){
				$APPLICATION->SetPageProperty("description", $arRes['UF_DESK']);
			}
			if(!empty($arRes['UF_KEYWORDS'])){
				$APPLICATION->SetPageProperty("description", $arRes['UF_DESK']);
			}
			
		}
	} 
}


$geParamsArray = [];
parse_str($GeParams, $geParamsArray);
//$filtr['SECTION_CODE']=("-",htmlspecialcharsback($_GET["type"]));

$arSelect = Array("ID", "NAME", "PROPERTY_FORMULA","PROPERTY_BRAND","IBLOCK_SECTION_ID","IBLOCK_SECTION","PROPERTY_AVAILABLE");
$arFilter = Array("IBLOCK_ID"=>1, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);
while($ob = $res->GetNextElement())
{
 $arFields[] = $ob->GetFields();
}
foreach ($arFields as &$arItem) {
	switch ($arItem["IBLOCK_SECTION_ID"]){
	case "1": {
	$arItem["CODE"] = "samosval";
	break;
	}
	case "2": {
	$arItem["CODE"] = "tyagach";
	break;
	}
	case "3": {
	$arItem["CODE"] = "spec";
	break;	
	}
	}
}