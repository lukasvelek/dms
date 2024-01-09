<?php

namespace DMS\Constants;

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
        self::CREATE_RIBBONS
    );
}

?>