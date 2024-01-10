<?php

namespace DMS\Entities;

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

    public function __construct(int $id, string $dateCreated, string $firstname, string $lastname, string $username, ?string $email, int $status, ?string $addressStreet, ?string $addressHouseNumber, ?string $addressCity, ?string $addressZipCode, ?string $addressCountry, ?string $datePasswordChanged, int $passwordChangeStatus, ?string $defaultUserPageUrl, ?string $dateUpdated) {
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
    }

    public function getFullname() {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    public function getFirstname() {
        return $this->firstname;
    }

    public function setFirstname(string $firstname) {
        $this->firstname = $firstname;
    }

    public function getLastname() {
        return $this->lastname;
    }

    public function setLastname(string $lastname) {
        $this->lastname = $lastname;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername(string $username) {
        $this->username = $username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail(string $email) {
        $this->email = $email;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus(int $status) {
        $this->status = $status;
    }

    public function getAddressStreet() {
        return $this->addressStreet;
    }

    public function setAddressStreet(string $addressStreet) {
        $this->addressStreet = $addressStreet;
    }

    public function getAddressHouseNumber() {
        return $this->addressHouseNumber;
    }

    public function setAddressHouseNumber(string $addressHouseNumber) {
        $this->addressHouseNumber = $addressHouseNumber;
    }

    public function getAddressCity() {
        return $this->addressCity;
    }

    public function setAddressCity(string $addressCity) {
        $this->addressCity = $addressCity;
    }

    public function getAddressZipCode() {
        return $this->addressZipCode;
    }

    public function setAddressZipCode(string $addressZipCode) {
        $this->addressZipCode = $addressZipCode;
    }

    public function getAddressCountry() {
        return $this->addressCountry;
    }

    public function setAddressCountry(string $addressCountry) {
        $this->addressCountry = $addressCountry;
    }

    public function getDatePasswordChanged() {
        return $this->datePasswordChanged;
    }

    public function setDatePasswordChanged(?string $date) {
        $this->datePasswordChanged = $date;
    }

    public function getPasswordChangeStatus() {
        return $this->passwordChangeStatus;
    }

    public function setPasswordChangeStatus(int $status) {
        $this->passwordChangeStatus = $status;
    }

    public function getDefaultUserPageUrl() {
        return $this->defaultUserPageUrl;
    }

    public function setDefaultUserPageUrl(?string $defaultUserPageUrl) {
        $this->defaultUserPageUrl = $defaultUserPageUrl;
    }

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

    public static function createEmptyUser() {
        return new self(0, date('Y-m-d H:i:s'), '', '', '', null, false, null, null, null, null, null, null, 1, null, null);
    }
}

?>