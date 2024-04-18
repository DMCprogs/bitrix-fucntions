<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("NO_AGENT_CHECK", true);

use Bitrix\Catalog\PriceTable;
use Bitrix\Iblock\ORM\Query;
use Bitrix\Sale\StoreProductTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserGroupTable;



require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// ------------------------------------------
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
CModule::IncludeModule("sale");
$items = [];

// SKU
$elements = \Bitrix\Iblock\Elements\ElementSkuTable::query()
	->setSelect([
		"ID",
		"NAME",
		"VOLUME" => "OBEM.VALUE",
		"CATALOG_ELEMENT_ID" => "CML2_LINK.VALUE",
		// "BRAND" => "CML2_LINK.ELEMENT.BREND.VALUE", // slow
		"ARTICLE" => "CML2_ARTICLE.VALUE",
	])
	->where('ACTIVE', 'Y')
	// ->where('CML2_LINK.ACTIVE', 'Y')
	->exec()->fetchAll();

$brands4sku = \Bitrix\Iblock\Elements\ElementCatalogTable::query()
	->setSelect([
		"ID",
		"BRAND" => "BREND.VALUE"
	])
	->where('ACTIVE', 'Y')
	->where('ID', 'in', array_unique(array_column($elements, "CATALOG_ELEMENT_ID")))
	->exec()->fetchAll();

$brands4sku = array_combine(
	array_column($brands4sku, "ID"),
	$brands4sku
);

foreach ($elements as &$sku) {
	$sku["BRAND"] = $brands4sku[$sku["CATALOG_ELEMENT_ID"]]["BRAND"];
}
unset($sku);
unset($brands4sku);

// not sku
$soloElements = \Bitrix\Iblock\Elements\ElementCatalogTable::query()
	->setSelect([
		"ID",
		"NAME",
		"VOLUME" => "OBEM.VALUE",
		"ARTICLE" => "CML2_ARTICLE.VALUE",
		"BRAND" => "BREND.VALUE"
	])
	->where('ACTIVE', 'Y')
	->whereNot('ID', 'in', array_unique(array_column($elements, "CATALOG_ELEMENT_ID")))
	->exec()->fetchAll();

$elements = array_merge($elements, $soloElements);

unset($soloElements);

$pricesTypes = \Bitrix\Catalog\GroupTable::query()
	->setSelect(["ID", "XML_ID", "NAME"])
	// ->where("BASE", "Y")
	->where(
		Query::filter()
			->logic("or")
			->where("NAME", "Оптовая 9")
			->where("NAME", "Дилерская")
			->where("BASE", "Y")
			->where("NAME", "интернет-розница")
	)
	->exec()->fetchAll();

$pricesTypes = array_combine(
	array_column($pricesTypes, "ID"),
	$pricesTypes
);

$pricesTmp = PriceTable::query()
	->setSelect(["PRODUCT_ID", "PRICE","CATALOG_GROUP_ID"])
	->where("PRODUCT_ID", "in", array_unique(array_column($elements, "ID")))
	->where("CATALOG_GROUP_ID", "in", array_column($pricesTypes, "ID"))
	->exec()->fetchAll();

$prices = array_keys(array_unique(array_column($pricesTmp, "PRODUCT_ID")));

foreach ($pricesTmp as $price) {
	$prices[$price["PRODUCT_ID"]][$pricesTypes[$price["CATALOG_GROUP_ID"]]["NAME"]] = $price["PRICE"];
}
unset($pricesTmp);

$arStores = \Bitrix\Catalog\StoreTable::getList()->fetchAll();
$arStores = array_combine(
	array_column($arStores, "ID"),
	$arStores
);
$rsAmounts = StoreProductTable::getList([
	"filter" => ["PRODUCT_ID" => array_unique(array_column($elements, "ID"))]
]);
$amounts = [];
while ($row = $rsAmounts->fetch()) {
	$amounts[$row["PRODUCT_ID"]][$row["STORE_ID"]] = (string) $row["AMOUNT"];
}
unset($rsAmounts);
// unset($arStores);

foreach ($elements as &$product) {
	$pid = $product["ID"];
	$product = [
		// "ID" => $pid,
		"NAME" => trim($product["NAME"]),
		"BRAND" => $product["BRAND"],
		"PRICE" => $prices[$product["ID"]]["BASE"],
		"VOLUME" => $product["VOLUME"],
		"ARTICLE" => $product["ARTICLE"],
		// "QUANTITY" => $amounts[$product["ID"]],
	];

	foreach ($arStores as $sid => $store) {
		$product[$store["TITLE"]] =  $amounts[$pid][$sid] ?? "0";
	}

	$product["PRICE_OPT"] = $prices[$pid]["Оптовая 9"];
	$product["PRICE_DIALER"] = $prices[$pid]["Дилерская"];
	if ($prices[$pid]["интернет-розница"] > 0) {
		$product["PRICE"] = $prices[$pid]["интернет-розница"];
	}
}


unset($product);
unset($amounts);
unset($prices);




