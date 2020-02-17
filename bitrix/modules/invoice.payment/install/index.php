<?php
header('Content-Type: text/html; charset=utf-8');

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class invoice_payment extends CModule {
    public function __construct(){

        if(file_exists(__DIR__ . "/version.php")){

            $arModuleVersion = array();

            include_once(__DIR__ . "/version.php");

            $this->MODULE_ID            = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION       = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE  = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME          = Loc::getMessage("INVOICE_PAYMENT_NAME");
            $this->MODULE_DESCRIPTION   = Loc::getMessage("INVOICE_PAYMENT_DESCRIPTION");
            $this->PARTNER_NAME         = Loc::getMessage("INVOICE_PAYMENT_PARTNER_NAME");
            $this->PARTNER_URI          = Loc::getMessage("INVOICE_PAYMENT_PARTNER_URI");
        }

        return false;
    }

    public function DoInstall(){

        global $APPLICATION;

        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){

            $this->InstallFiles();
            $this->InstallDB();

            ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallEvents();
        }else{

            $APPLICATION->ThrowException(
                Loc::getMessage("INVOICE_PAYMENT_INSTALL_ERROR_VERSION")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("INVOICE_PAYMENT_INSTALL_TITLE")." \"".Loc::getMessage("INVOICE_PAYMENT_NAME")."\"",
            __DIR__ . "/step.php"
        );

        return false;
    }

    public function InstallFiles(){
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/'.$this->MODULE_ID.'/install/payment',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/', true, true );

        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/'.$this->MODULE_ID.'/install/invoice_callback',
            $_SERVER['DOCUMENT_ROOT']. '/', true, true );

        CopyDirFiles(
            __DIR__."/assets/scripts",
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID."/",
            true,
            true
        );

        CopyDirFiles(
            __DIR__."/assets/styles",
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID."/",
            true,
            true
        );

        return false;
    }

    public function InstallDB(){

        return false;
    }

    public function InstallEvents(){

        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID
        );

        return false;
    }

    public function DoUninstall(){

        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("INVOICE_PAYMENT_UNINSTALL_TITLE")." \"".Loc::getMessage("INVOICE_PAYMENT_NAME")."\"",
            __DIR__."/unstep.php"
        );

        return false;
    }

    public function UnInstallFiles(){

        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID
        );

        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID
        );

        DeleteDirFilesEx("/bitrix/php_interface/include/sale_payment/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/php_interface/include/sale_payment/invoice_payment");
        DeleteDirFilesEx("/invoice_callback");

        return false;
    }

    public function UnInstallDB(){

        Option::delete($this->MODULE_ID);

        return false;
    }

    public function UnInstallEvents(){

        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID
        );

        return false;
    }

}