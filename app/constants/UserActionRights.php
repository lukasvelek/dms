<?php

namespace DMS\Constants;

/**
 * User action right constants
 * 
 * @author Lukas Velek
 */
class UserActionRights {
    public const CREATE_USER = 'create_user';
    public const CREATE_GROUP = 'create_group';
    public const MANAGE_USER_RIGHTS = 'manage_user_rights';
    public const MANAGE_GROUP_RIGHTS = 'manage_group_rights';
    public const CREATE_METADATA = 'create_metadata';
    public const DELETE_METADATA = 'delete_metadata';
    public const EDIT_METADATA_VALUES = 'edit_metadata_values';
    public const EDIT_USER_METADATA_RIGHTS = 'edit_user_metadata_rights';
    public const DELETE_COMMENTS = 'delete_comments';
    public const EDIT_USER = 'edit_user';
    public const REQUEST_PASSWORD_CHANGE_USER = 'request_password_change_user';
    public const GENERATE_DOCUMENT_REPORT = 'generate_document_report';
    public const RUN_SERVICE = 'run_service';
    public const EDIT_SERVICE = 'edit_service';
    public const SEE_DOCUMENT_INFORMATION = 'see_document_information';
    public const EDIT_DOCUMENT = 'edit_document';
    public const SHARE_DOCUMENT = 'share_document';
    public const CREATE_DOCUMENT = 'create_document';
    public const UPDATE_DEFAULT_USER_RIGHTS = 'update_default_user_rights';
    public const USE_DOCUMENT_GENERATOR = 'use_document_generator';
    public const EDIT_RIBBONS = 'edit_ribbons';
    public const EDIT_RIBBON_RIGHTS = 'edit_ribbon_rights';
    public const DELETE_RIBBONS = 'delete_ribbons';
    public const CREATE_RIBBONS = 'create_ribbons';
    public const EDIT_SYSTEM_FILTER = 'edit_system_filter';
    public const EDIT_OTHER_USERS_FILTER = 'edit_other_users_filter';
    public const DELETE_OTHER_USERS_FILTER = 'delete_other_users_filter';
    public const CREATE_FILTER = 'create_filter';
    public const SEE_SYSTEM_FILTER_RESULTS = 'see_system_filter_results';
    public const SEE_OTHER_USERS_FILTER_RESULTS = 'see_other_users_filter_results';
    public const SEE_SYSTEM_FILTERS = 'see_system_filters';
    public const SEE_OTHER_USERS_FILTERS = 'see_other_users_filters';
    public const CREATE_USER_CONNECTIONS = 'create_user_connections';
    public const REMOVE_USER_CONNECTIONS = 'remove_user_connections';
    public const ALLOW_RELOGIN = 'allow_relogin';
    public const DELETE_RIBBON_CACHE = 'delete_ribbon_cache';
    public const VIEW_USER_PROFILE = 'view_user_profile';
    public const VIEW_GROUP_USERS = 'view_group_users';
    public const CREATE_DOCUMENT_FOLDER = 'create_document_folder';
    public const DELETE_USER = 'delete_user';
    public const DELETE_GROUP = 'delete_group';
    public const CREATE_ARCHIVE_DOCUMENT = 'create_archive_document';
    public const CREATE_ARCHIVE_BOX = 'create_archive_box';
    public const CREATE_ARCHIVE_ARCHIVE = 'create_archive_archive';
    public const MOVE_ENTITIES_WITHIN_ARCHIVE = 'move_entities_within_archive';
    public const MOVE_ENTITIES_FROM_TO_ARCHIVE = 'move_entities_from_to_archive';
    public const VIEW_ARCHIVE_DOCUMENT_CONTENT = 'view_archive_document_content';
    public const VIEW_ARCHIVE_BOX_CONTENT = 'view_archive_box_content';
    public const VIEW_ARCHIVE_ARCHIVE_CONTENT = 'view_archive_archive_content';
    public const DELETE_ARCHIVE_DOCUMENT = 'delete_archive_document';
    public const DELETE_ARCHIVE_BOX = 'delete_archive_box';
    public const DELETE_ARCHIVE_ARCHIVE = 'delete_archive_archive';
    public const EDIT_ARCHIVE_DOCUMENT = 'edit_archive_document';
    public const EDIT_ARCHIVE_BOX = 'edit_archive_box';
    public const EDIT_ARCHIVE_ARCHIVE = 'edit_archive_archive';
    public const CLOSE_ARCHIVE = 'close_archive';
    public const EDIT_METADATA = 'edit_metadata';
    public const DELETE_DOCUMENT_REPORT_QUEUE_ENTRY = 'delete_document_report_queue_entry';
    public const VIEW_FILE_STORAGE_LOCATIONS = 'view_file_storage_locations';
    public const EDIT_FILE_STORAGE_LOCATIONS = 'edit_file_storage_locations';

