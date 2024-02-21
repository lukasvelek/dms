<?php

namespace DMS\Constants;

/**
 * Ribbon constants
 * 
 * @author Lukas Velek
 */
class Ribbons {
    public const ROOT_HOME = 0;
    public const ROOT_DOCUMENTS = 1;
    public const ROOT_PROCESSES = 2;
    public const ROOT_ARCHIVE = 3;
    public const ROOT_SETTINGS = 4;
    
    public const DOCUMENTS_ALL_DOCUMENTS = 5;
    public const DOCUMENTS_WAITING_FOR_ARCHIVATION = 6;
    public const DOCUMENTS_NEW_DOCUMENTS = 7;
    public const DOCUMENTS_SPLITTER = 8;
    
    public const PROCESSES_STARTED_BY_ME = 9;
    public const PROCESSES_WAITING_FOR_ME = 10;
    public const PROCESSES_FINISHED = 11;

    public const SETTINGS_DASHBOARD = 12;
    public const SETTINGS_DOCUMENT_FOLDERS = 13;
    public const SETTINGS_USERS = 14;
    public const SETTINGS_GROUPS = 15;
    public const SETTINGS_METADATA = 16;
    public const SETTINGS_SYSTEM = 17;
    public const SETTINGS_SERVICES = 18;
    public const SETTINGS_DASHBOARD_WIDGETS = 19;
    public const SETTINGS_RIBBONS = 20;

    public const ARCHIVE_DOCUMENTS = 21;
    public const ARCHIVE_BOXES = 22;
    public const ARCHIVE_ARCHIVES = 23;
}

?>