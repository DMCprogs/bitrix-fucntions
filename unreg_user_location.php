<?// Получаем текущий URI.
$currentUri = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri();

// Проверяем, соответствует ли текущий URI требуемому пути.
$isTargetPage = preg_match("#^/personal/order/make/#", $currentUri);

// Если не на требуемой странице - выходим из метода.
if ($isTargetPage) {
  $eventInstance = new SaleOrderEvents();
  \Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible(
    'sale', 
    'OnSaleComponentOrderProperties', 
    [$eventInstance, 'fillLocation']
  );
  
 
}
class SaleOrderEvents 
{
  function fillLocation(&$arUserResult, $request, &$arParams, &$arResult) 
  {
      

      // Проверка, не авторизован ли пользователь.
      if (!\Bitrix\Main\UserTable::getList([
          'select' => ['ID'],
          'filter' => ['=ID' => $GLOBALS['USER']->GetID(), 'ACTIVE' => 'Y'],
          'limit' => 1
      ])->fetch()) {
          $registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
          $orderClassName = $registry->getOrderClassName();
          $order = $orderClassName::create(\Bitrix\Main\Application::getInstance()->getContext()->getSite());
          $propertyCollection = $order->getPropertyCollection();

          foreach ($propertyCollection as $property) {
              if ($property->isUtil())
                  continue;

              $arProperty = $property->getProperty();
              if(
                  $arProperty['TYPE'] === 'LOCATION' 
                  && array_key_exists($arProperty['ID'],$arUserResult["ORDER_PROP"])
                  && !$request->getPost("ORDER_PROP_".$arProperty['ID'])
                  && (
                      !is_array($arOrder=$request->getPost("order"))
                      || !$arOrder["ORDER_PROP_".$arProperty['ID']]
                  )
              ) {
                  $arUserResult["ORDER_PROP"][$arProperty['ID']] = CURRENT_CITY_CODE;
              }
          }
      }
  }
}