    public static $all = array(
        self::CREATE_USER,
        self::CREATE_GROUP,
        self::MANAGE_USER_RIGHTS,
        self::MANAGE_GROUP_RIGHTS,
        self::CREATE_METADATA,
        self::DELETE_METADATA,
        self::EDIT_METADATA_VALUES,
        self::EDIT_USER_METADATA_RIGHTS,
        self::DELETE_COMMENTS,
        self::EDIT_USER,
        self::REQUEST_PASSWORD_CHANGE_USER,
        self::GENERATE_DOCUMENT_REPORT,
        self::RUN_SERVICE,
        self::EDIT_SERVICE,
        self::SEE_DOCUMENT_INFORMATION,
        self::EDIT_DOCUMENT, 
        self::SHARE_DOCUMENT,
        self::UPDATE_DEFAULT_USER_RIGHTS,
        self::USE_DOCUMENT_GENERATOR,
        self::CREATE_DOCUMENT,
        self::EDIT_RIBBONS,
        self::EDIT_RIBBON_RIGHTS,
        self::DELETE_RIBBONS,
        self::CREATE_RIBBONS,
        self::EDIT_SYSTEM_FILTER,
        self::EDIT_OTHER_USERS_FILTER,
        self::DELETE_OTHER_USERS_FILTER,
        self::CREATE_FILTER,
        self::SEE_SYSTEM_FILTER_RESULTS,
        self::SEE_OTHER_USERS_FILTER_RESULTS,
        self::SEE_SYSTEM_FILTERS,
        self::SEE_OTHER_USERS_FILTERS,
        self::CREATE_USER_CONNECTIONS,
        self::REMOVE_USER_CONNECTIONS,
        self::ALLOW_RELOGIN,
        self::DELETE_RIBBON_CACHE,
        self::VIEW_USER_PROFILE,
        self::VIEW_GROUP_USERS,
        self::CREATE_DOCUMENT_FOLDER,
        self::DELETE_USER,
        self::DELETE_GROUP,
        self::CREATE_ARCHIVE_DOCUMENT,
        self::CREATE_ARCHIVE_BOX,
        self::CREATE_ARCHIVE_ARCHIVE,
        self::MOVE_ENTITIES_FROM_TO_ARCHIVE,
        self::MOVE_ENTITIES_WITHIN_ARCHIVE,
        self::VIEW_ARCHIVE_DOCUMENT_CONTENT,
        self::VIEW_ARCHIVE_BOX_CONTENT,
        self::VIEW_ARCHIVE_ARCHIVE_CONTENT,
        self::DELETE_ARCHIVE_DOCUMENT,
        self::DELETE_ARCHIVE_BOX,
        self::DELETE_ARCHIVE_ARCHIVE,
        self::EDIT_ARCHIVE_DOCUMENT,
        self::EDIT_ARCHIVE_BOX,
        self::EDIT_ARCHIVE_ARCHIVE,
        self::CLOSE_ARCHIVE,
        self::EDIT_METADATA,
        self::DELETE_DOCUMENT_REPORT_QUEUE_ENTRY,
        self::VIEW_FILE_STORAGE_LOCATIONS,
        self::EDIT_FILE_STORAGE_LOCATIONS
    );
}

?>