$firstLine = [
		"NAME" => "Наименование",
		"BRAND" => "Бренд",
		"PRICE" => "Цена р.",
		"VOLUME" => "Объем",
		"ARTICLE" => "Артикул",
		// "QUANTITY" => "Остатки"
];

foreach ($arStores as $store) {
	$firstLine[] =  $store["TITLE"];
}
unset($arStores);

$fp = fopen($filePath, 'w+');
$fpOpt = fopen($filePathOpt, 'w+');
$fpDealer = fopen($filePathDealer, 'w+');
fputcsv($fp, $firstLine, ";");
fputcsv($fpOpt, $firstLine, ";");
fputcsv($fpDealer, $firstLine, ";");

$optPrice = [];
$dealerPrice = [];
foreach ($elements as $key => $value) {
	$optPrice[$key] = $value["PRICE"];
	if ($value["PRICE_OPT"] > 0) {
		$optPrice[$key] = $value["PRICE_OPT"];
	}
	$dealerPrice[$key] = $value["PRICE"];
	if ($value["PRICE_DIALER"] > 0) {
		$dealerPrice[$key] = $value["PRICE_DIALER"];
	}
	unset($elements[$key]["PRICE_OPT"]);
	unset($elements[$key]["PRICE_DIALER"]);

	fputcsv($fp, $elements[$key], ";");
}

// отдельные ибо писать в три файла долго
foreach ($elements as $key => $value) {
	$elements[$key]["PRICE"] = $optPrice[$key];
	fputcsv($fpOpt, $elements[$key], ";");
}

foreach ($elements as $key => $value) {
	$elements[$key]["PRICE"] = $dealerPrice[$key];
	fputcsv($fpDealer, $elements[$key], ";");
}

$files = [
	[
		"stream" => $fp,
		"path" => $filePath,
	],
	[
		"stream" => $fpOpt,
		"path" => $filePathOpt,
	],
	[
		"stream" => $fpDealer,
		"path" => $filePathDealer,
	],
];

foreach ($files as $file) {
	rewind($file["stream"]);
	$data = fread($file["stream"], 1048576);
	$data = iconv('UTF-8', 'Windows-1251', $data);
	fclose($file["stream"]);
	file_put_contents($file["path"], $data);
	unset($data);
}




$rsEnum = CUserFieldEnum::GetList(
	["SORT" => "ASC"],
	["USER_FIELD_NAME" => "UF_PRICE_LIST"]
);

$enums = [];
while ($arEnum = $rsEnum->Fetch()) {
	$enums[$arEnum["XML_ID"]] = $arEnum["ID"];
}

$emails = [];


$rsUsers = CUser::GetList(
	($by = "id"),
	($order = "desc"),
	["UF_PRICE_LIST" => $enums["DAILY"]],
	["SELECT" => ["UF_PRICE_LIST", "UF_ADDITIONAL_EMAIL"]]
);
while ($user = $rsUsers->Fetch()) {
	$emails[$user["ID"]][] = $user["EMAIL"];
	foreach ($user["UF_ADDITIONAL_EMAIL"] as $key => $value) {
		$emails[$user["ID"]][] = $value;
	}
}

// Если понедельник то отправляем еженедельный
if (date('D') === 'Mon') {
	$rsUsers = CUser::GetList(
		($by = "id"),
		($order = "asc"),
		["UF_PRICE_LIST" => $enums["WEEKLY"]],
		["SELECT" => ["UF_PRICE_LIST", "UF_ADDITIONAL_EMAIL"]]
	);
	while ($user = $rsUsers->Fetch()) {
		$emails[$user["ID"]][] = $user["EMAIL"];
		foreach ($user["UF_ADDITIONAL_EMAIL"] as $key => $value) {
			$emails[$user["ID"]][] = $value;
		}
	}
}


$groupsTmp = UserGroupTable::query()
	->where("USER_ID", "in", array_keys($emails))
	->setSelect(["USER_ID", "GROUP_ID"])
	->exec()->fetchAll();

define("OPR_GROUP", 8);
define("DEALER_GROUP", 7);

$groups = [];
foreach ($groupsTmp as $user) {
	$groups[$user["USER_ID"]][] = $user["GROUP_ID"];
}
unset($groupsTmp);



foreach ($emails as $id => $userEmails) {
	$path = $filePath;
	if (in_array(DEALER_GROUP, $groups[$id])) {
		$path = $filePathDealer;
	}
	if (in_array(OPR_GROUP, $groups[$id])) {
		$path = $filePathOpt;
	}

	foreach ($userEmails as $key => $email) {
		CEvent::Send(
			"SALT_SEND_PRICE_LIST", // mail event name
			SITE_ID,
			[
				"EMAIL_TO" => $email,
				"DATE" => date("d.m.Y"),
				"REMOVE_TEXT" => "Вы можете <a href=//paritet-sib.ru/personal/>отписаться от рассылки в личном кабинете</a>"
			],
			"Y",
			"",
			[$path]
		);
	}

}


CMain::FinalActions();