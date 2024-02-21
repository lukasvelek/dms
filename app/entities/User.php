<?php

namespace DMS\Entities;

/**
 * User entity
 * 
 * @author Lukas Velek
 */
class User extends AEntity {
    private string $firstname;
    private string $lastname;
    private string $username;
    private ?string $email;
    private int $status;
    private ?string $addressStreet;
    private ?string $addressHouseNumber;
    private ?string $addressCity;
    private ?string $addressZipCode;
    private ?string $addressCountry;
    private ?string $datePasswordChanged;
    private int $passwordChangeStatus;
    private ?string $defaultUserPageUrl;
    private ?string $defaultUserDateTimeFormat;

    /**
     * Class constructor
     * 
     * @param string $firstname User's firstname
     * @param string $lastname User's lastname
     * @param string $username User's username
     * @param null|string $email User's email
     * @param int $status User status
     * @param null|string $addressStreet Address street
     * @param null|string $addressHOuseNumber Address house number
     * @param null|string $addressCity Address city
     * @param null|string $addressZipCode Address zip code
     * @param null|string $addressCountry Address country
     * @param null|string $datePasswordChanged Date password changed
     * @param int $passwordChangeStatus Password change status
     * @param null|string $defaultUserPageUrl Default user page URL
     * @param null|string $dateUpdated Date updated
     * @param null|string $defaultUserDateTimeFormat Default user date time format
     */
    public function __construct(int $id, string $dateCreated, string $firstname, string $lastname, string $username, ?string $email, int $status, ?string $addressStreet, ?string $addressHouseNumber, ?string $addressCity, ?string $addressZipCode, ?string $addressCountry, ?string $datePasswordChanged, int $passwordChangeStatus, ?string $defaultUserPageUrl, ?string $dateUpdated, ?string $defaultUserDateTimeFormat) {
        parent::__construct($id, $dateCreated, $dateUpdated);

        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->username = $username;
        $this->email = $email;
        $this->status = $status;
        $this->addressStreet = $addressStreet;
        $this->addressHouseNumber = $addressHouseNumber;
        $this->addressCity = $addressCity;
        $this->addressZipCode = $addressZipCode;
        $this->addressCountry = $addressCountry;
        $this->datePasswordChanged = $datePasswordChanged;
        $this->passwordChangeStatus = $passwordChangeStatus;
        $this->defaultUserPageUrl = $defaultUserPageUrl;
        $this->defaultUserDateTimeFormat = $defaultUserDateTimeFormat;
    }

    /**
     * Returns user's default datetime format
     * 
     * @return null|string Default user datetime format or null
     */
    public function getDefaultUserDateTimeFormat() {
        return $this->defaultUserDateTimeFormat;
    }

    /**
     * Sets user's default datetime format
     * 
     * @param string $format Datetime format
     */
    public function setDefaultUserDateTimeFormat(string $format) {
        $this->defaultUserDateTimeFormat = $format;
    }

