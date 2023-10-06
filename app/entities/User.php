<?php

namespace DMS\Entities;

class User extends AEntity {
    /**
     * @var string
     */
    private $firstname;

    /**
     * @var string
     */
    private $lastname;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var string
     */
    private $addressStreet;
    
    /**
     * @var string
     */
    private $addressHouseNumber;

    /**
     * @var string
     */
    private $addressCity;
    
    /**
     * @var string
     */
    private $addressZipCode;

    /**
     * @var string
     */
    private $addressCountry;

    /**
     * @var string
     */
    private $username;

    public function __construct(int $id, string $dateCreated, string $firstname, string $lastname, string $username, ?string $email, bool $isActive, ?string $addressStreet, ?string $addressHouseNumber, ?string $addressCity, ?string $addressZipCode, ?string $addressCountry) {
        parent::__construct($id, $dateCreated);

        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->username = $username;
        $this->email = $email;
        $this->isActive = $isActive;
        $this->addressStreet = $addressStreet;
        $this->addressHouseNumber = $addressHouseNumber;
        $this->addressCity = $addressCity;
        $this->addressZipCode = $addressZipCode;
        $this->addressCountry = $addressCountry;
    }

    public function getFullname() {
        return $this->firstname . ' ' . $this->lastname;
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

    public function getIsActive() {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive) {
        $this->isActive = $isActive;
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
        return new self(0, date('Y-m-d H:i:s'), '', '', '', null, false, null, null, null, null, null);
    }
}

?>