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
    public const ROOT_CURRENT_USER = 5;
    
    public const DOCUMENTS_ALL_DOCUMENTS = 20;
    public const DOCUMENTS_WAITING_FOR_ARCHIVATION = 21;
    public const DOCUMENTS_NEW_DOCUMENTS = 22;
    public const DOCUMENTS_SPLITTER = 23;
    
    public const PROCESSES_STARTED_BY_ME = 40;
    public const PROCESSES_WAITING_FOR_ME = 41;
    public const PROCESSES_FINISHED = 42;

    public const SETTINGS_DASHBOARD = 60;
    public const SETTINGS_DOCUMENT_FOLDERS = 61;
    public const SETTINGS_USERS = 62;
    public const SETTINGS_GROUPS = 63;
    public const SETTINGS_METADATA = 64;
    public const SETTINGS_SYSTEM = 65;
    public const SETTINGS_SERVICES = 66;
    public const SETTINGS_DASHBOARD_WIDGETS = 67;
    public const SETTINGS_RIBBONS = 68;

    public const ARCHIVE_DOCUMENTS = 80;
    public const ARCHIVE_BOXES = 81;
    public const ARCHIVE_ARCHIVES = 82;

    public const CURRENT_USER_SETTINGS = 100;
    public const CURRENT_USER_DOCUMENT_REPORTS = 101;
}

?>