<? // Подключаем необходимые модули
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');

    // ID товара для поиска торговых предложений
    $productId = $item['ID'];

    // ID инфоблока торговых предложений, например 3
    $offersIblockId = 3;

    // Получаем список торговых предложений, привязанных к товару с ID = $productId
    $arSelect = array('ID', 'NAME', 'PROPERTY_CML2_LINK', 'PROPERTY_COLOR_REF', 'PROPERTY_MORE_PHOTO');
    $arFilter = array('IBLOCK_ID' => $offersIblockId, 'ACTIVE' => 'Y', 'PROPERTY_CML2_LINK' => $productId);
    $offersResult = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
    // Параметры для изменения размера
    $resizeParams = array(
        'width' => 500,
        'height' => 400,
        'type' => BX_RESIZE_IMAGE_PROPORTIONAL,
    );

    // Создаем массив для хранения предложений
    $offers = array();
    $colorRefsDubl = array();
    $photoOffers = array();
    while ($offer = $offersResult->fetch()) {
        // Добавляем значение свойства "COLOR_REF" в массив $colorRefs как элемент, а не массив
        if (!empty($offer["PROPERTY_COLOR_REF_VALUE"])) {
            if (is_array($offer["PROPERTY_COLOR_REF_VALUE"])) {
                // Здесь мы не ожидаем массив, поэтому берем только первый элемент
                $colorRefsDubl[] = reset($offer["PROPERTY_COLOR_REF_VALUE"]);
            } else {
                $colorRefsDubl[] = $offer["PROPERTY_COLOR_REF_VALUE"];
            }
        }

        // Обработка свойства "MORE_PHOTO"
        if (!empty($offer['PROPERTY_MORE_PHOTO_VALUE'])) {
            if (is_array($offer['PROPERTY_MORE_PHOTO_VALUE'])) {
                // Так как нужна только первая фотография, получаем первый элемент массива

                $imageFileId = reset($offer['PROPERTY_MORE_PHOTO_VALUE']);
            } else {
                $imageFileId = $offer['PROPERTY_MORE_PHOTO_VALUE'];
            }

            $photo = CFile::ResizeImageGet(
                $imageFileId,
                array('width' => $resizeParams['width'], 'height' => $resizeParams['height']),
                $resizeParams['type'],
                true
            );


            // Добавляем ссылку на фото в массив $photoOffers
            $photoOffers[$offer['ID']] = $photo; // Используем ID предложения как ключ массива
        }
    }