    /**
     * Returns user's fullname
     * 
     * @return string User's fullname
     */
    public function getFullname() {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Returns user's firstname
     * 
     * @return string User's firstname
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Sets user's firstname
     * 
     * @param string $firstname User's firstname
     */
    public function setFirstname(string $firstname) {
        $this->firstname = $firstname;
    }

    /**
     * Returns user's lastname
     * 
     * @return string User's lastname
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Sets user's lastname
     * 
     * @param string $lastname User's lastname
     */
    public function setLastname(string $lastname) {
        $this->lastname = $lastname;
    }

    /**
     * Returns user's username
     * 
     * @return string User's username
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Sets user's username
     * 
     * @param string $firstname User's username
     */
    public function setUsername(string $username) {
        $this->username = $username;
    }

    /**
     * Returns user's email
     * 
     * @return null|string User's email or null
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Sets user's email
     * 
     * @param string $firstname User's email
     */
    public function setEmail(string $email) {
        $this->email = $email;
    }

    /**
     * Returns user's status
     * 
     * @return int User's status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Sets user's status
     * 
     * @param int $status User's status
     */
    public function setStatus(int $status) {
        $this->status = $status;
    }

    /**
     * Returns user's address' street
     * 
     * @return null|string Address street or null
     */
    public function getAddressStreet() {
        return $this->addressStreet;
    }

    /**
     * Sets user's address' street
     * 
     * @param string $addressStreet Address street
     */
    public function setAddressStreet(string $addressStreet) {
        $this->addressStreet = $addressStreet;
    }

    /**
     * Returns user's address' house number
     * 
     * @return null|string Address house number or null
     */
    public function getAddressHouseNumber() {
        return $this->addressHouseNumber;
    }

    /**
     * Sets user's address' house number
     * 
     * @param string $addressHouseNumber Address house number
     */
    public function setAddressHouseNumber(string $addressHouseNumber) {
        $this->addressHouseNumber = $addressHouseNumber;
    }

    /**
     * Returns user's address' city
     * 
     * @return null|string Address city or null
     */
    public function getAddressCity() {
        return $this->addressCity;
    }

    /**
     * Sets user's address' city
     * 
     * @param string $addressCity Address city
     */
    public function setAddressCity(string $addressCity) {
        $this->addressCity = $addressCity;
    }

    /**
     * Returns user's address' zip code
     * 
     * @return null|string Address zip code or null
     */
    public function getAddressZipCode() {
        return $this->addressZipCode;
    }

    /**
     * Sets user's address zip code
     * 
     * @param string $addressZipCode Address zip code
     */
    public function setAddressZipCode(string $addressZipCode) {
        $this->addressZipCode = $addressZipCode;
    }

    /**
     * Returns user's address' country
     * 
     * @return null|string Address country or null
     */
    public function getAddressCountry() {
        return $this->addressCountry;
    }

    /**
     * Sets user's address country
     * 
     * @param string $addressCountry Address country
     */
    public function setAddressCountry(string $addressCountry) {
        $this->addressCountry = $addressCountry;
    }

    /**
     * Returns date password changed
     * 
     * @return null|string Date password changed
     */
    public function getDatePasswordChanged() {
        return $this->datePasswordChanged;
    }

    /**
     * Sets user's firstname
     * 
     * @param string $firstname User's firstname
     */
    public function setDatePasswordChanged(?string $date) {
        $this->datePasswordChanged = $date;
    }

    /**
     * Returns password change status
     * 
     * @return int Password change status
     */
    public function getPasswordChangeStatus() {
        return $this->passwordChangeStatus;
    }

    /**
     * Sets user's firstname
     * 
     * @param string $firstname User's firstname
     */
    public function setPasswordChangeStatus(int $status) {
        $this->passwordChangeStatus = $status;
    }

    /**
     * Returns user's default page URL
     * 
     * @return null|string Default user page or null
     */
    public function getDefaultUserPageUrl() {
        return $this->defaultUserPageUrl;
    }

    /**
     * Sets user's firstname
     * 
     * @param string $firstname User's firstname
     */
    public function setDefaultUserPageUrl(?string $defaultUserPageUrl) {
        $this->defaultUserPageUrl = $defaultUserPageUrl;
    }

    /**
     * Creates a user entity instance from given array values
     * 
     * @return self
     */
    public static function createUserObjectFromArrayValues(array $values) {
        $emptyUser = self::createEmptyUser();

        foreach($values as $key => $value) {
            if($key == 'id') {
                $emptyUser->setId($value);
                continue;
            } else if($key == 'dateCreated') {
                $emptyUser->setDateCreated($value);
                continue;
            }

            $methodName = 'set' . $key;

            if(method_exists($emptyUser, $methodName)) {
                $emptyUser->$methodName($value);
            }
        }

        return $emptyUser;
    }

    /**
     * Creates empty user instance
     * 
     * @return self
     */
    public static function createEmptyUser() {
        return new self(0, date('Y-m-d H:i:s'), '', '', '', null, false, null, null, null, null, null, null, 1, null, null, null);
    }
}

?>