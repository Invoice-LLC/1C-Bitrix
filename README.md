<h1>Invoice 1C-Bitrix Module</h1>

**Кодировка должна быть win-1251**

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.1C-Bitrix/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. Перейдите во вкладку **Marketplace->Установленные решения** и установите модуль
![Imgur](https://imgur.com/B7TWFJE.png)
3. Перейдите во вкладку **Настройки->Настройки продукта->Настройки модулей->Платежная система Invoice**, затем введите логин от личного кабинета и ключ API
![Imgur](https://imgur.com/X8T6JUA.png)
4. Перейдите во вкладку **Магазин->Настройки->Платежные системы** и нажмите "Добавить платежную систему"
![Imgur](https://imgur.com/OBpBvWF.png)
5. Выберите обработчик **"Invoice"**, затем сохраните настройки
![Imgur](https://imgur.com/7TwBlnc.png)
6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/invoice_callback/callback.php**
![Imgur](https://imgur.com/LZEozhf.png)
